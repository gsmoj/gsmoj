<?php
require_once('./include/db_info.inc.php');
require_once('./include/setlang.php');
require_once('./include/const.inc.php');
require_once('./include/my_func.inc.php');

$view_title = 'GSMOJ 비밀번호 재설정';
$lost_user_id = trim($_POST['user_id'] ?? '');
$lost_key = trim($_POST['lost_key'] ?? '');
$vcode = trim($_POST['vcode'] ?? '');
$session_user_id = $_SESSION[$OJ_NAME . '_lost_user_id'] ?? '';
$session_key = $_SESSION[$OJ_NAME . '_lost_key'] ?? '';

if ($lost_user_id === $session_user_id && ($vcode === '' || $vcode != ($_SESSION[$OJ_NAME . '_vcode'] ?? null))) {
    echo "<script>alert('인증 코드가 올바르지 않습니다.');history.go(-1);</script>";
    exit(0);
}

$valid_reset = $lost_user_id !== ''
    && $lost_user_id === $session_user_id
    && $lost_key !== ''
    && hash_equals((string)$session_key, (string)$lost_key);

if ($valid_reset) {
    $matching_users = pdo_query(
        "SELECT `user_id` FROM `users` WHERE `user_id`=? AND `activecode`=? AND `defunct`='N'",
        $lost_user_id,
        $lost_key
    );
    $valid_reset = !empty($matching_users);
}

if ($valid_reset) {
    pdo_query(
        "UPDATE `users` SET `password`=?, `activecode`='' WHERE `user_id`=? AND `activecode`=? AND `defunct`='N'",
        pwGen($lost_key),
        $lost_user_id,
        $lost_key
    );
    unset($_SESSION[$OJ_NAME . '_lost_user_id'], $_SESSION[$OJ_NAME . '_lost_key']);
    $mark = 100;
    $view_errors = "비밀번호가 재설정되었습니다.<br>메일로 받은 인증 코드가 임시 비밀번호입니다.<br><a href='loginpage.php'>로그인하기</a>";
} else {
    $mark = 0;
    $view_errors = '비밀번호 재설정 정보가 올바르지 않거나 이미 사용되었습니다.';
}

require('template/' . $OJ_TEMPLATE . '/error.php');
?>
