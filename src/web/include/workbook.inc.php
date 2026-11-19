<?php
// 문제집(관리자가 정한 순서로 묶은 문제 목록) 공용 함수. 학생 화면(workbook.php)과 관리 화면(admin/workbook_*.php)이 함께 쓴다.
// 테이블은 admin/update_db.php의 workbook, workbook_problem이다.

function wb_h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// DB 업데이트 전에는 테이블이 없으므로 화면에서 안내 문구를 보여 주기 위해 확인한다.
function wb_ready() {
    $r = pdo_query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ('workbook','workbook_problem')");
    return is_array($r) && !empty($r) && intval($r[0][0]) == 2;
}

function wb_get($id) {
    $r = pdo_query("SELECT * FROM `workbook` WHERE `workbook_id`=? AND `defunct`='N'", intval($id));
    return (is_array($r) && !empty($r)) ? $r[0] : null;
}

// 문제집의 문제를 순서대로 가져온다. 지워진 문제도 순서를 지키도록 left join으로 남긴다.
function wb_problems($id) {
    $r = pdo_query("SELECT wp.`num`, wp.`problem_id`, p.`title`, p.`defunct`, p.`accepted`, p.`submit`
        FROM `workbook_problem` wp LEFT JOIN `problem` p ON p.`problem_id`=wp.`problem_id`
        WHERE wp.`workbook_id`=? ORDER BY wp.`num`", intval($id));
    return is_array($r) ? $r : array();
}

// "1000, 1001 1002" 처럼 쉼표·공백·줄바꿈으로 구분한 문제 번호를 입력 순서대로 정리한다. 중복은 처음 것만 남긴다.
function wb_parse_ids($text, $limit = 500) {
    $out = array();
    foreach (preg_split('/[^0-9]+/', (string)$text) as $v) {
        if ($v === "") continue;
        $pid = intval($v);
        if ($pid > 0 && !in_array($pid, $out, true)) $out[] = $pid;
        if (count($out) >= $limit) break;
    }
    return $out;
}

// 문제 번호 목록으로 문제집 순서를 통째로 바꾼다.
function wb_save_problems($id, $pids) {
    $id = intval($id);
    pdo_query("DELETE FROM `workbook_problem` WHERE `workbook_id`=?", $id);
    foreach (array_values($pids) as $num => $pid)
        pdo_query("INSERT INTO `workbook_problem`(`workbook_id`,`num`,`problem_id`) VALUES(?,?,?)", $id, $num, intval($pid));
    pdo_query("UPDATE `workbook` SET `updated_at`=NOW() WHERE `workbook_id`=?", $id);
}

// 비공개 문제와 진행 중·비공개 대회에 쓰인 문제까지 볼 수 있는 사용자(problem.php의 id 경로와 같은 기준).
function wb_can_see_all() {
    global $OJ_NAME;
    foreach (array('administrator', 'problem_verifiter', 'contest_creator', 'problem_editor') as $right)
        if (isset($_SESSION[$OJ_NAME.'_'.$right])) return true;
    return false;
}

// 일반 사용자가 지금 열 수 있는 문제 번호만 돌려준다. 조건은 problem.php의 연습 경로와 같다.
function wb_visible_ids($pids) {
    global $OJ_FREE_PRACTICE;
    $pids = array_values(array_unique(array_map('intval', $pids)));
    if (empty($pids)) return array();
    if (wb_can_see_all()) return $pids;
    $in = implode(",", array_fill(0, count($pids), "?"));
    if (isset($OJ_FREE_PRACTICE) && $OJ_FREE_PRACTICE) {
        $r = pdo_query("SELECT `problem_id` FROM `problem` WHERE `defunct`='N' AND `problem_id` IN ($in)", $pids);
    } else {
        $now = date('Y-m-d H:i', time());
        $r = pdo_query("SELECT p.`problem_id` FROM `problem` p WHERE p.`defunct`='N' AND p.`problem_id` IN ($in) AND NOT EXISTS (
                SELECT 1 FROM `contest_problem` cp INNER JOIN `contest` c ON cp.contest_id=c.contest_id
                WHERE cp.problem_id=p.problem_id AND (c.end_time>? AND c.defunct='N' OR c.private='1' AND NOT EXISTS (
                    SELECT 1 FROM `assessment_session` gs INNER JOIN `assessment` ga ON ga.assessment_id=gs.assessment_id
                    WHERE gs.contest_id=c.contest_id AND ga.published_at IS NOT NULL
                ))
            )", array_merge($pids, array($now)));
    }
    $out = array();
    if (is_array($r)) foreach ($r as $row) $out[] = intval($row['problem_id']);
    return $out;
}

// 로그인한 사용자의 풀이 상태. 반환값: array(문제번호 => 'ac' 또는 'tried')
function wb_user_states($pids) {
    global $OJ_NAME;
    $pids = array_values(array_unique(array_map('intval', $pids)));
    if (empty($pids) || !isset($_SESSION[$OJ_NAME.'_'.'user_id'])) return array();
    $in = implode(",", array_fill(0, count($pids), "?"));
    $r = pdo_query("SELECT `problem_id`, MIN(`result`) AS `result` FROM `solution`
        WHERE `user_id`=? AND `result`>=4 AND `problem_id` IN ($in) GROUP BY `problem_id`",
        array_merge(array($_SESSION[$OJ_NAME.'_'.'user_id']), $pids));
    $out = array();
    if (is_array($r)) foreach ($r as $row) $out[intval($row['problem_id'])] = intval($row['result']) == 4 ? 'ac' : 'tried';
    return $out;
}
