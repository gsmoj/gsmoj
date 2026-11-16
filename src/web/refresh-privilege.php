<?php
require_once("./include/db_info.inc.php");
require_once('./include/setlang.php');

// 当前user_id
$user_id = isset($_SESSION[$OJ_NAME . '_' . 'user_id']) ? $_SESSION[$OJ_NAME . '_' . 'user_id'] : "";

if (!empty($user_id)) {
    // 已登录的
    if (!empty($_SESSION[$OJ_NAME . '_group_name'])) {
        $sql = "SELECT * FROM `privilege` WHERE `user_id`=? or (user_id=? and rightstr like 'c%' )";
        $result = pdo_query($sql, $user_id, $_SESSION[$OJ_NAME . '_group_name']);
    } else {
        $sql = "SELECT * FROM `privilege` WHERE `user_id`=? ";
        $result = pdo_query($sql, $user_id);
    }
    // 회수된 권한이 세션에 남지 않도록 DB에 없는 권한 키를 먼저 지운다.
    // 이름 있는 권한(admin/privilege_add.php 목록)과 대회 관리(mNNNN)·문제 권한(pNNNN)만 대상으로 한다.
    // 대회 참가(cNNNN)는 대회 비밀번호로도 세션에 들어가고 sNNNN은 풀이 열람 기록이라 지우지 않는다.
    // DB 조회가 실패(-1)하면 권한을 모두 잃지 않도록 세션을 그대로 둔다.
    if (is_array($result)) {
        $db_rights = array();
        foreach ($result as $row) $db_rights[$row['rightstr']] = true;
        $named_rights = array("administrator", "teacher", "problem_editor", "problem_importer", "tag_adder", "problem_verifiter", "source_browser", "contest_creator", "user_adder", "http_judge", "password_setter", "printer", "balloon", "vip", "problem_start", "problem_end", "service_port");
        $prefix = $OJ_NAME . '_';
        foreach (array_keys($_SESSION) as $key) {
            if (strpos($key, $prefix) !== 0) continue;
            $right = substr($key, strlen($prefix));
            if ((in_array($right, $named_rights, true) || preg_match('/^[mp][0-9]+$/', $right)) && !isset($db_rights[$right])) {
                unset($_SESSION[$key]);
            }
        }
    } else {
        $result = array();
    }
    // 刷新各种权限
    foreach ($result as $row) {
        if (isset($row['valuestr'])) {
            $_SESSION[$OJ_NAME . '_' . $row['rightstr']] = $row['valuestr'];
        } else {
            $_SESSION[$OJ_NAME . '_' . $row['rightstr']] = true;
        }
    }
    if (isset($_SESSION[$OJ_NAME . '_vip'])) {  // VIP mark can access all [VIP] marked contest
        $sql = "select contest_id from contest where title like '%[VIP]%'";
        $result = pdo_query($sql);
        foreach ($result as $row) {
            $_SESSION[$OJ_NAME . '_c' . $row['contest_id']] = true;
        }
    };
    ?>
    <script>console.log("Privilege refreshed !!");</script>
    <?php
} else {
    // 没登录的
    ?>
    <script>
        alert("<?php echo $MSG_Login; ?>");
        window.location.href = "./loginpage.php";
    </script>
    <?php
}

