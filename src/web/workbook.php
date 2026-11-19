<?php
// 문제집. 관리자가 정한 순서대로 묶은 문제를 보여 준다.
// id가 없으면 문제집 목록, 있으면 그 문제집의 문제를 순서대로 보여 준다. 진행도는 로그인한 사용자의 정답 기록으로 센다.
require_once('./include/db_info.inc.php');
require_once('./include/const.inc.php');
require_once('./include/setlang.php');
require_once('./include/my_func.inc.php');
require_once('./include/workbook.inc.php');
$view_title = "코스";

if (!wb_ready()) {
    $view_errors = "코스를 아직 쓸 수 없습니다. 관리자에게 문의하세요.";
    require("template/" . $OJ_TEMPLATE . "/error.php");
    exit(0);
}
$wb_logged_in = isset($_SESSION[$OJ_NAME . '_' . 'user_id']);

if (isset($_GET['id'])) {
    $wb = wb_get(intval($_GET['id']));
    if (!$wb) {
        $view_errors = "해당 코스가 없습니다.";
        require("template/" . $OJ_TEMPLATE . "/error.php");
        exit(0);
    }
    $problems = wb_problems($wb['workbook_id']);
    $all_pids = array_map(function ($p) { return intval($p['problem_id']); }, $problems);
    $visible = array_flip(wb_visible_ids($all_pids));
    $states = wb_user_states(array_keys($visible));
    // 화면에 쓸 행. 지금 볼 수 없는 문제는 제목을 숨기고 순서만 남긴다.
    $wb_rows = array();
    $wb_solved = 0;
    foreach ($problems as $i => $p) {
        $pid = intval($p['problem_id']);
        $open = isset($visible[$pid]) && $p['title'] !== null;
        $state = $open && isset($states[$pid]) ? $states[$pid] : "";
        if ($state === "ac") $wb_solved++;
        $wb_rows[] = array(
            "order" => $i + 1,
            "problem_id" => $pid,
            "open" => $open,
            "title" => $open ? str_replace("&#44;", ",", (string)$p['title']) : "",
            "state" => $state,
            "accepted" => intval($p['accepted']),
            "submit" => intval($p['submit']),
        );
    }
    $wb_total = count($wb_rows);
    $view_title = $wb['title'];
    require("template/" . $OJ_TEMPLATE . "/workbook.php");
} else {
    $books = pdo_query("SELECT `workbook_id`, `title`, `description` FROM `workbook` WHERE `defunct`='N' ORDER BY `workbook_id`");
    if (!is_array($books)) $books = array();
    // 문제집마다 문제 수와 내가 푼 문제 수를 센다. 볼 수 없는 문제도 문제 수에는 넣는다.
    $links = pdo_query("SELECT wp.`workbook_id`, wp.`problem_id` FROM `workbook_problem` wp
        INNER JOIN `workbook` w ON w.`workbook_id`=wp.`workbook_id` AND w.`defunct`='N'");
    $by_book = array();
    if (is_array($links)) foreach ($links as $l) $by_book[intval($l['workbook_id'])][] = intval($l['problem_id']);
    $all_pids = array();
    foreach ($by_book as $pids) $all_pids = array_merge($all_pids, $pids);
    $states = $wb_logged_in ? wb_user_states(wb_visible_ids($all_pids)) : array();
    $wb_books = array();
    foreach ($books as $b) {
        $pids = isset($by_book[intval($b['workbook_id'])]) ? $by_book[intval($b['workbook_id'])] : array();
        $solved = 0;
        foreach ($pids as $pid) if (isset($states[$pid]) && $states[$pid] === "ac") $solved++;
        $wb_books[] = array(
            "workbook_id" => intval($b['workbook_id']),
            "title" => $b['title'],
            "description" => (string)$b['description'],
            "total" => count($pids),
            "solved" => $solved,
        );
    }
    require("template/" . $OJ_TEMPLATE . "/workbook_list.php");
}
