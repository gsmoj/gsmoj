<?php
require_once('./include/db_info.inc.php');
require_once('./include/setlang.php');
require_once("./include/const.inc.php");
require_once("./include/my_func.inc.php");

/**
 * 사용자 계정 이메일 활성화 기능
 * 메일로 발송된 고유 인증 코드를 검증하여 계정을 정상 상태('N')로 변경합니다.
 */
$code = isset($_GET['code']) ? trim($_GET['code']) : "";

// 이메일 인증 기능이 켜져 있고 인증 코드 길이가 올바른지 확인
if (isset($OJ_EMAIL_CONFIRM) && $OJ_EMAIL_CONFIRM) {
    if (strlen($code) == 18) {
        // 🛠️ [버그 수정] 가입 시 defunct 상태가 'N'이든 'Y'든 토큰만 맞으면 무조건 정상화('N') 처리
        $sql = "UPDATE `users` SET defunct='N', activecode='' WHERE `activecode`=? AND `activecode`!=''";
        $result = pdo_query($sql, $code);
    }
}

// 계정 업데이트 완료 후 로그인 페이지로 리다이렉트
header("location:loginpage.php");
?>
r