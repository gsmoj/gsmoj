<?php
require_once("./include/db_info.inc.php");
require_once("./include/my_func.inc.php");
require_once('./include/setlang.php');
if (isset($OJ_CSRF) && $OJ_CSRF) require_once("./include/csrf_check.php");

// 💡 [교정 블록 1] 누락되었던 IP 추출 변수를 안전하게 정의합니다.
$ip = $_SERVER['REMOTE_ADDR'];
if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty(trim($_SERVER['HTTP_X_FORWARDED_FOR']))) {
    $tmp_ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $ip = htmlentities($tmp_ip[0], ENT_QUOTES, "UTF-8");
}

$use_cookie = false;
$login = false;

if ($OJ_LONG_LOGIN && isset($_COOKIE[$OJ_NAME . "_user"]) && isset($_COOKIE[$OJ_NAME . "_check"])) {
    $C_check = $_COOKIE[$OJ_NAME . "_check"];
    $C_user = $_COOKIE[$OJ_NAME . "_user"];
    $use_cookie = true;
    $C_num = strlen($C_check) - 1;
    $C_num = ($C_num * $C_num) % 7;
    if ($C_check[strlen($C_check) - 1] != $C_num) {
        setcookie($OJ_NAME . "_check", "", 0);
        setcookie($OJ_NAME . "_user", "", 0);
        echo "<script>\n alert('$MSG_COOKIE_ERROR (-1)'); \n history.go(-1); \n </script>";
        exit(0);
    }
    
    // defunct='N' 상태(활성화된 계정)만 쿠키 로그인을 허용합니다.
    $C_info_res = pdo_query("SELECT `password`,`accesstime` FROM `users` WHERE `user_id`=? and defunct='N'", $C_user);
    if ($C_info_res && count($C_info_res) > 0) {
        $C_info = $C_info_res[0];
        $C_len = strlen($C_info[1]);
        $C_res = ""; // 초기화 추가
        for ($i = 0; $i < strlen($C_info[0]); $i++) {
            $tp = ord($C_info[0][$i]);
            $C_res .= chr(39 + ($tp * $tp + ord($C_info[1][$i % $C_len]) * $tp) % 88);
        }
        if (substr($C_check, 0, -1) == sha1($C_res)) {
            $login = $C_user;
        } else {
            setcookie($OJ_NAME . "_check", "", 0);
            setcookie($OJ_NAME . "_user", "", 0);
            echo "<script>\n alert('$MSG_COOKIE_ERROR (-2)'); \n history.go(-1); \n </script>";
            exit(0);
        }
    }
}

$vcode = "";
if (!$use_cookie) {
    if (isset($_POST['vcode'])) $vcode = trim($_POST['vcode']);
    if ($OJ_VCODE && ($vcode != $_SESSION[$OJ_NAME . '_' . "vcode"] || $vcode == "" || $vcode == null)) {
        $_SESSION[$OJ_NAME . '_' . "vfail"] = true;
        echo "<script language='javascript'>\n";
        echo "alert('Verify Code Wrong!');\n";
        echo "history.go(-1);\n";
        echo "</script>";
        exit(0);
    }
    $view_errors = "";
    require_once("./include/login-" . $OJ_LOGIN_MOD . ".php");
    $user_id = $_POST['user_id'];
    $password = $_POST['password_hash'];
    $fiveMinutesAgo = date('Y-m-d H:i:s', strtotime("-5 minutes"));
    
    // IP 변수가 안전하게 매핑되어 정상 작동합니다.
    $failed = pdo_query("SELECT
                        (SELECT COUNT(1) FROM loginlog WHERE user_id=? AND password='login fail' AND time>=?) as user_fail,
                        (SELECT COUNT(1) FROM loginlog WHERE ip=? AND password='login fail' AND time>=?) as ip_fail;", $user_id, $fiveMinutesAgo, $ip, $fiveMinutesAgo);
                        
    if (isset($OJ_LOGIN_FAIL_LIMIT) && ($OJ_LOGIN_FAIL_LIMIT > 0) && ($failed[0][0] > $OJ_LOGIN_FAIL_LIMIT || $failed[0][1] > $OJ_LOGIN_FAIL_LIMIT * 4)) {
        $view_errors = "Failed login too frequently!";
        require("template/" . $OJ_TEMPLATE . "/error.php");
        exit(0);
    }

    // 🔴 [핵심 보정 블록] 로그인 체크 직전, 미인증 계정('Y') 상태인지 사전에 걸러냅니다.
    $check_defunct_res = pdo_query("SELECT `defunct`, `email` FROM `users` WHERE `user_id`=?", $user_id);
    if ($check_defunct_res && count($check_defunct_res) == 1) {
        if ($check_defunct_res[0]['defunct'] == 'Y') {
            
            // 실패 로그를 쌓고 이메일 미인증 전용 에러창 출력
            $sql = "INSERT INTO `loginlog`(user_id,password,ip,time) VALUES(?,'login fail (unverified email)',?,NOW())";
            pdo_query($sql, $user_id, $ip);

            $mark = 0; // 에러 템플릿의 색상을 결정하는 변수 (0: 빨간색 혹은 안내 설정에 맞춰 작동)
            $MSG_ERROR_INFO = "안내"; // 상단 타이틀 바 텍스트 보정
            
            $view_errors = "
            <span style='font-size: 18px; font-weight: bold;'>🔒 이메일 인증이 완료되지 않았습니다.</span><br><br>
            회원가입 시 입력하신 이메일(<strong>" . htmlspecialchars($check_defunct_res[0]['email']) . "</strong>) 주소로 계정 활성화 링크가 발송되었습니다.<br>
            메일함 혹은 스팸함을 확인하시고 <strong>인증 링크를 클릭</strong>하셔야 로그인이 가능합니다.";
            
            require("template/" . $OJ_TEMPLATE . "/error.php");
            exit(0);
        }
    }

    // 기존 로그인 인증 처리 함수 호출
    $login = check_login($user_id, $password);
}

if ($login) {
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
    
    // 💡 [성공 로그 기록]
    $sql = "INSERT INTO `loginlog`(user_id,password,ip,time) VALUES(?,'login ok',?,NOW())";
    pdo_query($sql, $user_id, $ip);

    echo "<script language='javascript'>\n";
    if (isset($_SESSION[$OJ_NAME . "_administrator"]))
        echo "window.location.href='admin/';\n";
    else if (isset($_SESSION[$OJ_NAME . "_contest_creator"]))
        echo "window.location.href='contest.php?my';\n";
    else
        echo "window.location.href='index.php';\n"; // 💡 더 직관적이고 직진성 있는 메인 리다이렉트
    echo "</script>";
} else {
    if (isset($OJ_LOG_ENABLED) && $OJ_LOG_ENABLED) {
        $params = json_encode(maskSensitiveParams($_REQUEST), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $logger->info($params);
    }
    
    // 💡 [실패 로그 기록]
    $sql = "INSERT INTO `loginlog`(user_id,password,ip,time) VALUES(?,'login fail',?,NOW())";
    pdo_query($sql, $user_id, $ip);

    if ($view_errors) {
        require("template/" . $OJ_TEMPLATE . "/error.php");
    } else {
        echo "<script language='javascript'>\n";
        echo "alert('UserName or Password Wrong!');\n";
        echo "history.go(-1);\n";
        echo "</script>";
    }
}
?>