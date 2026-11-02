<?php
/**
 * 검색어 POST 요청 여부에 따라 캐시 시간을 정한다.
 * 검색어가 있으면 짧게, 없으면 길게 캐시한다.
 */
if (isset($_POST['keyword']))
    $cache_time = 1;
else
    $cache_time = 10;

/**
 * 캐시 공유 여부. 현재는 false다.
 * 주석 처리된 코드는 원래 cid나 my 인자가 있을 때 캐시를 끄는 용도였다.
 */
$OJ_CACHE_SHARE = false;//!(isset($_GET['cid'])||isset($_GET['my']));

/**
 * 필요한 시스템 파일을 불러온다.
 * 캐시, 데이터베이스, 메모리 캐시, 공통 함수, 상수, 언어 설정이 포함된다.
 */
require_once('./include/cache_start.php');
require_once('./include/db_info.inc.php');
require_once('./include/memcache.php');
require_once('./include/my_func.inc.php');
require_once('./include/const.inc.php');
require_once('./include/setlang.php');

/**
 * 페이지 제목을 대회 제목으로 설정한다.
 */
$view_title = $MSG_CONTEST;

/**
 * 현재 타임스탬프를 가져온다.
 */
$now = time();

/**
 * 대회 상세 페이지를 처리한다.
 * cid 인자가 있으면 해당 대회의 문제 목록을 보여 준다.
 */
if (isset($_GET['cid'])) {

    require_once("contest-check.php");

    /**
     * 대회에 포함된 문제 정보를 조회한다.
     * 내부 조인으로 문제 제목, ID, 출처, 대회 내 문제 번호를 가져온다.
     */
    $sql = "select p.title,p.problem_id,p.source,cp.num as pnum,p.coin coin,cp.c_accepted accepted,cp.c_submit submit from problem p inner join contest_problem cp on p.problem_id = cp.problem_id and cp.contest_id=$cid order by cp.num";
    $result = mysql_query_cache($sql);
    $view_problemset = array();
    $pids = array_column($result, 'problem_id');
    if (!empty($pids)) $pids = implode(",", $pids);
    $cnt = 0;

    /**
     * NOIP 대회이거나 잠긴 대회인지 판단한다.
     * 대회가 진행 중이면서 NOIP 키워드가 있거나 잠겨 있는지 확인한다.
     */
    $noip = (time() < $end_time) && (stripos($view_title, $OJ_NOIP_KEYWORD) !== false || contest_locked($cid, 16));
    $hide_others = contest_locked($cid, 8);

    /**
     * 관리자, 대회 관리자, 소스 열람자, 대회 출제자는 NOIP 제한을 받지 않는다.
     */
    if (isset($_SESSION[$OJ_NAME . '_' . "administrator"]) ||
        isset($_SESSION[$OJ_NAME . '_' . "m$cid"]) ||
        isset($_SESSION[$OJ_NAME . '_' . "source_browser"]) ||
        isset($_SESSION[$OJ_NAME . '_' . "contest_creator"])
    ) $noip = false;

    /**
     * 조회 결과를 순회하며 문제 목록을 만든다.
     * 대회 상태와 사용자 권한에 따라 문제 표시 내용을 정한다.
     */
    foreach ($result as $row) {
        $view_problemset[$cnt][0] = "";
        if (isset($_SESSION[$OJ_NAME . '_' . 'user_id'])) {
            $ac = check_ac($cid, $cnt, $noip);
            $sub = "";
            if ($ac != "") $sub = "?";
            if ($noip)
                $view_problemset[$cnt][0] = "$sub";
            else
                $view_problemset[$cnt][0] = "$ac";

        } else
            $view_problemset[$cnt][0] = "";


        if ($now < $end_time) { //대회 진행 중
            $view_problemset[$cnt][1] = "<a href='problem.php?cid=$cid&pid=$cnt'>" . $PID[$cnt] . "</a>";
            $view_problemset[$cnt][2] = "<a href='problem.php?cid=$cid&pid=$cnt'>" . $row['title'] . "</a>";
            $view_problemset[$cnt][2] .= intval($row['coin']) > 0 ? str_repeat('<span style=\'color:orange\'>✦</span>', min(intval($row['coin']), 5)) : '';
        } else {               //대회 종료
            //문제가 다른 대회에서 쓰일 예정인지 확인한다.
            $tpid = intval($row['problem_id']);
            $sql = "SELECT `problem_id` FROM `problem` WHERE `problem_id`=? AND `problem_id` IN (
				SELECT `problem_id` FROM `contest_problem` WHERE `contest_id` IN (
					SELECT `contest_id` FROM `contest` WHERE (`defunct`='N' AND now()<`start_time`)
				)
			)";

            $tresult = pdo_query($sql, $tpid);

            if (intval($tresult) != 0 && !isset($_SESSION[$OJ_NAME . '_' . "m$cid"])) {
                //다른 비공개 대회에서 쓰일 문제는 다른 교사와 학생에게 보여 주지 않는다.
                $view_problemset[$cnt][1] = $PID[$cnt]; //대회 종료 후 제목을 숨긴다.
                $view_problemset[$cnt][2] = '--using in another private contest--';
            } else {
                $view_problemset[$cnt][1] = "<a href='problem.php?id=" . $row['problem_id'] . "'>" . $PID[$cnt] . "</a>";
                if ($contest_ok)
                    $view_problemset[$cnt][2] = "<a href='problem.php?cid=$cid&pid=$cnt'>" . $row['title'] . "</a>";
                else
                    $view_problemset[$cnt][2] = $row['title'];
            }
        }

        //$view_problemset[$cnt][3] = $row['source'];

        /**
         * NOIP 또는 숨김 설정에 따라 정답 수와 제출 수 표시 여부를 정한다.
         * 관리자는 제한을 받지 않는다.
         */
        if (($noip || $hide_others) && !(isset($_SESSION[$OJ_NAME . 'm' . $cid]) || isset($_SESSION[$OJ_NAME . '_administrator']))) {
            $view_problemset[$cnt][3] = "<span class=red>?</span>";
            $view_problemset[$cnt][4] = "<span class=red>?</span>";
        } else {
            $view_problemset[$cnt][3] = $row['accepted'];
            $view_problemset[$cnt][4] = $row['submit'];
        }


        $cnt++;
    }
} else {
    /**
     * 대회 목록 페이지를 처리한다.
     * cid 인자가 없으면 대회 목록을 보여 준다.
     */
    $page = 1;
    if (isset($_GET['page']))
        $page = intval($_GET['page']);

    $page_cnt = 25;
    $pstart = $page_cnt * $page - $page_cnt;
    $pend = $page_cnt;
    // 대회 목록과 수행평가 목록(assessment.php)을 contest_type 9번 비트로 나눠 보여 준다.
    $gs_assessment_list = isset($GS_ASSESSMENT_LIST) && $GS_ASSESSMENT_LIST;
    $gs_type_cond = $gs_assessment_list ? " AND (contest_type & 512)<>0" : " AND (contest_type & 512)=0";
    $gs_list_page = $gs_assessment_list ? "assessment.php" : "contest.php";
    $rows = pdo_query("select count(1) from contest where defunct='N' $gs_type_cond");

    if ($rows)
        $total = $rows[0][0];

    $view_total_page = intval($total / $page_cnt) + 1;
    $keyword = "";

    if (isset($_POST['keyword'])) {
        $keyword = "%" . $_POST['keyword'] . "%";
    }

    //echo "$keyword";
    $mycontests = "";
    $wheremy = "";

    /**
     * 현재 사용자가 참가한 대회 목록을 가져온다.
     * "내 대회" 기능에 쓰인다.
     */
    if (isset($_SESSION[$OJ_NAME . '_user_id'])) {
        $sql = "select distinct contest_id from solution where contest_id>0 and user_id=?";
        $result = pdo_query($sql, $_SESSION[$OJ_NAME . '_user_id']);

        foreach ($result as $row) {
            if (intval($row['contest_id']) > 0)
                $mycontests .= "," . $row['contest_id'];
        }

        $len = mb_strlen($OJ_NAME . '_');
        $user_id = $_SESSION[$OJ_NAME . '_' . 'user_id'];

        if ($user_id) {
            // 로그인한 사용자
            $sql = "SELECT * FROM `privilege` WHERE `user_id`=?";
            $result = pdo_query($sql, $user_id);

            // 각종 권한을 새로 고친다.
            foreach ($result as $row) {
                if (isset($row['valuestr'])) {
                    $_SESSION[$OJ_NAME . '_' . $row['rightstr']] = $row['valuestr'];
                } else {
                    $_SESSION[$OJ_NAME . '_' . $row['rightstr']] = true;
                }
            }
            if (isset($_SESSION[$OJ_NAME . '_vip'])) {  // VIP mark can access all [VIP] marked contest
                $sql = "select contest_id from contest where title like '%[VIP]%'";
                $result = pdo_query($sql);
                foreach ($result as $row) {
                    $_SESSION[$OJ_NAME . '_c' . $row['contest_id']] = true;
                }
            };
        }

        foreach ($_SESSION as $key => $value) {
            if ((mb_substr($key, $len, 1) == 'm' || mb_substr($key, $len, 1) == 'c') && intval(mb_substr($key, $len + 1)) > 0) {
                //echo substr($key,1)."<br>";
                $mycontests .= "," . intval(mb_substr($key, $len + 1));
            }
        }

        //echo "=====>$mycontests<====";

        if (strlen($mycontests) > 0)
            $mycontests = substr($mycontests, 1);
        if (isset($_GET['my']) && $mycontests != "")
            if (isset($_GET['my'])) $wheremy = " and( contest_id in ($mycontests) or user_id='" . $_SESSION[$OJ_NAME . '_user_id'] . "')";
    }

    $sql = "SELECT * FROM `contest` WHERE `defunct`='N' ORDER BY `contest_id` DESC LIMIT 1000";

    if ($keyword) {
        $sql = "SELECT *  FROM contest WHERE contest.defunct='N' AND contest.title LIKE ? $wheremy $gs_type_cond  ORDER BY contest_id DESC";
        $sql .= " limit " . strval($pstart) . "," . strval($pend);

        $result = pdo_query($sql, $keyword);
    } else {
        $sql = "SELECT *  FROM contest WHERE contest.defunct='N' $wheremy $gs_type_cond  ORDER BY contest_id DESC";
        $sql .= " limit " . strval($pstart) . "," . strval($pend);
        //echo $sql;
        $result = mysql_query_cache($sql);
    }

    $view_contest = array();
    $i = 0;

    /**
     * 조회 결과를 순회하며 대회 목록을 만든다.
     * 대회 상태(종료, 시작 전, 진행 중)에 따라 표시 내용을 다르게 정한다.
     */
    foreach ($result as $row) {
        $view_contest[$i][0] = $row['contest_id'];

        if (trim($row['title']) == "")
            $row['title'] = $MSG_CONTEST . $row['contest_id'];

        $view_contest[$i][1] = "<a href='contest.php?cid=" . $row['contest_id'] . "'>" . $row['title'] . "</a>";
        $start_time = strtotime($row['start_time']);
        $end_time = strtotime($row['end_time']);
        $now = time();

        $length = $end_time - $start_time;
        $left = $end_time - $now;

        if ($end_time <= $now) {
            //종료
            $view_contest[$i][2] = "<span class=text-muted>$MSG_Ended</span>" . " " . "<span class=text-muted>" . $row['end_time'] . "</span>";

        } else if ($now < $start_time) {
            //시작 전
            $view_contest[$i][2] = "<span class=text-success>$MSG_Start</span>" . " " . $row['start_time'] . "&nbsp;";
            $view_contest[$i][2] .= "<span class=text-success>$MSG_TotalTime</span>" . " " . formatTimeLength($length);
        } else {
            //진행 중
            $view_contest[$i][2] = "<span class=text-danger>$MSG_Running</span>" . " " . $row['start_time'] . "&nbsp;";
            $view_contest[$i][2] .= "<span class=text-danger>$MSG_LeftTime</span>" . " " . formatTimeLength($left) . "</span>";
        }

        $private = intval($row['private']);
        if ($private == 0)
            $view_contest[$i][4] = "<span class=text-primary>$MSG_Public</span>";
        else
            $view_contest[$i][5] = "<span class=text-danger>$MSG_Private</span>";

        $view_contest[$i][6] = $row['user_id'];

        $i++;
    }
}

/////////////////////////Template
/**
 * 인자에 맞는 템플릿 파일을 불러온다.
 * cid 인자가 있으면 대회 템플릿, 없으면 대회 목록 템플릿을 쓴다.
 */
if (isset($_GET['cid']))
    require("template/" . $OJ_TEMPLATE . "/contest.php");
else
    require("template/" . $OJ_TEMPLATE . "/contestset.php");
/////////////////////////Common foot
/**
 * 캐시 종료 파일이 있으면 불러온다.
 */
if (file_exists('./include/cache_end.php'))
    require_once('./include/cache_end.php');
?>
