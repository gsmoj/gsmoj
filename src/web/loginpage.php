<?php
$cache_time = 1;
require_once('./include/cache_start.php');
require_once('./include/db_info.inc.php');
require_once('./include/memcache.php');
require_once('./include/setlang.php');
require_once('./include/bbcode.php');
require_once('./include/datagsm_oauth.php');

// DataGSM 로그인 버튼 노출 여부와 login_datagsm.php에서 돌려보낸 오류 문구
$datagsm_enabled = datagsm_enabled();
$datagsm_error = "";
$datagsm_errors = array(
    'server' => 'MSG_DATAGSM_ERR_SERVER', 'state' => 'MSG_DATAGSM_ERR_STATE', 'denied' => 'MSG_DATAGSM_ERR_DENIED',
    'pending' => 'MSG_DATAGSM_ERR_PENDING', 'withdrawn' => 'MSG_DATAGSM_ERR_WITHDRAWN', 'defunct' => 'MSG_DATAGSM_ERR_DEFUNCT',
    'conflict' => 'MSG_DATAGSM_ERR_CONFLICT', 'exam' => 'MSG_DATAGSM_ERR_EXAM', 'disabled' => 'MSG_DATAGSM_ERR_DISABLED',
);
if (isset($_GET['datagsm_error']) && is_string($_GET['datagsm_error']) && isset($datagsm_errors[$_GET['datagsm_error']])) {
    $msg_name = $datagsm_errors[$_GET['datagsm_error']];
    $datagsm_error = isset($$msg_name) ? $$msg_name : $_GET['datagsm_error'];
}

require_once("./include/db_info.inc.php");
require_once("./include/setlang.php");
$view_title = "LOGIN";

if (isset($_SESSION[$OJ_NAME . '_' . 'user_id'])) {
    header("location:index.php");
    echo "<a href=logout.php>Please logout First!</a>";
    exit(1);
}

/////////////////////////Template
if ($OJ_LONG_LOGIN == true && isset($_COOKIE[$OJ_NAME . "_user"]) && isset($_COOKIE[$OJ_NAME . "_check"])) {
    ?>
    <script>
        let xhr = new XMLHttpRequest();
        xhr.open('GET', 'login.php', true);
        xhr.send();
        setTimeout("location.href='index.php'", 1500);
    </script>
    <?php
} else {
    require("template/" . $OJ_TEMPLATE . "/loginpage.php");
}
/////////////////////////Common foot
if (file_exists('./include/cache_end.php'))
    require_once('./include/cache_end.php');
?>
