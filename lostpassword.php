<?php
require_once('./include/db_info.inc.php');
require_once('./include/setlang.php');
$view_title = "Welcome To Online Judge";

require_once("./include/const.inc.php");
require_once("./include/my_func.inc.php");
$lost_user_id = $_POST['user_id'];
$lost_email = $_POST['email'];
if (isset($_POST['vcode'])) $vcode = trim($_POST['vcode']);

// 인증코드 검증
if ($lost_user_id && ($vcode != $_SESSION[$OJ_NAME . '_' . "vcode"] || $vcode == "" || $vcode == null)) {
    echo "<script language='javascript'>\n";
    echo "alert('인증 코드가 틀렸습니다!');\n";
    echo "history.go(-1);\n";
    echo "</script>";
    exit(0);
}
if (false) {
    $lost_user_id = stripslashes($lost_user_id);
    $lost_email = stripslashes($lost_email);
}

// DB에서 유저 이메일 조회
$sql = "SELECT `email` FROM `users` WHERE `user_id`=? and defunct='N' ";
$result = pdo_query($sql, $lost_user_id);
$row = $result[0];

// 아이디와 이메일이 일치하는지 확인
if ($row && $row['email'] == $lost_email && strpos($lost_email, '@')) {
    $_SESSION[$OJ_NAME . '_' . 'lost_user_id'] = $lost_user_id;
    $_SESSION[$OJ_NAME . '_' . 'lost_key'] = getToken(16);

    require_once("include/email.class.php");     // 새 버전의 메일 발송 정보는 이 파일에 작성해 주세요.
    
    /* [지원 중단 및 보존용, 수정 안 함]
        //******************** 설정 정보 ********************************
        $smtpserver = "smtp.qiye.aliyun.com";//SMTP 서버
        $smtpserverport =25;//SMTP 서버 포트
        $smtpusermail = "mailer@hustoj.com";//SMTP 서버의 유저 이메일
        $smtpemailto = $row['email'];//수신인
        $smtpuser = "mailer@hustoj.com";//SMTP 서버의 유저 계정
        $smtppass = "hustojmailer";//SMTP 서버의 유저 비밀번호
    이메일 정보 메모*/

    $mailtitle = "OJ 시스템 비밀번호 재설정 활성화"; // 메일 제목
    $mailcontent = "$lost_user_id 님:\n안녕하세요!\n귀하께서 [" . basename($ip) . "]를 통해 ${OJ_NAME} 시스템에서 비밀번호 찾기 서비스를 선택하셨습니다. 신원 확인을 위해 아래의 문자열을 토큰 재설정 페이지에 입력해 주세요:\n" . $_SESSION[$OJ_NAME . '_' . 'lost_key'] . "\n\n주의: 인증이 완료되면 위의 문자열이 귀하의 새 비밀번호가 됩니다!\n\n\nHUSTOJ 온라인 저지 시스템"; // 메일 내용

    /* [지원 중단 및 보존용, 수정 안 함]
            $mailtype = "TXT";//메일 형식（HTML/TXT）, TXT는 텍스트 메일
            //************************ 설정 정보 ****************************
            $smtp = new smtp($smtpserver,$smtpserverport,true,$smtpuser,$smtppass);//여기서 true는 인증을 사용함을 의미합니다. 그렇지 않으면 인증을 사용하지 않습니다.
            $smtp->debug =false;//메일 발송 디버그 정보 표시 여부
            $state = $smtp->sendmail($smtpemailto, $smtpusermail, $mailtitle, $mailcontent, $mailtype);
    이메일 정보 메모*/

    // 메일 발송 프록시 또는 기본 이메일 함수 호출
    if ($SMTP_USER == "mailer@qq.com") {
        file_get_contents("http://demo.hustoj.com/email-proxy.php?email=" . $row['email'] . "&token=" . $_SESSION[$OJ_NAME . '_' . 'lost_key']);
    } else {
        email($row['email'], $mailtitle, $mailcontent);
    }

    // 인증 완료 후 다음 단계 템플릿 로드
    require("template/" . $OJ_TEMPLATE . "/lostpassword2.php");

} else {
    // 에러 처리 및 기본 비밀번호 찾기 페이지 로드
    if ($row['email'] != $lost_email) $error_msg = "사용자 아이디와 이메일이 일치하지 않습니다.";
/////////////////////////Template
    require("template/" . $OJ_TEMPLATE . "/lostpassword.php");
}
/////////////////////////Common foot
?>