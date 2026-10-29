<?php
require_once('./include/db_info.inc.php');
if (isset($OJ_REGISTER) && !$OJ_REGISTER) exit(0);

require_once('./include/my_func.inc.php');
require_once('./include/setlang.php');
require_once('./include/email.class.php');
if (isset($OJ_CSRF) && $OJ_CSRF) require_once('./include/csrf_check.php');

function registration_error(&$errors, $message)
{
    $errors[] = $message;
}

function stop_registration($errors)
{
    $message = implode('\\n', $errors);
    echo "<script>alert(" . json_encode($message, JSON_UNESCAPED_UNICODE) . ");history.go(-1);</script>";
    exit(0);
}

function registration_client_ip()
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwarded = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return htmlentities(trim($forwarded[0]), ENT_QUOTES, 'UTF-8');
    }

    return $_SERVER['REMOTE_ADDR'];
}

$errors = array();
$user_id = trim($_POST['user_id']);
$email = trim($_POST['email']);
$school = trim($_POST['school']);
$nick = trim($_POST['nick']);
$password_text = $_POST['password'];
$repeated_password = $_POST['rptpassword'];
$vcode = isset($_POST['vcode']) ? trim($_POST['vcode']) : '';

if (isset($OJ_VCODE) && $OJ_VCODE && ($vcode === '' || $vcode != $_SESSION[$OJ_NAME . '_vcode'])) {
    $_SESSION[$OJ_NAME . '_vcode'] = null;
    $_SESSION[$OJ_NAME . '_vfail'] = true;
    registration_error($errors, 'Verification Code Wrong!');
}
if ($OJ_LOGIN_MOD !== 'hustoj') registration_error($errors, "$MSG_SYSTEM $MSG_DISABLE $MSG_REGISTER");

$user_id_length = mb_strlen($user_id);
if ($user_id_length > 48) registration_error($errors, "$MSG_USER_ID $MSG_TOO_LONG !");
elseif ($user_id_length < 3) registration_error($errors, $MSG_WARNING_USER_ID_SHORT);
if (!is_valid_user_name($user_id)) registration_error($errors, "$MSG_USER_ID $MSG_WRONG !");

if (mb_strlen($nick) > 20) registration_error($errors, "$MSG_NICK $MSG_TOO_LONG !");
elseif ($nick === '') $nick = $user_id;

if (has_bad_words($user_id) || has_bad_words($school) || has_bad_words($nick)) {
    registration_error($errors, '비속어 또는 금지 단어가 포함되어 있습니다.');
}
if (strcmp($password_text, $repeated_password) !== 0) registration_error($errors, $MSG_WARNING_REPEAT_PASSWORD_DIFF . '!');
if (strlen($password_text) < 6) registration_error($errors, $MSG_WARNING_PASSWORD_SHORT);
if ($errors) stop_registration($errors);

$existing_user = pdo_query('SELECT `user_id` FROM `users` WHERE `user_id`=?', $user_id);
if ($existing_user) stop_registration(array($MSG_USER_ID . ' Existed!'));

$nick = htmlentities($nick, ENT_QUOTES, 'UTF-8');
$school = htmlentities($school, ENT_QUOTES, 'UTF-8');
$email = htmlentities($email, ENT_QUOTES, 'UTF-8');
$ip = registration_client_ip();

if (isset($OJ_REG_SPEED) && $OJ_REG_SPEED > 0) {
    $recent_registrations = pdo_query(
        'SELECT COUNT(*) FROM `users` WHERE (`ip`=? OR `email`=?) AND `reg_time`>DATE_SUB(NOW(), INTERVAL 1 HOUR)',
        $ip,
        $email
    );
    if (intval($recent_registrations[0][0]) > $OJ_REG_SPEED) stop_registration(array($MSG_IP_EMAIL_REG_OVERLIMIT));
}

$needs_admin_confirmation = isset($OJ_REG_NEED_CONFIRM) && $OJ_REG_NEED_CONFIRM;
$needs_email_confirmation = isset($OJ_EMAIL_CONFIRM) && $OJ_EMAIL_CONFIRM;
$defunct = ($needs_admin_confirmation || $needs_email_confirmation) ? 'Y' : 'N';
$active_code = '';

if ($needs_email_confirmation) {
    if (empty($_SESSION[$OJ_NAME . '_activecode'])) $_SESSION[$OJ_NAME . '_activecode'] = getToken(18);
    $active_code = $_SESSION[$OJ_NAME . '_activecode'];
} else {
    $_SESSION[$OJ_NAME . '_activecode'] = '';
}

$group_name = function_exists('getMappedSpecial') ? getMappedSpecial($user_id) : '';
if ($needs_email_confirmation) pdo_query('START TRANSACTION');

$insert_result = pdo_query(
    'INSERT INTO `users` (`user_id`,`email`,`ip`,`accesstime`,`password`,`reg_time`,`nick`,`school`,`group_name`,`defunct`,`activecode`) VALUES (?,?,?,NOW(),?,NOW(),?,?,?,?,?)',
    $user_id,
    $email,
    $ip,
    pwGen($password_text),
    $nick,
    $school,
    $group_name,
    $defunct,
    $active_code
);

if ($insert_result === false || (is_array($insert_result) && count($insert_result) === 0)) {
    if ($needs_email_confirmation) pdo_query('ROLLBACK');
    echo '<h2>[DB 에러] 유저 생성(INSERT) 쿼리가 반영되지 않았습니다.</h2>';
    echo '아이디: ' . htmlspecialchars($user_id, ENT_QUOTES, 'UTF-8') . '<br>';
    exit(0);
}

if ($needs_email_confirmation) {
    $activation_url = gsmoj_absolute_url('active.php?code=' . urlencode($active_code));
    $subject = '=?UTF-8?B?' . base64_encode('[광주소프트웨어마이스터고] GSMOJ 회원가입 인증 메일') . '?=';
    $mail_body = gsmoj_registration_mail($user_id, $activation_url);
    $mail_error = null;
    $mail_sent = email($email, $subject, strip_tags($mail_body), $mail_body, $mail_error);

    if ($mail_sent) {
        pdo_query('COMMIT');
        $mark = 100;
        $view_errors = "<span style='font-size:18px;font-weight:bold;'>📩 인증 메일이 발송되었습니다.</span><br><br><strong>" . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</strong> 주소로 계정 활성화 링크를 전송했습니다.<br>메일함을 확인하신 후 인증 링크를 클릭하여 가입을 완료해 주세요.";
    } else {
        pdo_query('ROLLBACK');
        $_SESSION[$OJ_NAME . '_activecode'] = '';
        $mark = 0;
        $view_errors = "<span style='font-size:18px;font-weight:bold;'>회원가입을 완료하지 못했습니다.</span><br><br>인증 메일을 발송하지 못해 계정이 생성되지 않았습니다.<br>SMTP 설정이 복구된 후 다시 가입해 주세요.";
    }
    require('template/' . $OJ_TEMPLATE . '/error.php');
    exit(0);
}

pdo_query('INSERT INTO `loginlog` (`user_id`,`password`,`ip`,`time`) VALUES (?, ?, ?, NOW())', $user_id, 'no save', $ip);

if (!$needs_admin_confirmation) {
    $_SESSION[$OJ_NAME . '_user_id'] = $user_id;
    $_SESSION[$OJ_NAME . '_nick'] = $nick;
    $privileges = pdo_query('SELECT `rightstr` FROM `privilege` WHERE `user_id`=?', $user_id);
    foreach ($privileges as $privilege) $_SESSION[$OJ_NAME . '_' . $privilege['rightstr']] = true;
    $_SESSION[$OJ_NAME . '_ac'] = array();
    $_SESSION[$OJ_NAME . '_sub'] = array();
    header('Location: index.php');
    exit(0);
}

echo "<script>alert('가입 승인 대기 중입니다.');history.go(-2);</script>";
?>
