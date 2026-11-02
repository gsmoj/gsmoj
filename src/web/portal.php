<?php
ini_set("display_errors", "Off");  // 디버깅할 때 On으로 바꾸면 문제가 생겼을 때 PHP가 페이지에 오류를 출력한다.
require_once("include/db_info.inc.php"); // 기본 설정과 데이터베이스 함수를 불러온다.
require_once('include/const.inc.php');   // 필요한 상수를 불러온다.
require_once('include/my_func.inc.php'); // 공통 함수를 불러온다.
require_once('include/curl.php'); // 크롤러 관련 함수를 불러온다.
$now = date('Y-m-d H:i', time());
if (isset($_SESSION[$OJ_NAME . '_user_id'])) {  //로그인한 사용자
    $user_id = $_SESSION[$OJ_NAME . '_user_id'];
    $show_title = $MSG_TODO . " - " . $OJ_NAME;
    $sub_arr = array(); //제출한 문제 번호
    $acc_arr = array(); //정답을 받은 문제 번호
    if (isset($_SESSION[$OJ_NAME . '_' . 'user_id'])) {
        $sql = "SELECT problem_id, MIN(result) AS result FROM solution WHERE user_id=? and result>=4 GROUP BY problem_id ";
        $result = pdo_query($sql, $_SESSION[$OJ_NAME . '_' . 'user_id']);
        foreach ($result as $row) {
            $sub_arr[$row['problem_id']] = true;
            if ($row['result'] == 4) $acc_arr[$row['problem_id']] = true;
        }
    }
    $noip_problems = array_merge(...mysql_query_cache("select problem_id from contest c left join contest_problem cp on start_time<'$now' and end_time>'$now' and (c.title like ? or (c.contest_type & 20) >0) and c.contest_id=cp.contest_id", "%$OJ_NOIP_KEYWORD%"));
    $noip_problems = array_unique($noip_problems);
} else {    //로그인하지 않은 사용자에게 로그인을 안내한다.
    $view_errors = "<a href=loginpage.php>$MSG_Login</a>";
    require("template/" . $OJ_TEMPLATE . "/error.php");
    exit(0);
}
$sql = "select * from problem p inner join (
 			select distinct problem_id,contest_id from solution where result!=4 and user_id=? and problem_id not in 
    				(select distinct problem_id from solution where result=4 and user_id=?)
			) f on p.problem_id=f.problem_id ";
$result = pdo_query($sql, $user_id, $user_id);      // 풀었지만 정답을 받지 못한 문제를 찾는다.
$cnt = 0;
$view_problemset = array();  //조회 결과를 화면에 넘기는 2차원 배열
$i = 0;
foreach ($result as $row) {
    if (in_array($row['problem_id'], $noip_problems)) continue;
    $view_problemset[$i] = array();    //$i번째 행 배열 초기화
    if (isset($sub_arr[$row['problem_id']])) {
        if (isset($acc_arr[$row['problem_id']]))
            $view_problemset[$i][0] = "<div class='label label-success'>Y</div>";
        else
            $view_problemset[$i][0] = "<div class='label label-danger'>N</div>";
    } else {
        $view_problemset[$i][0] = "<div class=none> </div>";
    }
    $category = array();
    $cate = explode(" ", $row['source']);  //분류 태그
    foreach ($cate as $cat) {
        $cat = trim($cat);
        if (mb_ereg("^http", $cat)) {
            $cat = get_domain($cat);   // URL 태그는 도메인으로 표시한다. get_domain은 curl.php에 있다.
        }
        array_push($category, trim($cat));
    }
    $view_problemset[$i][1] = "<div fd='problem_id' class='center'>" . $row['problem_id'] . "</div>";
    $view_problemset[$i][2] = "<div class='left'><a href='problem.php?id=" . $row['problem_id'] . "'>" . $row['title'] . "</a></div>";;
    $view_problemset[$i][3] = "<div pid='" . $row['problem_id'] . "' fd='source' class='center'>";

    foreach ($category as $cat) {
        if (trim($cat) == "" || trim($cat) == "&nbsp")
            continue;
        $hash_num = hexdec(substr(md5($cat), 0, 7));
        $label_theme = $color_theme[$hash_num % count($color_theme)];   //$color_theme은 const.inc.php에 있다.
        if ($label_theme == "")
            $label_theme = "default";
        $view_problemset[$i][3] .= "<a title='" . htmlentities($cat, ENT_QUOTES, 'UTF-8') . "' class='label label-$label_theme' style='display: inline-block;' href='problemset.php?search=" . htmlentities(urlencode($cat), ENT_QUOTES, 'UTF-8') . "'>" . mb_substr($cat, 0, 10, 'utf8') . "</a>&nbsp;";
    }

    $view_problemset[$i][3] .= "</div >";
    $view_problemset[$i][4] = "<div class='center'><a href='status.php?problem_id=" . $row['problem_id'] . "&jresult=4'>" . $row['accepted'] . "</a></div>";
    $view_problemset[$i][5] = "<div class='center'><a href='status.php?problem_id=" . $row['problem_id'] . "'>" . $row['submit'] . "</a></div>";
    $i++;
}
$mycontests = $wheremy = "";
$gs_visible_args = array();

if (isset($_SESSION[$OJ_NAME . '_user_id'])) {
    $sql = "select distinct contest_id from solution where contest_id>0 and user_id=?";
    $result = pdo_query($sql, $_SESSION[$OJ_NAME . '_user_id']);  //제출 기록이 있는 대회
    foreach ($result as $row) {
        $mycontests .= "," . $row[0];
    }
    $len = mb_strlen($OJ_NAME . '_');
    $user_id = $_SESSION[$OJ_NAME . '_' . 'user_id'];
    if (!empty($user_id)) {
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

    foreach ($_SESSION as $key => $value) {   //권한이 있는 대회
        if ((mb_substr($key, $len, 1) == 'm' || mb_substr($key, $len, 1) == 'c') && intval(mb_substr($key, $len + 1)) > 0) {
            //echo substr($key,1)."<br>";
            $mycontests .= "," . intval(mb_substr($key, $len + 1));
        }
    }

    //echo "=====>$mycontests<====";
    if (!empty($mycontests)) {
        $mycontests = substr($mycontests, 1);
        $wheremy = " and  contest_id in ($mycontests) ";
    }
    $wheremy .= " and start_time < now() and end_time > now() ";   //진행 중인 대회
    // 수행평가는 들어갈 수 있는 회차만 보여 준다.
    if (!isset($_SESSION[$OJ_NAME . '_administrator'])) {
        list($gs_visible_cond, $gs_visible_args) = assessment_visible_cond($_SESSION[$OJ_NAME . '_user_id']);
        $wheremy .= " and ((contest_type & 512)=0 or $gs_visible_cond) ";
    }
}

$sql = "SELECT *  FROM contest WHERE contest.defunct='N' $wheremy  ORDER BY contest_id DESC";
//echo htmlentities($sql);
$result = call_user_func_array("pdo_query", array_merge(array($sql), $gs_visible_args));
$view_contest = array();
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
    $view_contest[$i][2] = $row['start_time'] . "&nbsp;";
    $view_contest[$i][2] .= "<span class=text-danger>$MSG_LeftTime</span>" . " " . intval($left / 60) . "$MSG_MINUTES</span>";
    $private = intval($row['private']);
    if ($private == 0)
        $view_contest[$i][4] = "<span class=text-primary>$MSG_Public</span>";
    else
        $view_contest[$i][5] = "<span class=text-danger>$MSG_Private</span>";
    $view_contest[$i][6] = $row['user_id'];
    $i++;
}
require("template/" . $OJ_TEMPLATE . "/" . basename(__FILE__));

