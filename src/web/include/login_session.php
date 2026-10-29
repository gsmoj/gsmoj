<?php
// 로그인 성공 뒤 공통 처리(세션 재발급, 닉네임·반 저장, 권한 로드, 접속 기록)를 맡는다.
// 비밀번호 로그인(login.php)과 DataGSM 로그인(login_datagsm.php)이 같은 흐름을 쓴다.

// 시험 모드(OJ_EXAM_CONTEST_ID)에서는 시험 시작 뒤 처음 로그인한 IP로만 다시 로그인할 수 있다.
// 다른 IP면 $view_errors에 안내를 넣고 false를 돌려준다.
function check_exam_login_ip($user_id) {
    global $ip, $view_errors, $OJ_EXAM_CONTEST_ID, $MSG_WARNING_DURING_EXAM_NOT_ALLOWED, $MSG_WARNING_LOGIN_FROM_DIFF_IP;
    if (!isset($OJ_EXAM_CONTEST_ID) || intval($OJ_EXAM_CONTEST_ID) <= 0) return true;
    $ccid = $OJ_EXAM_CONTEST_ID;
    $sql = "select min(start_time - INTERVAL 30 MINUTE) from contest where start_time<=now() and end_time>=now() and contest_id>=?";
    $rows = pdo_query($sql, $ccid);
    $start_time = $rows[0][0];
    $sql = "select ip from loginlog where user_id=? and time>? order by time desc limit 1";
    $rows = pdo_query($sql, $user_id, $start_time);
    $lastip = $rows[0][0];
    $sql = "select count(1) from `privilege` where `user_id`=? and `rightstr`='administrator' limit 1";
    $rows = pdo_query($sql, $user_id);
    $isAdministrator = ($rows[0][0] > 0);
    if ((!empty($lastip)) && $lastip != $ip && !($isAdministrator)) { // 시험 중 이미 로그인한 IP가 있으면 같은 IP만 허용한다.
        $view_errors = "$MSG_WARNING_LOGIN_FROM_DIFF_IP($lastip/$ip) $MSG_WARNING_DURING_EXAM_NOT_ALLOWED!";
        return false;
    } // 컴퓨터 고장 등은 관리자가 로그인 IP를 바꿔 허용한다.
    return true;
}

// $login 사용자로 로그인 세션을 만들고 loginlog에 $log_text로 기록한다.
function login_complete($login, $ip, $log_text = 'login ok') {
    global $OJ_NAME, $OJ_LONG_LOGIN, $OJ_KEEP_TIME;
    session_regenerate_id();
    $group_name = "";
    $group_row = pdo_query("select group_name,nick from users where user_id=?", $login);
    if (!empty($group_row)) {
        $group_name = $group_row[0]['group_name'];
        $_SESSION[$OJ_NAME . '_nick'] = $group_row[0]['nick'];
        $_SESSION[$OJ_NAME . '_group_name'] = $group_name;
    }
    if (empty($group_name)) {
        $sql = "SELECT * FROM `privilege` WHERE `user_id`=?";
        $_SESSION[$OJ_NAME . '_' . 'user_id'] = $login;
        $result = pdo_query($sql, $login);
    } else {
        $sql = "SELECT * FROM `privilege` WHERE `user_id`=? or (user_id=? and rightstr like 'c%' )";
        $_SESSION[$OJ_NAME . '_' . 'user_id'] = $login;
        $result = pdo_query($sql, $login, $group_name);
    }

    foreach ($result as $row) {
        if (isset($row['valuestr']))
            $_SESSION[$OJ_NAME . '_' . $row['rightstr']] = $row['valuestr'];
        else
            $_SESSION[$OJ_NAME . '_' . $row['rightstr']] = true;
    }
    if (isset($_SESSION[$OJ_NAME . '_vip'])) {
        $sql = "select contest_id from contest where title like '%[VIP]%'";
        $result = pdo_query($sql);
        foreach ($result as $row) {
            $_SESSION[$OJ_NAME . '_c' . $row['contest_id']] = true;
        }
    }

    $sql = "update users set accesstime=now() where user_id=?";
    $result = pdo_query($sql, $login);

    if ($OJ_LONG_LOGIN) {
        $C_info = pdo_query("SELECT `password` , `accesstime` FROM `users` WHERE `user_id`=? and defunct='N'", $login)[0];
        $C_len = strlen($C_info[1]);
        $C_res = "";
        for ($i = 0; $i < strlen($C_info[0]); $i++) {
            $tp = ord($C_info[0][$i]);
            $C_res .= chr(39 + ($tp * $tp + ord($C_info[1][$i % $C_len]) * $tp) % 88);
        }
        $C_res = sha1($C_res);
        $C_time = time() + 86400 * $OJ_KEEP_TIME;
        setcookie($OJ_NAME . "_user", $login, $C_time);
        setcookie($OJ_NAME . "_check", $C_res . (strlen($C_res) * strlen($C_res)) % 7, $C_time);
    }

    $sql = "INSERT INTO `loginlog`(user_id,password,ip,time) VALUES(?,?,?,NOW())";
    pdo_query($sql, $login, $log_text, $ip);
}

// 로그인 뒤 이동할 페이지. 관리자는 관리자 화면, 대회 출제자는 내 대회 목록으로 보낸다.
function login_redirect_url() {
    global $OJ_NAME;
    if (isset($_SESSION[$OJ_NAME . "_administrator"])) return 'admin/';
    if (isset($_SESSION[$OJ_NAME . "_contest_creator"])) return 'contest.php?my';
    return 'index.php';
}
?>
