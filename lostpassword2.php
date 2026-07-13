<?php
require_once('./include/db_info.inc.php');
require_once('./include/setlang.php');
require_once('./include/const.inc.php');
require_once('./include/my_func.inc.php');

$view_title = 'GSMOJ 비밀번호 재설정';
$lost_user_id = trim($_POST['user_id'] ?? '');
$lost_key = trim($_POST['lost_key'] ?? '');
$new_password = (string)($_POST['new_password'] ?? '');
$confirm_password = (string)($_POST['confirm_password'] ?? '');
$session_user_id = $_SESSION[$OJ_NAME . '_lost_user_id'] ?? '';
$session_key = $_SESSION[$OJ_NAME . '_lost_key'] ?? '';

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

if (!$valid_reset) {
    $mark = 0;
    $view_errors = '비밀번호 재설정 링크가 올바르지 않거나 이미 사용되었습니다.';
    require('template/' . $OJ_TEMPLATE . '/error.php');
    exit(0);
}

if (strlen($new_password) < 6) {
    $reset_error = '새 비밀번호는 6자 이상 입력해 주세요.';
    require('template/' . $OJ_TEMPLATE . '/lostpassword2.php');
    exit(0);
}

if (!hash_equals($new_password, $confirm_password)) {
    $reset_error = '새 비밀번호와 비밀번호 확인이 일치하지 않습니다.';
    require('template/' . $OJ_TEMPLATE . '/lostpassword2.php');
    exit(0);
}

if ($valid_reset) {
    pdo_query(
        "UPDATE `users` SET `password`=?, `activecode`='' WHERE `user_id`=? AND `activecode`=? AND `defunct`='N'",
        pwGen($new_password),
        $lost_user_id,
        $lost_key
    );
    unset($_SESSION[$OJ_NAME . '_lost_user_id'], $_SESSION[$OJ_NAME . '_lost_key']);
    $mark = 100;
    $view_errors = "비밀번호가 재설정되었습니다.<br>새 비밀번호로 로그인해 주세요.<br><a href='loginpage.php'>로그인하기</a>";
}

require('template/' . $OJ_TEMPLATE . '/error.php');
?>
