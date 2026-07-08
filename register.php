<?php
//////////////////////////// 공통 헤더 및 환경 설정 부
require_once("./include/db_info.inc.php");
if (isset($OJ_REGISTER) && !$OJ_REGISTER) exit(0);

require_once("./include/my_func.inc.php");
require_once('./include/setlang.php');
require_once("./include/email.class.php"); 
if (isset($OJ_CSRF) && $OJ_CSRF) require_once("./include/csrf_check.php");

// 에러 메시지 초기화 및 카운터 설정
$err_str = "";
$err_cnt = 0;
$len;

// 사용자 제출 가입 정보 바인딩
$user_id = trim($_POST['user_id']);
$len = mb_strlen($user_id);
$email = trim($_POST['email']);
$school = trim($_POST['school']);

if (isset($OJ_VCODE) && $OJ_VCODE) $vcode = trim($_POST['vcode']);

// [검증] 보안문자 확인
if ($OJ_VCODE && ($vcode != $_SESSION[$OJ_NAME . '_' . "vcode"] || $vcode == "" || $vcode == null)) {
    $_SESSION[$OJ_NAME . '_' . "vcode"] = null;
    $err_str = $err_str . "Verification Code Wrong!\\n";
    $err_cnt++;
    $_SESSION[$OJ_NAME . '_' . "vfail"] = true;
}

// [검증] 로그인 모듈 검사
if ($OJ_LOGIN_MOD != "hustoj") {
    $err_str = $err_str . "$MSG_SYSTEM $MSG_DISABLE $MSG_REGISTER \\n";
    $err_cnt++;
}

// [검증] 아이디 길이 검사
if ($len > 48) {
    $err_str = $err_str . "$MSG_USER_ID $MSG_TOO_LONG !\\n";
    $err_cnt++;
} else if ($len < 3) {
    $err_str = $err_str . " $MSG_WARNING_USER_ID_SHORT\\n";
    $err_cnt++;
}

// [검증] 아이디 정규식 포맷 검사
if (!is_valid_user_name($user_id)) {
    $err_str = $err_str . "$MSG_USER_ID $MSG_WRONG !\\n";
    $err_cnt++;
}

// [검증] 닉네임 유효성 검사
$nick = trim($_POST['nick']);
$len = mb_strlen($nick);
if ($len > 20) {
    $err_str = $err_str . "$MSG_NICK $MSG_TOO_LONG !\\n";
    $err_cnt++;
} else if ($len == 0) $nick = $user_id;

// [검증] 금지어 필터링
if (has_bad_words($user_id) || has_bad_words($school) || has_bad_words($nick)) {
    $err_str = $err_str . "비속어 또는 금지 단어가 포함되어 있습니다.\\n";
    $err_cnt++;
}

// [검증] 비밀번호 재입력 일치 여부
if (strcmp($_POST['password'], $_POST['rptpassword']) != 0) {
    $err_str = $err_str . "$MSG_WARNING_REPEAT_PASSWORD_DIFF!\\n";
    $err_cnt++;
}

// [검증] 비밀번호 최소 길이 확인
if (strlen($_POST['password']) < 6) {
    $err_cnt++;
    $err_str = $err_str . "$MSG_WARNING_PASSWORD_SHORT \\n";
}

if ($err_cnt > 0) {
    print "<script language='javascript'>\n";
    print "alert('";
    print $err_str;
    print "');\n history.go(-1);\n</script>";
    exit(0);
}

// 패스워드 암호화 및 유저 아이디 중복 체크
$password = pwGen($_POST['password']);
$sql = "SELECT `user_id` FROM `users` WHERE `users`.`user_id` = ?";
$result = pdo_query($sql, $user_id);
if ($result && count($result) > 0) {
    print "<script language='javascript'>\n";
    print "alert('$MSG_USER_ID Existed!\\n');\n";
    print "history.go(-1);\n</script>";
    exit(0);
}

// XSS 방지 처리 및 IP 추출
$nick = htmlentities($nick, ENT_QUOTES, "UTF-8");
$school = htmlentities($school, ENT_QUOTES, "UTF-8");
$email = htmlentities($email, ENT_QUOTES, "UTF-8");
$ip = $_SERVER['REMOTE_ADDR'];

if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty(trim($_SERVER['HTTP_X_FORWARDED_FOR']))) {
    $tmp_ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $ip = htmlentities($tmp_ip[0], ENT_QUOTES, "UTF-8");
}
// 회원가입 도배 속도 제한 제어
if (isset($OJ_REG_SPEED) && $OJ_REG_SPEED > 0) {
    $sql = "SELECT COUNT(*) FROM `users` WHERE (`ip` = ? or email = ? ) AND `reg_time` > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $result = pdo_query($sql, $ip, $email);
    if (intval($result[0][0]) > $OJ_REG_SPEED) {
        print "<script language='javascript'>\n";
        print "alert('$MSG_IP_EMAIL_REG_OVERLIMIT\\n');\n";
        print "history.go(-1);\n</script>";
        exit(0);
    }
}

// 메일 제목 ($sub) 변수를 안전하게 인코딩 처리
$raw_title = "[광주소프트웨어마이스터고] GSMOJ 회원가입 인증 메일";
$sub = "=?UTF-8?B?" . base64_encode($raw_title) . "?=";

// 💡 [교정 블록 1] 이메일 인증 기능이 켜져 있다면, 최초 가입 계정 상태는 무조건 잠금('Y')이어야 active.php와 통합니다.
if ((isset($OJ_REG_NEED_CONFIRM) && $OJ_REG_NEED_CONFIRM) || (isset($OJ_EMAIL_CONFIRM) && $OJ_EMAIL_CONFIRM)) {
    $defunct = "Y";
} else {
    $defunct = "N";
}

// 💡 [교정 블록 2] 인증 세션을 확실하게 보장하고 변수에 담기
if (isset($OJ_EMAIL_CONFIRM) && $OJ_EMAIL_CONFIRM) {
    if (!isset($_SESSION[$OJ_NAME . '_' . 'activecode']) || empty($_SESSION[$OJ_NAME . '_' . 'activecode'])) {
        $_SESSION[$OJ_NAME . '_' . 'activecode'] = getToken(18);
    }
    $my_activecode = $_SESSION[$OJ_NAME . '_' . 'activecode'];
} else {
    $_SESSION[$OJ_NAME . '_' . 'activecode'] = "";
    $my_activecode = "";
}

$my_group = function_exists('getMappedSpecial') ? getMappedSpecial($user_id) : '';

// 1. 안전하게 인서트 쿼리 준비 및 실행 (정렬 완화)
$sql = "INSERT INTO `users` (
            `user_id`, `email`, `ip`, `accesstime`, `password`, 
            `reg_time`, `nick`, `school`, `group_name`, `defunct`, `activecode`
        ) VALUES (
            ?, ?, ?, NOW(), ?, 
            NOW(), ?, ?, ?, ?, ?
        )";

$insert_result = pdo_query($sql, 
    $user_id,       // 1
    $email,         // 2
    $ip,            // 3
    $password,      // 4
    $nick,          // 5
    $school,        // 6
    $my_group,      // 7
    $defunct,       // 8
    $my_activecode  // 9
);

// DB 강제 저장을 한 번 더 호출하여 동기화 유도
pdo_query("COMMIT");

// 인서트 처리 유효성 엄격 검사
if ($insert_result === false || (is_array($insert_result) && count($insert_result) == 0)) {
    echo "<h2>[DB 에러] 유저 생성(INSERT) 쿼리가 반영되지 않았습니다.</h2>";
    echo "아이디: " . htmlspecialchars($user_id) . "<br>";
    exit(0);
}

// 2. DB 저장이 완벽하게 완료된 후, 이메일 전송 단계로 진입
if (isset($OJ_EMAIL_CONFIRM) && $OJ_EMAIL_CONFIRM) {
    // 💡 [교정 블록 3] 디렉터리 경로 분리 기호 보정
    $uri_dir = rtrim(dirname($_SERVER['REQUEST_URI']), '/\\');
    $link = 'http://' . $_SERVER['HTTP_HOST'] . $uri_dir . "/active.php?code=" . $my_activecode;
    
    // 🎨 [오라클 스타일] 깔끔한 HTML 템플릿으로 메일 본문 교체
    $mail_body = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
    </head>
    <body style="margin: 0; padding: 0; background-color: #f4f4f5; font-family: \'Malgun Gothic\', \'맑은 고딕\', helvetica, sans-serif;">
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f4f5; padding: 40px 10px;">
            <tr>
                <td align="center">
                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                        
                        <tr>
                            <td style="background: linear-gradient(135deg, #1f2937, #111827); padding: 30px; text-align: left;">
                                <span style="color: #ffffff; font-size: 24px; font-weight: bold; letter-spacing: 1px;">GSMOJ</span>
                            </td>
                        </tr>
                        
                        <tr>
                            <td style="padding: 40px 30px; text-align: left; line-height: 1.6;">
                                <h2 style="margin-top: 0; color: #111827; font-size: 20px; font-weight: 700;">안녕하세요, '.htmlentities($user_id).' 회원님!</h2>
                                <p style="color: #4b5563; font-size: 15px; margin-bottom: 30px;">
                                    광주소프트웨어마이스터고등학교 온라인 저지(GSMOJ) 계정 생성을 환영합니다.<br>
                                    아래 버튼을 클릭하여 이메일 주소 인증을 완료하고 가입을 마무리해 주세요!
                                </p>
                                
                                <table border="0" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
                                    <tr>
                                        <td align="center" style="border-radius: 4px;" bgcolor="#2563eb">
                                            <a href="'.htmlentities($link).'" target="_blank" style="font-size: 16px; font-weight: bold; color: #ffffff; text-decoration: none; padding: 14px 30px; display: inline-block; letter-spacing: 0.5px;">
                                                이메일 주소 인증 &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                                
                                <p style="color: #4b5563; font-size: 14px; margin-top: 30px;">
                                    또는 아래 URL을 클릭하거나 복사하여 브라우저 주소창에 입력하셔도 됩니다:<br>
                                    <a href="'.htmlentities($link).'" target="_blank" style="color: #2563eb; word-break: break-all; font-size: 13px;">'.htmlentities($link).'</a>
                                </p>
                                
                                <p style="color: #9ca3af; font-size: 13px; margin-top: 25px; border-top: 1px solid #e5e7eb; padding-top: 20px;">
                                    본인이 요청한 가입이 아니라면 이 메일을 무시하셔도 됩니다.
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <td style="background-color: #1f2937; padding: 20px 30px; text-align: center;">
                                <p style="color: #9ca3af; font-size: 12px; margin: 0;">
                                    Copyright &copy; 2026 GSMOJ / 광주소프트웨어마이스터고등학교. All rights reserved.
                                </p>
                            </td>
                        </tr>
                        
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>
    ';

// 메일 발송 실행
    email($email, $sub, $mail_body);

    // [핵심] HUSTOJ error.php 템플릿이 '성공(positive)' 레이아웃을 타도록 플래그 선언
    $mark = 100; 

    // 화면에 출력할 문구 다듬기 (Semantic UI 구조에 맞게 깔끔하게 복사 안내 제거)
    $view_errors = "
    <span style='font-size: 18px; font-weight: bold;'>📩 인증 메일이 발송되었습니다.</span><br><br>
    <strong>" . htmlspecialchars($email) . "</strong> 주소로 계정 활성화 링크를 전송했습니다.<br>
    메일함을 확인하신 후 인증 링크를 클릭하여 가입을 완료해 주세요.";

    require("template/" . $OJ_TEMPLATE . "/error.php");
    exit(0);
}

// 로그인 로그 저장 및 자동 로그인 세션 생성 (이메일 인증 안 쓸 때용)
$sql = "INSERT INTO `loginlog`(user_id,password,ip,time) VALUES(?,?,?,NOW())";
pdo_query($sql, $user_id, "no save", $ip);

if (!isset($OJ_REG_NEED_CONFIRM) || !$OJ_REG_NEED_CONFIRM) {
    $_SESSION[$OJ_NAME . '_' . 'user_id'] = $user_id;
    $_SESSION[$OJ_NAME . '_' . 'nick'] = $nick;
    
    $sql = "SELECT `rightstr` FROM `privilege` WHERE `user_id`=?";
    $result = pdo_query($sql, $user_id);
    foreach ($result as $row) {
        $_SESSION[$OJ_NAME . '_' . $row['rightstr']] = true;
    }
    $_SESSION[$OJ_NAME . '_' . 'ac'] = array();
    $_SESSION[$OJ_NAME . '_' . 'sub'] = array();
    header("location:index.php");
} else {
    echo "<script>alert('가입 승인 대기 중입니다.'); history.go(-2);</script>";
}
?>