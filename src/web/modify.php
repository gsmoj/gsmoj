<?php
//$cache_time=10;
$OJ_CACHE_SHARE = false;
//require_once('./include/cache_start.php');
require_once('./include/db_info.inc.php');
require_once('./include/setlang.php');
$view_title = $MSG_MODIFY_USERINFO;
//require_once("./include/check_post_key.php");
require_once("./include/my_func.inc.php");
if (
    (isset($OJ_EXAM_CONTEST_ID) && $OJ_EXAM_CONTEST_ID > 0) ||
    (isset($OJ_ON_SITE_CONTEST_ID) && $OJ_ON_SITE_CONTEST_ID > 0)
) {
    $view_errors = $MSG_MODIFY_NOT_ALLOWED_FOR_EXAM;
    require("template/" . $OJ_TEMPLATE . "/error.php");
    exit ();
}
if (!isset($_SESSION[$OJ_NAME . '_' . 'user_id'])) {
    $view_errors = "<a href=./loginpage.php>$MSG_Login</a>";
    require("template/" . $OJ_TEMPLATE . "/error.php");
    exit(0);
}
$err_str = "";
$err_cnt = 0;
$len;
$user_id = $_SESSION[$OJ_NAME . '_' . 'user_id'];
$email = isset($_POST['email']) ? trim($_POST['email']) : "";
$school = isset($_POST['school']) ? trim($_POST['school']) : "";
$nick = isset($_POST['nick']) ? trim($_POST['nick']) : "";
$opassword = isset($_POST['opassword']) ? $_POST['opassword'] : "";
$npassword = isset($_POST['npassword']) ? $_POST['npassword'] : "";
$rptpassword = isset($_POST['rptpassword']) ? $_POST['rptpassword'] : "";
$len = strlen($nick);
if ($len > 100) {
    $err_str = $err_str . "$MSG_NICK: $MSG_TOO_LONG ";
    $err_cnt++;
} else if ($len == 0) $nick = $user_id;
$password = $opassword;
$sql = "SELECT `user_id`,`password`,`email` FROM `users` WHERE `user_id`=?";
$result = pdo_query($sql, $user_id);
$row = (is_array($result) && count($result) > 0) ? $result[0] : null;
// DataGSM으로 자동 가입한 계정은 비밀번호가 무작위 값이라 기존 비밀번호를 알 수 없다.
// DataGSM에 연결된 계정이 비밀번호를 바꾸지 않을 때는 기존 비밀번호 대신 폼의 postkey(CSRF 토큰)로 본인 요청을 확인한다.
// 새 비밀번호를 정하는 것은 계정 탈취 위험이 커서 여전히 기존 비밀번호가 필요하다.
$datagsm_linked = false;
if ($row) {
    $linked = pdo_query("SELECT 1 FROM `datagsm_user` WHERE `user_id`=?", $user_id);
    $datagsm_linked = is_array($linked) && count($linked) > 0;
}
$skip_password_check = $datagsm_linked && $opassword === "" && $npassword === "";
if ($skip_password_check) {
    require_once("./include/check_post_key.php");
    $rows_cnt = 1;
} else if ($row && pwCheck($password, $row['password'])) $rows_cnt = 1;
else $rows_cnt = 0;

if ($rows_cnt == 0) {
    $err_str = $err_str . "$MSG_OLD $MSG_PASSWORD: $MSG_WRONG ";
    $err_cnt++;
}
$len = strlen($npassword);
if ($len > 0) {
    if (too_simple($npassword)) {
        $err_cnt++;
        $err_str = $err_str . "$MSG_PASSWORD $MSG_TOO_SIMPLE !\\n";
    } else if (strcmp($npassword, $rptpassword) != 0) {
        $err_str = $err_str . "$MSG_REPEAT_PASSWORD $MSG_DIFFERENT !";
        $err_cnt++;
    }
}
$len = strlen($school);
if ($len > 100) {
    $err_str = $err_str . "$MSG_SCHOOL: $MSG_TOO_LONG ";
    $err_cnt++;
}
// 이메일은 DataGSM 계정 연동 기준이므로 사용자가 직접 바꿀 수 없다. 저장된 값을 그대로 유지한다.
$stored_email = $row ? $row['email'] : '';
if (isset($_POST['email']) && $email !== $stored_email) {
    $err_str = $err_str . "이메일은 바꿀 수 없습니다. 변경이 필요하면 관리자에게 문의하세요.\\n";
    $err_cnt++;
}
$email = $stored_email;

if (has_bad_words($user_id)) {
    $err_str = $err_str . $MSG_USER_ID . " $MSG_TOO_BAD!\\n";
    $err_cnt++;
}
if (has_bad_words($school)) {
    $err_str = $err_str . $MSG_SCHOOL . " $MSG_TOO_BAD!\\n";
    $err_cnt++;
}
if (has_bad_words($nick)) {
    $err_str = $err_str . $MSG_NICK . " $MSG_TOO_BAD!\\n";
    $err_cnt++;
}
if ($err_cnt > 0) {
    print "<script language='javascript'>\n";
    echo "alert('";
    echo $err_str;
    print "');\n history.go(-1);\n</script>";
    exit(0);
}
if ($skip_password_check) $password = $row['password'];  // 비밀번호를 확인하지 않은 경우 저장된 값을 그대로 둔다.
else if (strlen($npassword) == 0) $password = pwGen($opassword);
else $password = pwGen($npassword);
$nick = htmlentities($nick, ENT_QUOTES, "UTF-8");
$school = (htmlentities($school, ENT_QUOTES, "UTF-8"));
if(isset($OJ_NICK_IMMUTABLE)&&$OJ_NICK_IMMUTABLE){
    $sql = "UPDATE `users` SET `password`=?,`school`=?,`email`=? WHERE `user_id`=?";
    pdo_query($sql, $password , $school, $email, $user_id);
}else{
    $sql = "UPDATE `users` SET `password`=?,`nick`=?,`school`=?,`email`=? WHERE `user_id`=?";
    pdo_query($sql, $password, $nick, $school, $email, $user_id);
    if ($nick != "") {
        $sql = "update solution set nick=? where user_id=?";
        pdo_query($sql, $nick, $user_id);
    }
}
header("Location: ./");
?>
