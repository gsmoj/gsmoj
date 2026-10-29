<?php
// DataGSM OAuth(PKCE) 로그인.
// 파라미터 없이 들어오면 state·code_verifier를 세션에 저장하고 DataGSM 인가 화면으로 보낸다.
// DataGSM에서 code·state를 받아 돌아오면 토큰 교환과 userinfo 조회 뒤 회원 계정에 연결해 로그인시킨다.

// 세션 쿠키가 SameSite=Strict라서 DataGSM에서 넘어온 첫 요청에는 쿠키가 실리지 않는다.
// 이때 세션을 시작하면 새 세션 쿠키가 기존 쿠키를 덮어쓰므로, 세션 없이 같은 사이트에서 한 번 더 이동시킨다.
if ((isset($_GET['code']) || isset($_GET['error'])) && !isset($_GET['bounce'])) {
    $query = array();
    foreach (array('code', 'state', 'error') as $key) {
        if (isset($_GET[$key]) && is_string($_GET[$key])) $query[$key] = $_GET[$key];
    }
    $query['bounce'] = 1;
    $target = htmlspecialchars('login_datagsm.php?' . http_build_query($query), ENT_QUOTES, 'UTF-8');
    header('Cache-Control: no-store');
    header('Referrer-Policy: no-referrer');
    // 세션을 열기 전이라 lang 파일을 불러오지 않고 안내 문구를 직접 쓴다. 링크는 자동 이동이 막힌 경우를 위한 것이다.
    echo "<!doctype html><html lang='ko'><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'>";
    echo "<meta http-equiv='refresh' content='0;url=$target'><title>로그인 중</title>";
    echo "<style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f4f5f7;color:#4b5563;font:15px/1.6 -apple-system,BlinkMacSystemFont,'Apple SD Gothic Neo','Malgun Gothic',sans-serif;text-align:center}";
    echo "p{margin:0 0 6px}a{color:#9ca3af;font-size:13px}</style></head>";
    echo "<body><div><p>DataGSM 로그인 처리 중입니다…</p><a href='$target'>자동으로 넘어가지 않으면 여기를 누르세요</a></div></body></html>";
    exit(0);
}

require_once("./include/db_info.inc.php");
require_once("./include/my_func.inc.php");
require_once("./include/login_session.php");
require_once("./include/datagsm_oauth.php");
require_once('./include/setlang.php');
header('Cache-Control: no-store');

$session_key = $OJ_NAME . '_datagsm_oauth';

// 실패하면 loginlog에 남기고 안내 문구와 함께 로그인 화면으로 돌려보낸다.
function datagsm_fail($reason, $user_id = '') {
    global $ip;
    pdo_query("INSERT INTO `loginlog`(user_id,password,ip,time) VALUES(?,?,?,NOW())", $user_id, substr('datagsm fail: ' . $reason, 0, 40), $ip);
    header("Location: loginpage.php?datagsm_error=" . urlencode($reason));
    exit(0);
}

if (isset($_SESSION[$OJ_NAME . '_' . 'user_id'])) {
    header("Location: index.php");
    exit(0);
}

if (!datagsm_enabled()) {
    header("Location: loginpage.php?datagsm_error=disabled");
    exit(0);
}

// 1단계: 인가 요청
if (!isset($_GET['bounce'])) {
    list($verifier, $challenge) = datagsm_new_pkce();
    $state = datagsm_new_state();
    $_SESSION[$session_key] = array('state' => $state, 'verifier' => $verifier, 'time' => time());
    header("Location: " . datagsm_authorize_url($state, $challenge));
    exit(0);
}

// 2단계: 콜백. state는 한 번만 쓰고 바로 지운다.
$saved = isset($_SESSION[$session_key]) ? $_SESSION[$session_key] : null;
unset($_SESSION[$session_key]);
$state = isset($_GET['state']) && is_string($_GET['state']) ? $_GET['state'] : '';
if (!is_array($saved) || $state === '' || !hash_equals($saved['state'], $state) || time() - $saved['time'] > 600) {
    datagsm_fail('state');
}
if (isset($_GET['error']) || !isset($_GET['code']) || !is_string($_GET['code']) || $_GET['code'] === '') {
    datagsm_fail('denied');
}

$access_token = datagsm_exchange_code($_GET['code'], $saved['verifier']);
if ($access_token === false) datagsm_fail('server');
$userinfo = datagsm_fetch_userinfo($access_token);
unset($access_token);
if ($userinfo === false) datagsm_fail('server');
list($status, $info) = $userinfo;
if ($status === 403 || (isset($info['status']) && $info['status'] === 'PENDING')) datagsm_fail('pending');
if ($status !== 200 || !isset($info['id']) || empty($info['email'])) datagsm_fail('server');

$datagsm_id = strval($info['id']);
$email = strval($info['email']);
$object_type = isset($info['objectType']) ? strval($info['objectType']) : '';
$student = ($object_type === 'STUDENT' && isset($info['student']) && is_array($info['student'])) ? $info['student'] : null;
$teacher = ($object_type === 'TEACHER' && isset($info['teacher']) && is_array($info['teacher'])) ? $info['teacher'] : null;
if ($object_type === 'STUDENT' && $student === null) datagsm_fail('server');
if ($student !== null && ((isset($student['role']) && $student['role'] === 'WITHDRAWN') || !empty($student['isLeaveSchool']))) {
    datagsm_fail('withdrawn');
}

$name = '';
if ($student !== null && isset($student['name'])) $name = strval($student['name']);
else if ($teacher !== null && isset($teacher['name'])) $name = strval($teacher['name']);
$student_value = function ($key) use ($student) {
    return ($student !== null && isset($student[$key])) ? $student[$key] : null;
};
// 졸업생은 grade·classNum이 null이라 반을 바꾸지 않는다. 교사도 바꾸지 않는다.
$group_name = datagsm_group_name($student);

// 계정 매핑: DataGSM 연결 → 같은 이메일의 기존 회원 → 자동 가입
$login = '';
$linked = pdo_query("SELECT `user_id` FROM `datagsm_user` WHERE `datagsm_id`=?", $datagsm_id);
if (!is_array($linked)) datagsm_fail('server');
if (count($linked) == 1) {
    $login = $linked[0]['user_id'];
    pdo_query("UPDATE `datagsm_user` SET `email`=?,`name`=?,`object_type`=?,`grade`=?,`class_num`=?,`number`=?,`student_number`=?,`major`=?,`student_role`=?,`last_login`=NOW() WHERE `datagsm_id`=?",
        $email, $name, $object_type, $student_value('grade'), $student_value('classNum'), $student_value('number'),
        $student_value('studentNumber'), $student_value('major'), $student_value('role'), $datagsm_id);
} else {
    $same_email = pdo_query("SELECT `user_id`,`defunct` FROM `users` WHERE `email`=?", $email);
    // 같은 이메일로 가입한 회원이 여럿이면 사용 중인 회원만 남기고, 그래도 여럿이면 이메일 @ 앞부분과 같은 아이디를 고른다.
    if (count($same_email) > 1) {
        $candidates = array_values(array_filter($same_email, function ($row) { return $row['defunct'] != 'Y'; }));
        if (count($candidates) == 0) datagsm_fail('defunct', $same_email[0]['user_id']);
        if (count($candidates) > 1) {
            $email_id = strtolower(strstr($email, '@', true));
            $candidates = array_values(array_filter($candidates, function ($row) use ($email_id) { return strtolower($row['user_id']) === $email_id; }));
        }
        if (count($candidates) != 1) datagsm_fail('conflict');
        $same_email = $candidates;
    }
    if (count($same_email) == 1) {
        $login = $same_email[0]['user_id'];
        if ($same_email[0]['defunct'] == 'Y') datagsm_fail('defunct', $login);
        $other = pdo_query("SELECT `datagsm_id` FROM `datagsm_user` WHERE `user_id`=?", $login);
        if (count($other) > 0) datagsm_fail('conflict', $login);
    } else {
        // 아이디는 이메일 @ 앞부분. 형식이 맞지 않거나 이미 있는 아이디면 만들지 않는다.
        $login = substr($email, 0, strpos($email, '@') === false ? strlen($email) : strpos($email, '@'));
        if ($login === '' || strlen($login) > 48 || !is_valid_user_name($login) || $login[0] === '*') datagsm_fail('conflict');
        $exists = pdo_query("SELECT `user_id` FROM `users` WHERE `user_id`=?", $login);
        if (count($exists) > 0) datagsm_fail('conflict', $login);
        // 비밀번호 로그인은 막기 위해 아무도 모르는 무작위 값의 해시를 넣는다.
        pdo_query("INSERT INTO `users` (`user_id`,`email`,`ip`,`accesstime`,`password`,`reg_time`,`nick`,`school`,`group_name`,`defunct`,`activecode`) VALUES (?,?,?,NOW(),?,NOW(),?,?,?,'N','')",
            $login, $email, $ip, pwGen(bin2hex(random_bytes(32))), mb_substr($name !== '' ? $name : $login, 0, 20, 'UTF-8'), '', $group_name);
        $created = pdo_query("SELECT `user_id` FROM `users` WHERE `user_id`=?", $login);
        if (count($created) != 1) datagsm_fail('server', $login);
    }
    $inserted = pdo_query("INSERT INTO `datagsm_user` (`datagsm_id`,`user_id`,`email`,`name`,`object_type`,`grade`,`class_num`,`number`,`student_number`,`major`,`student_role`,`linked_at`,`last_login`) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())",
        $datagsm_id, $login, $email, $name, $object_type, $student_value('grade'), $student_value('classNum'), $student_value('number'),
        $student_value('studentNumber'), $student_value('major'), $student_value('role'));
    if ($inserted === -1 || $inserted === false) datagsm_fail('server', $login);
}

// 비밀번호 로그인과 같은 기준으로 사용 중지·기간 만료 계정을 막는다.
$active = pdo_query("SELECT `user_id` FROM `users` WHERE `user_id`=? and defunct='N' and expiry_date >= curdate()", $login);
if (count($active) != 1) datagsm_fail('defunct', $login);
$login = $active[0]['user_id'];

$view_errors = '';
if (!check_exam_login_ip($login)) datagsm_fail('exam', $login);

if ($group_name !== '') {
    pdo_query("UPDATE `users` SET `group_name`=? WHERE `user_id`=?", $group_name, $login);
}
pdo_query("UPDATE `users` SET `accesstime`=NOW(),`ip`=? WHERE `user_id`=?", $ip, $login);

// 이전 세션 값이 남지 않도록 비우고 새 로그인 세션을 만든다.
session_destroy();
session_start();
login_complete($login, $ip, 'datagsm login ok');
header("Location: " . login_redirect_url());
?>
