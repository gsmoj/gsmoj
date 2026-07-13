<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require dirname(__FILE__).'/Exception.php';
require dirname(__FILE__).'/PHPMailer.php';
require dirname(__FILE__).'/SMTP.php';

function gsmoj_absolute_url($path)
{
    $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';
    $scheme = $is_https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $base_path = rtrim(dirname($_SERVER['REQUEST_URI']), '/\\');

    return $scheme . '://' . $host . $base_path . '/' . ltrim($path, '/');
}

function gsmoj_mail_layout($heading, $message, $action_label, $action_url, $secondary_message, $notice)
{
    $safe_heading = htmlentities($heading, ENT_QUOTES, 'UTF-8');
    $safe_action_label = htmlentities($action_label, ENT_QUOTES, 'UTF-8');
    $safe_action_url = htmlentities($action_url, ENT_QUOTES, 'UTF-8');

    return '<!DOCTYPE html>
<html><head><meta charset="utf-8"></head>
<body style="margin:0;padding:0;background-color:#f4f4f5;font-family:\'Malgun Gothic\',\'맑은 고딕\',helvetica,sans-serif;">
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f4f4f5;padding:40px 10px;"><tr><td align="center">
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:600px;background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,.05);">
<tr><td style="background:linear-gradient(135deg,#1f2937,#111827);padding:30px;text-align:left;"><span style="color:#fff;font-size:24px;font-weight:bold;letter-spacing:1px;">GSMOJ</span></td></tr>
<tr><td style="padding:40px 30px;text-align:left;line-height:1.6;">
<h2 style="margin-top:0;color:#111827;font-size:20px;font-weight:700;">' . $safe_heading . '</h2>
<div style="color:#4b5563;font-size:15px;margin-bottom:30px;">' . $message . '</div>
<table border="0" cellpadding="0" cellspacing="0" style="margin:30px 0;"><tr><td align="center" style="border-radius:4px;" bgcolor="#2563eb"><a href="' . $safe_action_url . '" target="_blank" style="font-size:16px;font-weight:bold;color:#fff;text-decoration:none;padding:14px 30px;display:inline-block;letter-spacing:.5px;">' . $safe_action_label . ' &rarr;</a></td></tr></table>
<div style="color:#4b5563;font-size:14px;margin-top:30px;">' . $secondary_message . '</div>
<p style="color:#9ca3af;font-size:13px;margin-top:25px;border-top:1px solid #e5e7eb;padding-top:20px;">' . $notice . '</p>
</td></tr>
<tr><td style="background-color:#1f2937;padding:20px 30px;text-align:center;"><p style="color:#9ca3af;font-size:12px;margin:0;">Copyright &copy; 2026 GSMOJ / 광주소프트웨어마이스터고등학교. All rights reserved.</p></td></tr>
</table></td></tr></table></body></html>';
}

function gsmoj_registration_mail($user_id, $activation_url)
{
    $safe_url = htmlentities($activation_url, ENT_QUOTES, 'UTF-8');

    return gsmoj_mail_layout(
        '안녕하세요, ' . $user_id . ' 회원님!',
        '광주소프트웨어마이스터고등학교 온라인 저지(GSMOJ) 계정 생성을 환영합니다.<br>아래 버튼을 클릭하여 이메일 주소 인증을 완료하고 가입을 마무리해 주세요!',
        '이메일 주소 인증',
        $activation_url,
        '또는 아래 URL을 클릭하거나 복사하여 브라우저 주소창에 입력하셔도 됩니다:<br><a href="' . $safe_url . '" target="_blank" style="color:#2563eb;word-break:break-all;font-size:13px;">' . $safe_url . '</a>',
        '본인이 요청한 가입이 아니라면 이 메일을 무시하셔도 됩니다.'
    );
}

function gsmoj_password_reset_mail($user_id, $reset_key)
{
    $safe_key = htmlentities($reset_key, ENT_QUOTES, 'UTF-8');

    return gsmoj_mail_layout(
        '안녕하세요, ' . $user_id . ' 회원님!',
        'GSMOJ 계정의 비밀번호 재설정 요청을 받았습니다.<br>아래 버튼을 눌러 재설정 화면으로 돌아간 뒤 인증 코드를 입력해 주세요.',
        '비밀번호 재설정 화면 열기',
        gsmoj_absolute_url('lostpassword.php'),
        '비밀번호 재설정 인증 코드:<br><strong style="display:inline-block;margin-top:10px;padding:12px 16px;color:#111827;background:#f3f4f6;border-radius:6px;font-size:18px;letter-spacing:2px;">' . $safe_key . '</strong><br><span style="color:#9ca3af;font-size:13px;">인증이 완료되면 이 코드가 임시 비밀번호로 설정됩니다.</span>',
        '본인이 요청한 비밀번호 재설정이 아니라면 이 메일을 무시하셔도 됩니다.'
    );
}

function email($address,$mailtitle,$mailcontent,$html=""){
        
        global $OJ_NAME,$SMTP_SERVER,$SMTP_PORT,$SMTP_USER,$SMTP_PASS;

    $mail = new PHPMailer(true);
    
    // 만약 주석 검증 플래그가 원래대로 유지되어 있다면 발송 처리 진행
    if( $SMTP_USER != "mailer@qq.com") {      
        try {
            // Server settings
            // 💡 에러가 날 경우 DEBUG_SERVER(2)로 바꾸면 브라우저에 구글 대화 로그가 상세히 찍힙니다.
            $mail->SMTPDebug = SMTP::DEBUG_OFF;                      
            $mail->isSMTP();                                            // Send using SMTP
            $mail->Host       = $SMTP_SERVER;                     // Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
            $mail->Username   = $SMTP_USER;                     // SMTP username
            $mail->Password   = $SMTP_PASS;                               // SMTP password
            
            // 🛠️ [수정 1] 구글 587 포트에 맞게 STARTTLS 암호화 방식으로 변경
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;            
            $mail->Port       = $SMTP_PORT;                                    

            // 🛠️ [수정 2] 우분투 로컬 환경 인증서 검증 우회 설정 주입 (구글 튕김 방지 필수 코드)
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Recipients
            $mail->setFrom($SMTP_USER, $OJ_NAME );
            $mail->addAddress($address, $OJ_NAME.' User');     // Add a recipient

            // Content
            if($html!=""){
                $mail->Body= $html;
                $mail->isHTML(true);    // Set email format to HTML
            }else{
                $mail->Body= $mailcontent;
                $mail->isHTML(false);
            }
            $mail->Subject= $mailtitle;
            $mail->AltBody = $mailcontent;
            
            $mail->send();
            echo 'Message has been sent';
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }       
    }       
}
