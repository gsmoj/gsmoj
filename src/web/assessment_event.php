<?php
// 수행평가 진행 중 화면 이탈·복귀·붙여넣기·급격한 코드 증가·복사를 assessment_event 테이블에 기록한다.
// template/syzoj/assessment_monitor.php의 스크립트가 navigator.sendBeacon으로 POST하며, 응답 본문은 쓰지 않는다.
// 기록하지 않은 이유는 X-GS-Event 응답 헤더로 알려 준다. 브라우저 개발자 도구와 admin/assessment_event_test.php에서 확인한다.
$OJ_CACHE_SHARE = false;
require_once('./include/db_info.inc.php');
require_once('./include/my_func.inc.php');

// 관리자가 테스트 화면에서 보낸 요청(test=1)은 기록하지 않고 단계별 확인 결과를 JSON으로 돌려준다.
$ae_test = isset($_POST['test']) && $_POST['test'] === '1' && isset($_SESSION[$OJ_NAME . '_' . 'administrator']);
$ae_report = array();
function ae_end($code, $reason) {
    global $ae_test, $ae_report;
    header('X-GS-Event: ' . $reason);
    http_response_code($ae_test ? 200 : $code);
    if ($ae_test) {
        header('Content-Type: application/json; charset=utf-8');
        $ae_report['result'] = $reason;
        $ae_report['status'] = $code;
        echo json_encode($ae_report, JSON_UNESCAPED_UNICODE);
    }
    exit(0);
}
// 관리자에게는 SQL 오류를 화면에 출력하므로(include/pdo.php), 테스트 응답이 JSON으로 남도록 출력을 막는다.
if ($ae_test) $POP_UPED = true;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') ae_end(405, 'method');
if (!isset($_SESSION[$OJ_NAME . '_' . 'user_id'])) ae_end(403, 'login');

// 다른 사이트에서 보낸 요청은 받지 않는다. 역방향 프록시가 Host를 바꿔 넘기는 경우를 위해 X-Forwarded-Host도 같은 사이트로 본다.
$ae_origin = !empty($_SERVER['HTTP_ORIGIN']) ? strtolower((string)parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST)) : '';
$ae_hosts = array();
foreach (array('HTTP_HOST', 'HTTP_X_FORWARDED_HOST') as $ae_key) {
    if (empty($_SERVER[$ae_key])) continue;
    $ae_first = trim(explode(',', $_SERVER[$ae_key])[0]);
    $ae_host = strtolower((string)parse_url('//' . $ae_first, PHP_URL_HOST));
    if ($ae_host !== '') $ae_hosts[] = $ae_host;
}
$ae_report['origin'] = array('origin' => $ae_origin, 'hosts' => $ae_hosts);
if ($ae_origin !== '' && !in_array($ae_origin, $ae_hosts, true)) ae_end(403, 'origin');

if ($ae_test) {
    // DB에 쓸 수 있는지 시험 행을 넣었다가 바로 지운다.
    $ae_id = pdo_query("INSERT INTO `assessment_event`(`contest_id`,`num`,`user_id`,`event_type`,`amount`,`line_count`,`place`,`ip`,`created_at`) VALUES(0,-1,?,'test',0,0,'test',?,NOW())",
        $_SESSION[$OJ_NAME . '_' . 'user_id'], substr($ip, 0, 46));
    if ($ae_id === -1 || intval($ae_id) <= 0) ae_end(500, 'db');
    pdo_query("DELETE FROM `assessment_event` WHERE `event_id`=? AND `event_type`='test'", intval($ae_id));
    ae_end(200, 'ok');
}

$user_id = $_SESSION[$OJ_NAME . '_' . 'user_id'];
$cid = intval($_POST['cid'] ?? 0);
$num = intval($_POST['num'] ?? -1);
$event_type = strval($_POST['type'] ?? '');
$place = strval($_POST['place'] ?? '');
$amount = max(0, min(intval($_POST['amount'] ?? 0), 1000000));
$line_count = max(0, min(intval($_POST['lines'] ?? 0), 100000));
if ($num < -1 || $num > 100) $num = -1;

if (!in_array($event_type, array('leave', 'return', 'paste', 'burst', 'copy'), true) || !in_array($place, array('problem', 'editor'), true)) ae_end(400, 'input');

// 관리자와 해당 수행평가 관리자의 화면 확인은 기록하지 않는다.
if (isset($_SESSION[$OJ_NAME . '_' . 'administrator']) || isset($_SESSION[$OJ_NAME . '_' . "m$cid"])) ae_end(204, 'manager');

// 진행 중인 수행평가(contest_type 9번 비트)에만 기록한다.
$now = date('Y-m-d H:i:s');
$sql = "SELECT `private` FROM `contest` WHERE `contest_id`=? AND `defunct`='N' AND (`contest_type` & 512)<>0 AND `start_time`<=? AND `end_time`>?";
$contest = pdo_query($sql, $cid, $now, $now);
if (empty($contest)) ae_end(404, 'not-running');
// 비공개 수행평가는 참가 권한(contest-check.php가 세션에 넣는 c{cid})이 있어야 한다.
if ($contest[0]['private'] && !isset($_SESSION[$OJ_NAME . '_' . 'c' . $cid])) ae_end(403, 'no-access');

// 한 사용자가 1분에 너무 많이 보내면 더 기록하지 않는다.
$sql = "SELECT count(1) FROM `assessment_event` WHERE `contest_id`=? AND `user_id`=? AND `created_at`>DATE_SUB(NOW(), INTERVAL 1 MINUTE)";
$recent = pdo_query($sql, $cid, $user_id);
if (!empty($recent) && intval($recent[0][0]) >= 120) ae_end(429, 'rate');

$sql = "INSERT INTO `assessment_event`(`contest_id`,`num`,`user_id`,`event_type`,`amount`,`line_count`,`place`,`ip`,`created_at`) VALUES(?,?,?,?,?,?,?,?,NOW())";
$inserted = pdo_query($sql, $cid, $num, $user_id, $event_type, $amount, $line_count, $place, substr($ip, 0, 46));
// SQL 오류는 pdo_query가 -1을 돌려주므로 기록 실패를 성공으로 보고하지 않는다.
if ($inserted === -1 || intval($inserted) <= 0) ae_end(500, 'db');
ae_end(204, 'ok');
