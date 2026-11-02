<?php
require_once("include/curl.php");
$unixnow = time();
$cid = abs(intval($_GET['cid']));
if (isset($_SESSION[$OJ_NAME . "_user_id"]))
    $user_id = $_SESSION[$OJ_NAME . "_user_id"];
else
    $user_id = "";
if ($cid < 0) $cid = -$cid;
$view_cid = $cid;
//print $cid;

/**
 * 대회가 유효한지 확인하고 사용자 권한을 검사한다.
 * 대회 기본 정보를 가져와 상태, 권한, 시간 조건을 확인한다.
 * 대회 비밀번호, IP 서브넷 제한, 비공개 대회 권한을 처리한다.
 * 개인 시간제한 대회를 지원해 사용자의 풀이 시간을 제한한다.
 * 확인 결과에 따라 대회 접근 허용 여부를 정한다.
 */

//check contest valid
$sql = "SELECT * FROM `contest` WHERE `contest_id`=?";
$result = mysql_query_cache($sql, $cid);
$rows_cnt = count($result);
$is_assessment = false;

if ($rows_cnt > 0) {
    // 수행평가(contest_type 9번 비트)는 화면 문구와 부정행위 감지 기록을 대회와 다르게 처리한다.
    $is_assessment = (intval($result[0]['contest_type']) & 512) != 0;
    $row = $result[0];
    $start_time = strtotime($row['start_time']);
    $end_time = strtotime($row['end_time']);
    $view_description = $row['description'];
    $view_title = $row['title'];
    $view_start_time = $row['start_time'];
    $view_end_time = $row['end_time'];
    $subnet = $row['subnet'];
}
// 볼 권한이 없는 수행평가는 아래의 제목·안내문·시작 시간 화면을 거치지 않고 여기서 막는다.
if ($is_assessment) require_once("include/assessment_access_guard.php");
$contest_ok = true;
$password = "";

if (isset($_POST['password']))
    $password = $_POST['password'];

// IP가 대회에서 허용한 서브넷 범위 안에 있는지 확인한다.
if (!in_subnet_of_contest($ip, $cid)) {
    $contest_ok = false;
    $view_description = "Not in $MSG_SUBNET $subnet";
}

// 대회가 없거나 닫혔는지 확인한다(관리자가 아니면 닫힌 대회에 접근할 수 없다).
if ($rows_cnt == 0 || ($row['defunct'] == 'Y' && !isset($_SESSION[$OJ_NAME . '_administrator']))) {
    $view_title = $MSG_CONTEST . "가 닫혔습니다!";
    $contest_ok = false;
} else {

    $view_private = $row['private'];
    $view_contest_creator = $row['user_id'];
    if ($password != "" && $password == $row['password'])
        $_SESSION[$OJ_NAME . '_' . 'c' . $cid] = true;

    // 비공개 대회 권한을 확인한다.
    if ($row['private'] && !isset($_SESSION[$OJ_NAME . '_' . 'c' . $cid])) {

        $sql = "SELECT count(*) FROM `privilege` WHERE `user_id`=? AND `rightstr`=?";
        $result = mysql_query_cache($sql, $user_id, "c$cid");
        $row = $result[0];
        $ccnt = intval($row[0]);
        if ($ccnt > 0) {
            // 권한이 있어도 위에서 실패한 서브넷 제한을 되돌리지 않도록 $contest_ok는 바꾸지 않는다.
            $_SESSION[$OJ_NAME . '_' . 'c' . $cid] = true;
        } else {
            $contest_ok = false;
        }
    }
//              if($row['defunct']=='Y')  //defunct problem not in contest/exam list
//                      $contest_ok = false;

    // 관리자와 대회 출제자는 특별 권한이 있다.
    if (isset($_SESSION[$OJ_NAME . '_' . 'administrator']) || isset($_SESSION[$OJ_NAME . '_' . 'contest_creator']))
        $contest_ok = true;

    // 대회가 끝났는지 판단한다.
    if ($unixnow > $end_time) $contest_is_over = true;    // 끝난 대회는 연습 방식으로 제출한다.
    else $contest_is_over = false;

    // 대회가 시작 전이면 오류 페이지를 보여 준다.
    if (!isset($_SESSION[$OJ_NAME . '_' . 'administrator']) && $unixnow < $start_time) {
        $view_errors = "<center>";
        $view_errors .= "<h3>$MSG_CONTEST_ID : $view_cid - $view_title</h3>";
        $view_errors .= "<p>$view_description</p>";
        $view_errors .= "<br>";
        $view_errors .= "<span class=text-success>$MSG_TIME_WARNING</span>";
        $view_errors .= "</center>";
        $view_errors .= "<br><br>";

        require("template/" . $OJ_TEMPLATE . "/error.php");
        exit(0);
    }
}

// 개인 시간제한 대회 기능을 처리한다.
if (!isset($OJ_CONTEST_LIMIT_KEYWORD)) $OJ_CONTEST_LIMIT_KEYWORD = "限时";
if ($contest_ok && str_contains($view_description, $OJ_CONTEST_LIMIT_KEYWORD) && isset($_SESSION[$OJ_NAME . "_user_id"])) {
    //echo "<!-- 개인 시간제한 대회  -->";
    $contest_limit_minutes = intval(getPartByMark($view_description, $OJ_CONTEST_LIMIT_KEYWORD, "分钟"));  //대회 설명에 "限时xx分钟"이라고 쓰면 개인 풀이 시간을 정할 수 있다.
    if ($contest_limit_minutes == 0) $contest_limit_minutes = 120;
    //echo "<!-- $contest_limit_minutes mins -->";
    $user_id = $_SESSION[$OJ_NAME . "_user_id"];
    $first_login_contest = mysql_query_cache("select time from loginlog where user_id=? and password=?", $user_id, "c" . $cid);
    if (empty($first_login_contest)) {
        //echo "<!-- 첫 방문  -->";
        $ip = ($_SERVER['REMOTE_ADDR']);
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty(trim($_SERVER['HTTP_X_FORWARDED_FOR']))) {
            $REMOTE_ADDR = $_SERVER['HTTP_X_FORWARDED_FOR'];
            $tmp_ip = explode(',', $REMOTE_ADDR);
            $ip = (htmlentities($tmp_ip[0], ENT_QUOTES, "UTF-8"));
        } else if (isset($_SERVER['HTTP_X_REAL_IP']) && !empty(trim($_SERVER['HTTP_X_REAL_IP']))) {
            $REMOTE_ADDR = $_SERVER['HTTP_X_REAL_IP'];
            $tmp_ip = explode(',', $REMOTE_ADDR);
            $ip = (htmlentities($tmp_ip[0], ENT_QUOTES, "UTF-8"));
        }
        $sql = "INSERT INTO `loginlog`(user_id,password,ip,time) VALUES(?,?,?,NOW())";
        mysql_query_cache($sql, $user_id, "c" . $cid, $ip);
        $first_login_contest = time();
    } else {
        $first_login_contest = strtotime($first_login_contest[0][0]);
    }
    $unixnow = time();
    //echo "<!-- $unixnow - $first_login_contest = ".($unixnow-$first_login_contest)." -->";
    if ($unixnow - $first_login_contest >= $contest_limit_minutes * 60 && !isset($_SESSION[$OJ_NAME . "_m" . $cid]) && !isset($_SESSION[$OJ_NAME . "_administrator"])) {
        $contest_ok = false;

        $view_errors = "<center>";
        $view_errors .= "<h3>$MSG_CONTEST_ID : $view_cid - $view_title</h3>";
        $view_errors .= "<p>$view_description</p>";
        require("template/" . $OJ_TEMPLATE . "/error.php");
        exit(0);
    } else {
        $time_left = $contest_limit_minutes * 60 - ($unixnow - $first_login_contest);
        if ($time_left > 0)
            $view_description .= "<br><span id='time_left'>$MSG_LeftTime :" . intval($time_left / 60) . " $MSG_MINUTES" . ($time_left % 60) . $MSG_SECONDS . "<span>";
    }

};

// 대회 확인에 실패하면 오류 페이지와 비밀번호 입력 폼을 보여 준다.
if (!$contest_ok) {
    $view_errors = "<center>";
    $view_errors .= "<h3>$MSG_CONTEST_ID : $view_cid - $view_title</h3>";
    $view_errors .= "<p>$view_description</p>";
    $view_errors .= "<span class=text-danger>$MSG_PRIVATE_WARNING</span>";

    $view_errors .= "<br><br>";

    $view_errors .= "<div class='btn-group'>";
    $view_errors .= "<a href=contestrank.php?cid=$view_cid class='btn btn-primary'>$MSG_STANDING</a>";
    $view_errors .= "<a href=contestrank-oi.php?cid=$view_cid class='btn btn-primary'>OI$MSG_STANDING</a>";
    $view_errors .= "<a href=conteststatistics.php?cid=$view_cid class='btn btn-primary'>$MSG_STATISTICS</a>";
    $view_errors .= "</div>";

    $view_errors .= "<br><br>";
    $view_errors .= "<table align=center width=80%>";
    $view_errors .= "<tr align='center'>";
    $view_errors .= "<td>";
    $view_errors .= "<form class=form-inline method=post action=contest.php?cid=$cid>";
    $view_errors .= "<input class=input-mini type=password name=password value='' placeholder=$MSG_CONTEST-$MSG_PASSWORD>";
    $view_errors .= "<button class='form-control'>$MSG_SUBMIT</button>";
    $view_errors .= "</form>";
    $view_errors .= "</td>";
    $view_errors .= "</tr>";
    $view_errors .= "</table>";
    $view_errors .= "<br>";

    require("template/" . $OJ_TEMPLATE . "/error.php");
    exit(0);
}

