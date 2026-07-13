<?php
require_once('./include/db_info.inc.php');
require_once('./include/setlang.php');
require_once('./include/const.inc.php');
require_once('./include/my_func.inc.php');

$view_title = 'GSMOJ 비밀번호 재설정';
$lost_user_id = trim($_POST['user_id'] ?? '');
$lost_email = trim($_POST['email'] ?? '');
$vcode = trim($_POST['vcode'] ?? '');

if ($lost_user_id === '') {
    require('template/' . $OJ_TEMPLATE . '/lostpassword.php');
    exit(0);
}

if ($vcode === '' || $vcode != ($_SESSION[$OJ_NAME . '_vcode'] ?? null)) {
    echo "<script>alert('인증 코드가 틀렸습니다!');history.go(-1);</script>";
    exit(0);
}

$users = pdo_query("SELECT `email` FROM `users` WHERE `user_id`=? AND `defunct`='N'", $lost_user_id);
$registered_email = $users ? $users[0]['email'] : '';

if ($registered_email === '' || $registered_email !== $lost_email || strpos($lost_email, '@') === false) {
    $error_msg = '사용자 아이디와 이메일이 일치하지 않습니다.';
    require('template/' . $OJ_TEMPLATE . '/lostpassword.php');
    exit(0);
}

$_SESSION[$OJ_NAME . '_lost_user_id'] = $lost_user_id;
$_SESSION[$OJ_NAME . '_lost_key'] = getToken(16);

require_once('./include/email.class.php');
$reset_key = $_SESSION[$OJ_NAME . '_lost_key'];
$subject = '=?UTF-8?B?' . base64_encode('[광주소프트웨어마이스터고] GSMOJ 비밀번호 재설정') . '?=';
$mail_body = gsmoj_password_reset_mail($lost_user_id, $reset_key);
$mail_error = null;
if (!email($registered_email, $subject, strip_tags($mail_body), $mail_body, $mail_error)) {
    unset($_SESSION[$OJ_NAME . '_lost_user_id'], $_SESSION[$OJ_NAME . '_lost_key']);
    $error_msg = '비밀번호 재설정 메일을 발송하지 못했습니다. 관리자에게 문의해 주세요.';
    require('template/' . $OJ_TEMPLATE . '/lostpassword.php');
    exit(0);
}

require('template/' . $OJ_TEMPLATE . '/lostpassword2.php');
?>
