<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require dirname(__FILE__).'/Exception.php';
require dirname(__FILE__).'/PHPMailer.php';
require dirname(__FILE__).'/SMTP.php';

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