<?php
require_once('./include/db_info.inc.php');
require_once('./include/const.inc.php');
require_once('./include/my_func.inc.php');
require_once('./include/memcache.php');
require_once('./include/setlang.php');
require_once('./include/curl.php');
$view_title = $MSG_SUBMIT;
if (!isset($_SESSION[$OJ_NAME . '_' . 'user_id'])) {
    if (isset($OJ_GUEST) && $OJ_GUEST) {
        $_SESSION[$OJ_NAME . '_' . 'user_id'] = "Guest";
    } else {
        $view_errors = "<a href=loginpage.php>$MSG_Login</a>";
        require("template/" . $OJ_TEMPLATE . "/error.php");
        exit(0);
    }
}
$langmask = $OJ_LANGMASK;
$problem_id = 1000;
if (!empty($_SERVER['HTTP_REFERER'])) {
    $queryString = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY);
    if ($queryString) {
        // 쿼리 문자열을 연관 배열로 변환한다.
        parse_str($queryString, $queryParams);
        // cid 인자가 있는지 확인한다.
        if (isset($queryParams['cid'])) {
            // 정수로 변환한 cid를 사용한다.
            $sql = "select contest_type,end_time from contest where contest_id=?";
            $contest_type = pdo_query($sql, intval($queryParams['cid']));
            if (!empty($contest_type)) {
                $end_time = $contest_type[0][1];
                $contest_type = $contest_type[0][0];
            }
            //echo "[ $contest_type $end_time ]";
            if (($contest_type & 64) && strtotime($end_time) < time()) {
                $view_errors = "$MSG_FORBIDDEN.$MSG_UPSOLVING";
                require("template/$OJ_TEMPLATE/error.php");
                exit();
            }
        }
    }
}
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // 수행평가 전용 문제는 회차 밖에서 예제가 보이지 않게 한다.
    $gs_dedicated = pdo_query("SELECT 1 FROM problem WHERE problem_id=? AND defunct='A'", $id);
    if (!empty($gs_dedicated) && !(isset($_SESSION[$OJ_NAME . '_administrator']) || isset($_SESSION[$OJ_NAME . '_contest_creator']) || isset($_SESSION[$OJ_NAME . '_problem_editor']) || isset($_SESSION[$OJ_NAME . '_p' . $id]))) {
        $view_errors = "<h2>$MSG_NO_PROBLEM</h2>";
        require("template/" . $OJ_TEMPLATE . "/error.php");
        exit(0);
    }
    // 연습 경로는 problem.php의 id 경로와 같은 조건으로 연다. 비활성 문제와 진행 중이거나 시작 전인 대회·수행평가, 비공개 대회에 쓰인 문제는 열지 않는다.
    // 관리자·문제 검수자·대회 출제자·문제 편집자·문제 작성자(pNNNN)는 제한하지 않는다.
    if ($id > 0 && !(isset($_SESSION[$OJ_NAME . '_' . 'administrator']) || isset($_SESSION[$OJ_NAME . '_' . 'problem_verifiter']) || isset($_SESSION[$OJ_NAME . '_' . 'contest_creator']) || isset($_SESSION[$OJ_NAME . '_' . 'problem_editor']) || isset($_SESSION[$OJ_NAME . '_' . 'p' . $id]))) {
        $gs_now = date('Y-m-d H:i', time());
        if (isset($OJ_FREE_PRACTICE) && $OJ_FREE_PRACTICE) {
            $gs_practice = pdo_query("SELECT `problem_id` FROM `problem` WHERE `defunct`='N' AND `problem_id`=?", $id);
        } else {
            $sql = "SELECT `problem_id` FROM `problem` WHERE `problem_id`=? AND `defunct`='N' AND NOT EXISTS (
            SELECT 1 FROM `contest_problem` cp
            INNER JOIN `contest` c ON cp.contest_id=c.contest_id
            WHERE cp.problem_id=? AND (c.end_time>? AND c.defunct='N' OR c.private='1' AND NOT EXISTS (SELECT 1 FROM `assessment_session` gs INNER JOIN `assessment` ga ON ga.assessment_id=gs.assessment_id WHERE gs.contest_id=c.contest_id AND ga.published_at IS NOT NULL))
        )";
            $gs_practice = pdo_query($sql, $id, $id, $gs_now);
        }
        if (empty($gs_practice)) {
            $view_errors = "<h2>$MSG_NO_SUCH_PROBLEM</h2>";
            require("template/" . $OJ_TEMPLATE . "/error.php");
            exit(0);
        }
    }
} else if (isset($_GET['cid']) && isset($_GET['pid'])) {
    $cid = intval($_GET['cid']);
    $pid = intval($_GET['pid']);
    require_once("contest-check.php");
    $psql = "SELECT problem_id FROM contest_problem WHERE contest_id=? AND num=?";
    $data = pdo_query($psql, $cid, $pid);
    $row = $data[0];
    $problem_id = $row[0];
} else {
    $view_errors = "<h2>$MSG_NO_PROBLEM</h2>";
    require("template/" . $OJ_TEMPLATE . "/error.php");
    exit(0);
}
$view_src = "";
$lastlang = 1;
if (isset($_GET['sid'])) {
    $sid = intval($_GET['sid']);
    $sql = "SELECT * FROM `solution` WHERE `solution_id`=?";
    $result = pdo_query($sql, $sid);
    $row = $result[0];
    $cid = intval($row['contest_id']);
    $sproblem_id = intval($row['problem_id']);
    $contest_id = $cid;
    if ($row && $row['user_id'] == $_SESSION[$OJ_NAME . '_' . 'user_id'])
        $ok = true;

    $need_check_using = true;
    if ($contest_id > 0) {
        $sql = "select start_time,end_time from contest where contest_id=?";
        $result = pdo_query($sql, $contest_id);
        if ($result) {
            $row = $result[0];
            $start_time = strtotime($row['start_time']);
            $end_time = strtotime($row['end_time']);
            $now = time();
            if ($end_time < $now) { // 이미 끝난 대회의 제출이면 진행 중인 대회에서 쓰이는지 확인한다.
                $need_check_using = true;
            } else {            // 진행 중인 대회의 제출이면 볼 수 있다.
                $need_check_using = false;
            }
        }
    } else { // 대회 밖 제출이면 진행 중인 대회에서 쓰이는지 확인한다.
        //	echo $now.'+'.$end_time;
        if (isset($_SESSION[$OJ_NAME . '_' . 'source_browser']))
            $need_check_using = false;
        else
            $need_check_using = true;
    }
    // 사용 중인지 확인한다.
    //echo $now.'*'.$end_time;
    $now = date('Y-m-d H:i', time());
    $sql = "select contest_id from contest where contest_id in (select contest_id from contest_problem where problem_id=?) 
									and start_time < '$now' and end_time > '$now' ";
    if ($need_check_using) {
        //echo $sql;
        $result = pdo_query($sql, $sproblem_id);
        if (count($result) > 0 && !isset($_SESSION[$OJ_NAME . '_' . 'source_browser'])) {
            $view_errors = "<center>";
            $view_errors .= "<h3>$MSG_CONTEST_ID : " . $result[0][0] . "</h3>";
            $view_errors .= "<p> $MSG_SOURCE_NOT_ALLOWED_FOR_EXAM </p>";
            $view_errors .= "<br>";
            $view_errors .= "</center>";
            $view_errors .= "<br><br>";
            require("template/" . $OJ_TEMPLATE . "/error.php");
            exit(0);
        }

    }
    if (isset($_SESSION[$OJ_NAME . '_' . 'source_browser'])) {
        $ok = true;
    } else {


        if (isset($OJ_EXAM_CONTEST_ID)) {
            if ($cid < $OJ_EXAM_CONTEST_ID && !isset($_SESSION[$OJ_NAME . '_' . 'source_browser'])) {

                $view_errors = "<center>";
                $view_errors .= "<h3>$MSG_CONTEST_ID : " . $OJ_EXAM_CONTEST_ID . "+ </h3>";
                $view_errors .= "<p> $MSG_SOURCE_NOT_ALLOWED_FOR_EXAM </p>";
                $view_errors .= "<br>";
                $view_errors .= "</center>";
                $view_errors .= "<br><br>";
                require("template/" . $OJ_TEMPLATE . "/error.php");
                exit(0);
            }
        }
    }

    if ($ok == true) {
        $sql = "SELECT `source` FROM `source_code_user` WHERE `solution_id`=?";
        $result = pdo_query($sql, $sid);

        $row = $result[0];

        if ($row)
            $view_src = $row['source'];

        if (isset($cid) && $cid > 0) {

            $sql = "SELECT langmask FROM contest WHERE contest_id=?";

            $result = pdo_query($sql, $cid);
            $row = $result[0];

            if (count($row) > 0) {
                $_GET['langmask'] = $row['langmask'];
                $langmask = $row['langmask'];
            }
        }
        $sql = "select language from solution where solution_id=?";
        $result = pdo_query($sql, $sid);
        $row = $result[0];
        if ($row && str_contains($_SERVER['HTTP_REFERER'], "status.php"))
            $lastlang = intval($row['language']);   //Click on Edit from status.php
        else
            $lastlang = intval($_COOKIE['lastlang']);  // Switch Language from submitpage.php
    }
}

if (isset($id))
    $problem_id = $id;

$view_sample_input = "1 2";
$view_sample_output = "3";
$spj = 0;
$sample_sql = "SELECT sample_input,sample_output,problem_id,spj,remote_oj FROM problem WHERE problem_id = ?";
$remote_oj = "";
if (isset($sample_sql)) {
    //echo $sample_sql;
    if (isset($_GET['id'])) {
        $result = pdo_query($sample_sql, $id);
    } else {
        $result = pdo_query($sample_sql, $problem_id);
    }
    if ($result == false) {
        $view_errors = "<h2>$MSG_NO_PROBLEM</h2>";
        require("template/" . $OJ_TEMPLATE . "/error.php");
        exit(0);
    }
    $row = $result[0];
    $view_sample_input = $row['sample_input'];
    $view_sample_output = $row['sample_output'];
    $problem_id = $row['problem_id'];
    $spj = $row['spj'];
    $remote_oj = $row['remote_oj'];
    if ($spj > 1) $OJ_ACE_EDITOR = false;
}

$solution_file = "$OJ_DATA/$problem_id/solution.name";
if (file_exists($solution_file)) {
    $solution_name = file_get_contents($solution_file);
} else {
    $solution_name = false;
}


if (!$view_src) {
    if (isset($_COOKIE['lastlang']) && $_COOKIE['lastlang'] != "undefined") {
        $lastlang = intval($_COOKIE['lastlang']);
    } else {
        $sql = "SELECT language FROM solution WHERE user_id=? ORDER BY solution_id DESC LIMIT 1";
        $result = pdo_query($sql, $_SESSION[$OJ_NAME . '_' . 'user_id']);

        if (count($result) > 0) {
            $lastlang = $result[0][0];
        } else {
            $lastlang = 1;   // 기본 언어: 0=C  1=C++ 3=Java  6=Python
        }
        //echo "last=$lastlang";
    }
    $template_file = "$OJ_DATA/$problem_id/template." . $language_ext[$lastlang];

    if (file_exists($template_file)) {
        $view_src = file_get_contents($template_file);
    } else if ($spj > 1 && file_exists("$OJ_DATA/$problem_id/template.c")) {
        $view_src = file_get_contents("$OJ_DATA/$problem_id/template.c");
    } else if ($spj == 2 && file_exists("$OJ_DATA/$problem_id/test.in")) {
        $total = file_get_contents("$OJ_DATA/$problem_id/test.in");
        $total = intval($total);
        $view_src = "";
        for ($i = 1; $i <= $total; $i++) {
            $view_src .= $i . "\n";
        }
    }
	if(($view_src=="") && isset($_SESSION[$OJ_NAME.'_administrator']) && file_exists("$OJ_DATA/$problem_id/Main.c")){
			// 관리자에게는 C 표준 풀이가 있으면 자동으로 불러온다.
			$view_src = file_get_contents( "$OJ_DATA/$problem_id/Main.c" );
		    $lastlang = intval($_COOKIE['lastlang']);
	}else if(($view_src=="") && isset($_SESSION[$OJ_NAME.'_administrator']) && file_exists("$OJ_DATA/$problem_id/Main.cc")){
			// 관리자에게는 C++ 표준 풀이가 있으면 자동으로 불러온다.
			$view_src = file_get_contents( "$OJ_DATA/$problem_id/Main.cc" );
		    $lastlang = intval($_COOKIE['lastlang']);
	}

}

$sql = "SELECT count(1) FROM `solution` WHERE result<4";
$result = pdo_query($sql);

$row = $result[0];

if ($row[0] > 10) {
    $OJ_VCODE = true;
}

/////////////////////////Template
require("template/" . $OJ_TEMPLATE . "/submitpage.php");
/////////////////////////Common foot
?>
