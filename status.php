<?php
// 캐시 제어 및 브라우저 강제 새로고침 헤더 설정
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // 과거의 특정 날짜로 캐시 만료 설정

//////////////////////////// 공통 헤더 및 환경 설정 부
$cache_time = 2;
$OJ_CACHE_SHARE = false;
require_once('./include/cache_start.php');
require_once('./include/db_info.inc.php');
require_once('./include/memcache.php');
require_once('./include/setlang.php');
require_once("./include/my_func.inc.php");
$view_title = "$MSG_STATUS";

if (isset($OJ_LANG)) {
    require_once("./lang/$OJ_LANG.php");
}

require_once("./include/const.inc.php");

$str2 = "";
$lock = false;
$lock_time = date("Y-m-d H:i:s", time());

$sql = "WHERE problem_id>0 ";

// [분기] 대회(Contest) ID 매개변수가 넘어온 경우, 해당 대회의 채점 조건 추가
if (isset($_GET['cid'])) {
    $cid = intval($_GET['cid']);
    $sql = $sql . " AND `contest_id`='$cid' and num>=0 ";
    $str2 = $str2 . "&cid=$cid";
    $sql_lock = "SELECT * FROM `contest` WHERE `contest_id`=?";

    $view_cid = $cid;
    $result = pdo_query($sql_lock, $cid);
    $rows_cnt = count($result);
    $start_time = 0;
    $end_time = 0;

    if ($rows_cnt > 0) {
        $row = $result[0];
        $title = $row['title'];
        $contest_type = $row['contest_type'];
        $start_time = strtotime($row['start_time']);
        $end_time = strtotime($row['end_time']);
        $view_description = $row['description'];
        $view_title = $row['title'];
        $view_start_time = $row['start_time'];
        $view_end_time = $row['end_time'];
        
        // NOIP(특수 올림피아드 형식) 규정이나 차단 조건이 충족되었는지 확인
        $noip = (time() < $end_time) && ((stripos($title, $OJ_NOIP_KEYWORD) !== false) || ($contest_type & 16) > 0);
        
        // 관리자, 대회 생성자, 소스 리뷰어 등 마스터 권한은 NOIP 제약 조건을 무시합니다.
        if (isset($_SESSION[$OJ_NAME . '_' . "administrator"]) ||
                isset($_SESSION[$OJ_NAME . '_' . "m$cid"]) ||
                isset($_SESSION[$OJ_NAME . '_' . "source_browser"]) ||
                isset($_SESSION[$OJ_NAME . '_' . "contest_creator"])
        ) $noip = false;
        
        // 실시간 소스 열람 차단(NOIP 모드) 시 예외 처리 화면 표출 후 스크립트 종료
        if ($noip) {
            $view_errors = "<h2> $MSG_NOIP_WARNING <a href=\"contest.php?cid=$cid\">$MSG_RETURN_CONTEST</a></h2>";
            $refererUrl = parse_url($_SERVER['HTTP_REFERER']);
            $top = intval($_GET['top']);
            if ($refererUrl['path'] == "/submitpage.php") {
                $view_errors = "<h2>$MSG_SUBMIT $MSG_SUCCESS! $top </h2><a href=\"contest.php?cid=$cid\">$MSG_RETURN_CONTEST</a></h2>";
            }
            require("template/" . $OJ_TEMPLATE . "/error.php");
            exit(0);
        }
    }

    // 대회 마감 직전 순위표 및 채점 현황을 얼려버리는(Lock) 시간 산출
    $lock_time = $end_time - ($end_time - $start_time) * $OJ_RANK_LOCK_PERCENT;
    if (time() > $lock_time && time() < $end_time) {
        $lock = true; // 현재 랭킹 락(블라인드) 상태 활성화
    } else {
        $lock = false;
    }

} else {
    // [분기] 일반 연습 모드 상태일 때 사용자 권한별 SQL 검색 조건 매핑
    if (isset($_SESSION[$OJ_NAME . '_' . 'administrator'])       // 서비스 최고 관리자
            || isset($_SESSION[$OJ_NAME . '_' . 'source_browser'])   // 소스코드 감독관
            || (isset($_SESSION[$OJ_NAME . '_' . 'user_id']) && (isset($_GET['user_id']) && $_GET['user_id'] == $_SESSION[$OJ_NAME . '_' . 'user_id']))  // 일반 사용자가 자기 자신의 기록만 조회할 때
    ) {
        if (isset($_SESSION[$OJ_NAME . '_' . 'source_browser'])) {
            $sql = "WHERE problem_id>0  "; // 소스 리뷰어는 연습 상태에서도 모든 유저의 대회 제출 기록 추적 가능
        } else if ($_SESSION[$OJ_NAME . '_' . 'user_id'] != "guest") {
            $sql = "WHERE (contest_id=0 or contest_id is null)  "; // 일반 연습 상태에서 본인의 대회 제출을 숨기려면 이 상태를 유지, 만약 연습 창에서 모든 대회 제출 내역도 같이 보게 하려면 'where problem_id>0' 으로 수정합니다.
        }
    } else {
        // 비로그인 게스트 혹은 타인의 기록을 볼 때: 랭킹 숨김 처리된 유저 제외 및 순수 연습 문제(`contest_id=0`)만 표출
        $sql = "WHERE solution.user_id not in ($OJ_RANK_HIDDEN) and  problem_id>0 and (contest_id=0 or contest_id is null) "; 
    }
}

$start_first = true;
$order_str = " ORDER BY `solution_id` DESC "; // 최신 채점 제출 건이 가장 먼저 오도록 내림차순 정렬

// 페이징용 최상단 solution_id(top) 검색 매개변수 적용
if (isset($_GET['top'])) {
    $top = strval(intval($_GET['top']));
    if ($top != -1)
        $sql = $sql . "AND `solution_id`<='" . $top . "' ";
}

// 문제 번호(problem_id) 검색 조건 구체화 및 보안 처리
$problem_id = "";
if (isset($_GET['problem_id']) && $_GET['problem_id'] != "") {
    if (isset($_GET['cid'])) {
        // 대회 내부 문항(A, B, C...)인 경우 배열 매핑 인덱스로 추적
        $problem_id = htmlentities($_GET['problem_id'], ENT_QUOTES, 'UTF-8');
        $num = array_search($problem_id, $PID);
        $problem_id = $PID[$num];
        $sql = $sql . "AND `num`='" . $num . "' ";
        $str2 = $str2 . "&problem_id=" . trim($problem_id);
    } else {
        // 일반 단독 문항 검색인 경우 숫자형 검증 후 쿼리 조합
        $problem_id = strval(intval($_GET['problem_id']));
        if ($problem_id != '0') {
            $sql = $sql . "AND `problem_id`='" . $problem_id . "' ";
            $str2 = $str2 . "&problem_id=" . trim($problem_id);
        } else
            $problem_id = "";
    }
}

$param = array();

// 특정 사이트 대회 전용 예외 필터링 및 권한 강제 조율
if ((isset($OJ_ON_SITE_CONTEST_ID) && $OJ_ON_SITE_CONTEST_ID > 0 && !isset($_SESSION[$OJ_NAME . '_' . 'administrator']))
        || (isset($OJ_PUBLIC_STATUS) && !$OJ_PUBLIC_STATUS)
        || (isset($contest_type) && ($contest_type & 8) > 0)
) {
    if (!isset($_SESSION[$OJ_NAME . '_' . 'user_id']))
        $_GET['user_id'] = 'Guest';
    else if (!isset($_SESSION[$OJ_NAME . '_' . 'source_browser']))
        $_GET['user_id'] = $_SESSION[$OJ_NAME . '_' . 'user_id'];
}

// 특정 유저 아이디(user_id) 검색용 바인딩 파라미터 적재
if (isset($_GET['user_id'])) {
    $user_id = trim($_GET['user_id']);
    if ($user_id != "") {
        $sql = $sql . "AND solution.user_id=? ";
        if ($str2 != "")
            $str2 = $str2 . "&";
        $str2 = $str2 . "&user_id=" . htmlentities(urlencode($user_id), ENT_QUOTES);
        array_push($param, $user_id);
    } else {
        $user_id = "";
    }
}

// 프로그래밍 언어(C++, Java, Python 등) 검색용 예외 검사 및 적재
if (isset($_GET['language'])) {
    $language = intval($_GET['language']);
    if ($language > count($language_ext) || $language < 0)
        $language = -1;
    if ($language != -1) {
        $sql = $sql . "AND solution.`language`=?  ";
        $str2 = $str2 . "&language=" . $language;
        array_push($param, $language);
    }
} else {
    $language = -1;
}

// 채점 결과 상태(AC, WA, TLE 등) 검색 필터 적용 (대회 락 상태일 때는 결과 필터 무효화)
if (isset($_GET['jresult'])) {
    $result = intval($_GET['jresult']);
    if ($result != -1 && !$lock) {
        $sql = $sql . "AND `result`=? ";
        $str2 = $str2 . "&jresult=" . $result;
        array_push($param, $result);
    }
} else {
    $result = -1;
}

// 코드 표절 검사 모듈(SIM) 활성화 유무에 따른 반환 컬럼 확장 필드 세팅
$showsim = isset($_GET['showsim']) ? intval($_GET['showsim']) : 0;
if ($OJ_SIM & $showsim > 0) {
    $fields = "solution.*,users.nick,users.group_name,users.starred,sim.*";
} else {
    $fields = "solution.*,users.nick,users.group_name,users.starred";
}

// [분기] 학교 이름(school) 혹은 그룹명(group_name)으로 필터링해서 대량 조회할 때의 최적화 이너 조인 쿼리 빌드
if (isset($_GET['school']) && trim($_GET['school']) != "" || isset($_GET['group_name']) && trim($_GET['group_name']) != "") {
    $topwhere = $sql;
    $sql0 = "select $fields from (select * from solution $topwhere $order_str LIMIT 1500) solution inner join users users on solution.user_id=users.user_id  and users.defunct='N' ";
    if (!empty($param)) {
        $values = array_values($param);
        foreach ($values as $v) {
            array_push($param, $v);
        }
    }
    if (isset($_GET['school']) && trim($_GET['school']) != "") {
        $school = trim($_GET['school']);
        $sql .= " and users.school=? ";
        array_push($param, trim($_GET['school']));
        $str2 = $str2 . "&school=" . htmlentities(trim($_GET['school']), ENT_QUOTES);
    }
    if (isset($_GET['group_name']) && trim($_GET['group_name']) != "") {
        $group_name = trim($_GET['group_name']);
        $sql .= " and users.group_name=? ";
        array_push($param, trim($_GET['group_name']));
        $str2 = $str2 . "&group_name=" . htmlentities(trim($_GET['group_name']), ENT_QUOTES);
    }
} else {
    // [분기] 일반적인 통합 실시간 채점 현황 서치 (속도 최적화를 위해 50개 단위로 LIMIT 제어)
    $topwhere = $sql;
    if (!empty($param)) {
        $values = array_values($param);
        foreach ($values as $v) {
            array_push($param, $v);
        }
    }
    $sql0 = "select $fields from ( select * from solution $topwhere $order_str limit 50 )solution inner join users on solution.user_id=users.user_id  and users.defunct='N' ";
}

// 코드 표절 모듈(SIM) 구문 최종 결합
if ($OJ_SIM & $showsim > 0) {
    $sql = $sql0 . " left join `sim` sim on solution.solution_id=sim.s_id " . $sql;
    if ($showsim > 0) {
        $sql .= " and sim.sim>=$showsim";
        $str2 .= "&showsim=$showsim";
    }
} else {
    $sql = $sql0 . " " . $sql;
}

$sql = $sql . $order_str . " LIMIT 50";

// 결합된 서브쿼리와 메인 조건 쿼리 실행
if (!empty($param)) {
    $result = pdo_query($sql, $param);
} else {
    $result = pdo_query($sql);
}

if (!empty($result))
    $rows_cnt = count($result);
else
    $rows_cnt = 0;

$top = $bottom = -1;
$cnt = 0;
if ($start_first) {
    $row_start = 0;
    $row_add = 1;
} else {
    $row_start = $rows_cnt - 1;
    $row_add = -1;
}

$view_status = array();

// 💡 [성능 최적화 패치] 다중 루프 내부 쿼리를 한 번에 다이렉트 캐싱하여 데이터베이스 N+1 쿼리 병목현상 방지
$hidden_problems_cache = array();
if (!isset($cid) && !empty($result)) {
    $problem_ids = array_column($result, 'problem_id');
    if (!empty($problem_ids)) {
        $pids_str = implode(',', array_map('intval', $problem_ids));
        $cache_sql = "SELECT DISTINCT cp.problem_id 
                      FROM contest_problem cp
                      INNER JOIN contest c ON cp.contest_id = c.contest_id
                      WHERE cp.problem_id IN ($pids_str)
                        AND c.defunct = 'N' 
                        AND c.end_time > NOW()";
        $cache_result = pdo_query($cache_sql);
        $hidden_problems_cache = array_column($cache_result, 'problem_id');
    }
}

$last = 0;
$avg_delay = 0;
$total_count = 0;
$need_refresh_remote = false;

// 추출된 50개 데이터 행을 하나씩 루프 돌리며 가공 처리 시작
for ($i = 0; $i < $rows_cnt; $i++) {
    $row = $result[$i];
    if ($i == 0 && $row['result'] < 4)
        $last = $row['solution_id'];

    if ($top == -1)
        $top = $row['solution_id'];

    $bottom = $row['solution_id'];
    
    // 소스코드를 직접 열람할 자격이 있는지 플래그 판별 (본인 소스, 최고 관리자, 소스 리뷰어 권한 유무)
    $flag = (!is_running(intval($row['contest_id'])))
            || isset($_SESSION[$OJ_NAME . '_' . 'source_browser'])
            || isset($_SESSION[$OJ_NAME . '_' . 'administrator'])
            || (isset($_SESSION[$OJ_NAME . '_' . 'user_id']) && !strcmp($row['user_id'], $_SESSION[$OJ_NAME . '_' . 'user_id']));

    $cnt = 1 - $cnt;

    $view_status[$i][0] = $row['solution_id'];

    // 하이퍼링크 래핑 분기: 대회 순위표 링크 연동 혹은 일반 유저 세부 정보 링크 연동
    if ($row['contest_id'] > 0) {
        if (isset($_SESSION[$OJ_NAME . '_' . 'administrator']))
            $view_status[$i][1] = "<a href='contestrank.php?cid=" . $row['contest_id'] . "&user_id=" . $row['user_id'] . "#" . $row['user_id'] . "' title='" . $row['ip'] . "'>" . $row['user_id'] . "</a>";
        else
            $view_status[$i][1] = "<a href='contestrank.php?cid=" . $row['contest_id'] . "&user_id=" . $row['user_id'] . "#" . $row['user_id'] . "'>" . $row['user_id'] . "</a>";
    } else {
        if (isset($_SESSION[$OJ_NAME . '_' . 'administrator']))
            $view_status[$i][1] = "<a href='userinfo.php?user=" . $row['user_id'] . "' title='" . $row['nick'] . "[" . $row['ip'] . "]'>" . $row['user_id'] . "</a>";
        else
            $view_status[$i][1] = "<a href='userinfo.php?user=" . $row['user_id'] . "' title='" . $row['nick'] . "'>" . $row['user_id'] . "</a>";
    }
    
    // 깃허브 스타(Star)처럼 오픈소스 기여 유저 요소를 표시하기 위한 특별 별 표시 처리
    if (isset($row['starred']) && $row['starred'] > 0) {
        $view_status[$i][1] = "⭐" . $view_status[$i][1] . "<span title='용同名账户给hustoj项目加星，可以点亮此星' >⭐</span>";
    }

    $view_status[$i]['nick'] = "<span title='" . $row['group_name'] . "@" . myLocation($row['ip']) . "'>" . (
            mb_strlen($row['nick']) > 10 ? mb_substr($row['nick'], 0, 7) . "..." : $row['nick']) . "</span>";

    // 문제 번호 탭 하이퍼링크 주소 유효성 빌드
    if ($row['contest_id'] > 0) {
        if (isset($end_time) && time() < $end_time) {
            $view_status[$i][2] = "<div><a href='problem.php?cid=" . $row['contest_id'] . "&pid=" . $row['num'] . "'>";
            if (isset($cid)) {
                $view_status[$i][2] .= $PID[$row['num']];
            } else {
                $view_status[$i][2] .= $row['problem_id'];
            }
            $view_status[$i][2] .= "</div></a>";
        } else {
            $view_status[$i][2] = "<div class=center>";
            if (isset($cid)) {
                // 상단에서 선행 수집한 벌크 캐시(hidden_problems_cache)를 이용해 O(1) 초고속 검증 (치명적인 다중 쿼리 제거 완료)
                if (in_array($row['problem_id'], $hidden_problems_cache))
                    $view_status[$i][2] .= $PID[$row['num']]; // 비공개 대회 문제인 경우 링크 차단
                else
                    $view_status[$i][2] .= "<a href='problem.php?id=" . $row['problem_id'] . "'>" . $PID[$row['num']] . "</a>";
            } else {
                $view_status[$i][2] .= "<a href='problem.php?id=" . $row['problem_id'] . "'>" . $row['problem_id'] . "</a>";
            }
            $view_status[$i][2] .= "</div>";
        }
    } else {
        $view_status[$i][2] = "<div class=center><a href='problem.php?id=" . $row['problem_id'] . "'>" . $row['problem_id'] . "</a></div>";
    }

    // 채점 결과 정수형 코드별 다국어 매핑 툴팁 분기 지정
    switch ($row['result']) {
        case 4: $MSG_Tips = $MSG_HELP_AC; break;  // 정답 완료 (Accepted)
        case 5: $MSG_Tips = $MSG_HELP_PE; break;  // 출력 형식이 다름 (Presentation Error)
        case 6: $MSG_Tips = $MSG_HELP_WA; break;  // 오답 (Wrong Answer)
        case 7: $MSG_Tips = $MSG_HELP_TLE; break; // 시간 초과 (Time Limit Exceeded)
        case 8: $MSG_Tips = $MSG_HELP_MLE; break; // 메모리 초과 (Memory Limit Exceeded)
        case 9: $MSG_Tips = $MSG_HELP_OLE; break; // 출력 초과 (Output Limit Exceeded)
        case 10: $MSG_Tips = $MSG_HELP_RE; break; // 런타임 에러 (Runtime Error)
        case 11: $MSG_Tips = $MSG_HELP_CE; break; // 컴파일 에러 (Compile Error)
        default: $MSG_Tips = "";
    }
    
    if ($row['result'] > 15) {
        $need_refresh_remote = true;
    }
    
    // 부분 점수 또는 테스트케이스 통과 비율 퍼센트 환산
    $AC_RATE = floatval($row['pass_rate'] * 100);
    if (isset($OJ_MARK) && $OJ_MARK != "mark") {
        if ($OJ_MARK == "percent" && $row['result'] > 4) {
            $mark = (100 - $AC_RATE) . "%";
        } else {
            $mark = "";
        }
    } else {
        if ($AC_RATE > 99 || $row['result'] == 4)
            $mark = "";
        else
            $mark = " " . $MSG_MARK . $AC_RATE;
    }

    if ((!isset($_SESSION[$OJ_NAME . '_' . 'user_id']) || $row['user_id'] != $_SESSION[$OJ_NAME . '_' . 'user_id']) && !isset($_SESSION[$OJ_NAME . '_' . 'source_browser']))
        $mark = "";

    $view_status[$i][3] = "<span class='hidden' style='display:none' result=" . $row['result'] . "></span>";
    if ($row['first_time'] == 1) $view_status[$i][3] .= "⭐"; // 퍼스트 킬(가장 먼저 맞춘 사람) 마크 부여
    
    // 컴파일 에러(CE) 시 세부 정보 열람 링크 연동 조율
    if (intval($row['result']) == 11 && ((isset($_SESSION[$OJ_NAME . '_' . 'user_id']) && $row['user_id'] == $_SESSION[$OJ_NAME . '_' . 'user_id']) || isset($_SESSION[$OJ_NAME . '_' . 'source_browser']))) {
        $view_status[$i][3] .= "<a href=ceinfo.php?sid=" . $row['solution_id'] . " class='" . $judge_color[$row['result']] . "' title='$MSG_Tips'>" . $MSG_Compile_Error . "</a>";
    // 런타임 에러(RE) 및 특정 오답 데이터 에러 정보 노출 분기 조율
    } else if ((((intval($row['result']) == 8 || intval($row['result']) == 7 || intval($row['result']) == 5 || intval($row['result']) == 6) && ($OJ_SHOW_DIFF || isset($_SESSION[$OJ_NAME . '_' . 'source_browser']))) || $row['result'] == 10 || $row['result'] == 13) && ((isset($_SESSION[$OJ_NAME . '_' . 'user_id']) && $row['user_id'] == $_SESSION[$OJ_NAME . '_' . 'user_id']) || isset($_SESSION[$OJ_NAME . '_' . 'source_browser']))) {
        $view_status[$i][3] .= "<a href=reinfo.php?sid=" . $row['solution_id'] . " class='" . $judge_color[$row['result']] . "' title='$MSG_Tips'>" . $judge_result[$row['result']] . $mark . "</a>";
    } else {
        // 대회 종료 전 얼려진(Lock) 시점이거나 타인의 코드인 경우 마스킹('----') 처리 분기
        if (!$lock || $lock_time > $row['in_date'] || $row['user_id'] == $_SESSION[$OJ_NAME . '_' . 'user_id']) {
            // 소스코드 표절(SIM) 수치가 기준치를 넘었을 때의 경고 마크 주입 부
            if ($OJ_SIM && isset($row['sim']) && $row['sim'] > 80 && $row['sim_s_id'] != $row['s_id']) {
                $view_status[$i][3] .= "<a href=reinfo.php?sid=" . $row['solution_id'] . " class='" . $judge_color[$row['result']] . "' title='$MSG_Tips'>*" . $judge_result[$row['result']];

                if ($row['result'] != 4 && isset($row['pass_rate']) && $row['pass_rate'] != 1)
                    $view_status[$i][3] .= $mark . "</a>";
                else
                    $view_status[$i][3] .= "</a>";

                if (isset($_SESSION[$OJ_NAME . '_' . 'source_browser'])) {
                    $view_status[$i][3] .= "<a href=comparesource.php?left=" . $row['sim_s_id'] . "&right=" . $row['solution_id'] . " class='label label-info' target=original>" . $row['sim_s_id'] . "(" . $row['sim'] . "%)</a>";
                } else {
                    $view_status[$i][3] .= "<span class='label label-info'>" . $row['sim_s_id'] . "</span>";
                }

                if (isset($_GET['showsim']) && isset($row['sim_s_id'])) {
                    $view_status[$i][3] .= "<span sid='" . $row['sim_s_id'] . "' class='original'></span>";
                }
            } else {
                $view_status[$i][3] .= "<a href=reinfo.php?sid=" . $row['solution_id'] . " class='" . $judge_color[$row['result']] . "' title='$MSG_Tips'>" . $judge_result[$row['result']] . $mark . "</a>";
            }
        } else {
            $view_status[$i][3] = "----"; // 순위표 동결 구간일 때 마스킹 표출
        }
    }

    if (isset($_SESSION[$OJ_NAME . '_' . 'http_judge'])) {
        $view_status[$i][3] .= "<form class='http_judge_form form-inline'> <input type=hidden name=sid value='" . $row['solution_id'] . "'>";
        $view_status[$i][3] .= "</form>";
    }

    // 소스 열람 자격 플래그 세부 수치(메모리 사용량, 실행 소요 시간, 사용 언어, 코드 크기) 표출 가공
    if ($flag) {
        if ($row['result'] >= 4) {
            $view_status[$i][4] = "<div id=center>" . $row['memory'] . " KiB</div>";
            $view_status[$i][5] = "<div id=center>" . $row['time'] . " ms</div>";
        } else {
            $view_status[$i][4] = "---";
            $view_status[$i][5] = "---";
        }

        if (!(isset($_SESSION[$OJ_NAME . '_' . 'user_id']) && strtolower($row['user_id']) == strtolower($_SESSION[$OJ_NAME . '_' . 'user_id'])
                || isset($_SESSION[$OJ_NAME . '_' . 'source_browser']))) {
            $view_status[$i][6] = $language_name[$row['language']];
        } else {
            if ((isset($end_time) && time() < $end_time)
                    || (isset($_SESSION[$OJ_NAME . '_' . 'user_id']) && strtolower($row['user_id']) == strtolower($_SESSION[$OJ_NAME . '_' . 'user_id']))
                    || isset($_SESSION[$OJ_NAME . '_' . 'source_browser'])
            )
                $view_status[$i][6] = "<a target=_self href=showsource.php?id=" . $row['solution_id'] . ">" . $language_name[$row['language']] . "</a>";
            else
                $view_status[$i][6] = $language_name[$row['language']];

            // 템플릿 테마 설정에 맞춘 원클릭 재제출(Edit) 하이퍼링크 주소 매핑
            if ((!(isset($OJ_OLD_FASHINED) && $OJ_OLD_FASHINED)) && ($OJ_TEMPLATE == "syzoj" || $OJ_TEMPLATE == "bs3") && $OJ_AUTO_SHOW_OFF) {
                $edit_link = "problem.php";
            } else {
                $edit_link = "submitpage.php";
            }
            if ($row["problem_id"] > 0) {
                if ($row['contest_id'] > 0) {
                    if (isset($end_time) && time() < $end_time || isset($_SESSION[$OJ_NAME . '_' . 'source_browser']))
                        $view_status[$i][6] .= "/<a target=_self href=\"$edit_link?cid=" . $row['contest_id'] . "&pid=" . $row['num'] . "&sid=" . $row['solution_id'] . "\">Edit</a>";
                    else
                        $view_status[$i][6] .= "";
                } else {
                    $view_status[$i][6] .= "/<a target=_self href=\"$edit_link?id=" . $row['problem_id'] . "&sid=" . $row['solution_id'] . "\">Edit</a>";
                }
            }
        }

        $view_status[$i][7] = $row['code_length'] . " bytes";

    } else {
        // 소스 열람 자격 권한이 없으면 메모리, 시간, 언어, 용량을 전부 블라인드 대시('----') 처리합니다.
        $view_status[$i][4] = "----";
        $view_status[$i][5] = "----";
        $view_status[$i][6] = "----";
        $view_status[$i][7] = "----";
    }

    // 제출 시점부터 채점 완료 시점까지 걸린 시스템 딜레이 평균치 연산 부
    $used = (strtotime($row['judgetime']) - strtotime($row['in_date']));
    if ($used > 0) {
        $avg_delay += floatval($used);
        $total_count++;
    }
    
    // 최고 관리자인 경우 아이디 탭에 제출 시간 마진과 담당 채점 코어 인덱스 번호를 상세 추가 표출
    if (isset($_SESSION[$OJ_NAME . '_' . 'administrator'])) {
        $view_status[$i][8] = substr($row['in_date'], 5) . "[" . $used . "]";
        $view_status[$i][9] = $row['judger'];
    } else
        $view_status[$i][8] = $row['in_date'];
}
if ($total_count > 0) $avg_delay /= $total_count;

///////////////////////// 프론트엔드 템플릿 뷰어 연동 부
if (isset($_GET['cid']))
    require("template/" . $OJ_TEMPLATE . "/conteststatus.php"); // 대회 전용 현황 템플릿
else
    require("template/" . $OJ_TEMPLATE . "/status.php");        // 일반 연습 전용 현황 템플릿

///////////////////////// 공통 푸터 및 전체 캐시 마감
if (file_exists('./include/cache_end.php'))
    require_once('./include/cache_end.php');
?>