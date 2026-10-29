<?php
// 수행평가 진행 중 화면 이탈·복귀·붙여넣기·급격한 코드 증가·복사를 assessment_event 테이블에 기록한다.
// template/syzoj/assessment_monitor.php의 스크립트가 navigator.sendBeacon으로 POST하며, 응답 본문은 쓰지 않는다.
$OJ_CACHE_SHARE = false;
require_once('./include/db_info.inc.php');
require_once('./include/my_func.inc.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION[$OJ_NAME . '_' . 'user_id'])) {
    http_response_code(403);
    exit(0);
}
// 다른 사이트에서 보낸 요청은 받지 않는다.
if (!empty($_SERVER['HTTP_ORIGIN']) && parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST) !== parse_url("//" . $_SERVER['HTTP_HOST'], PHP_URL_HOST)) {
    http_response_code(403);
    exit(0);
}

$user_id = $_SESSION[$OJ_NAME . '_' . 'user_id'];
$cid = intval($_POST['cid'] ?? 0);
$num = intval($_POST['num'] ?? -1);
$event_type = strval($_POST['type'] ?? '');
$place = strval($_POST['place'] ?? '');
$amount = max(0, min(intval($_POST['amount'] ?? 0), 1000000));
$line_count = max(0, min(intval($_POST['lines'] ?? 0), 100000));
if ($num < -1 || $num > 100) $num = -1;

if (!in_array($event_type, array('leave', 'return', 'paste', 'burst', 'copy'), true) || !in_array($place, array('problem', 'editor'), true)) {
    http_response_code(400);
    exit(0);
}

// 관리자와 해당 수행평가 관리자의 화면 확인은 기록하지 않는다.
if (isset($_SESSION[$OJ_NAME . '_' . 'administrator']) || isset($_SESSION[$OJ_NAME . '_' . "m$cid"])) {
    http_response_code(204);
    exit(0);
}

// 진행 중인 수행평가(contest_type 9번 비트)에만 기록한다.
$now = date('Y-m-d H:i:s');
$sql = "SELECT `private` FROM `contest` WHERE `contest_id`=? AND `defunct`='N' AND (`contest_type` & 512)<>0 AND `start_time`<=? AND `end_time`>?";
$contest = pdo_query($sql, $cid, $now, $now);
if (empty($contest)) {
    http_response_code(404);
    exit(0);
}
// 비공개 수행평가는 참가 권한(contest-check.php가 세션에 넣는 c{cid})이 있어야 한다.
if ($contest[0]['private'] && !isset($_SESSION[$OJ_NAME . '_' . 'c' . $cid])) {
    http_response_code(403);
    exit(0);
}

// 한 사용자가 1분에 너무 많이 보내면 더 기록하지 않는다.
$sql = "SELECT count(1) FROM `assessment_event` WHERE `contest_id`=? AND `user_id`=? AND `created_at`>DATE_SUB(NOW(), INTERVAL 1 MINUTE)";
$recent = pdo_query($sql, $cid, $user_id);
if (!empty($recent) && intval($recent[0][0]) >= 120) {
    http_response_code(429);
    exit(0);
}

$sql = "INSERT INTO `assessment_event`(`contest_id`,`num`,`user_id`,`event_type`,`amount`,`line_count`,`place`,`ip`,`created_at`) VALUES(?,?,?,?,?,?,?,?,NOW())";
pdo_query($sql, $cid, $num, $user_id, $event_type, $amount, $line_count, $place, substr($ip, 0, 46));
http_response_code(204);
