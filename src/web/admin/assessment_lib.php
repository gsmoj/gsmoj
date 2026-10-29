<?php
// 수행평가 공통 함수. 수행평가(assessment)는 반별 회차가 공유하는 문제 묶음이고,
// 회차(assessment_session)마다 수행평가 모드(contest_type 9번 비트)가 켜진 비공개 대회를 하나씩 만든다.
// 그래서 회차의 참가·제출·채점·순위는 기존 대회 기능을 그대로 쓴다.

$ASM_CONTEST_TYPE = 32 | 512;   // 5번 비트(차이 보기 금지) + 9번 비트(수행평가 모드)
$ASM_DEFAULT_SETTINGS = array("leave_seconds" => 2, "paste_chars" => 30, "burst_chars" => 200, "copy_chars" => 20);

function asm_me() {
    global $OJ_NAME;
    return isset($_SESSION[$OJ_NAME.'_'.'user_id']) ? $_SESSION[$OJ_NAME.'_'.'user_id'] : "";
}
function asm_is_admin() {
    global $OJ_NAME;
    return isset($_SESSION[$OJ_NAME.'_'.'administrator']);
}
// 수행평가 화면 접근: 관리자, 교사, 대회 생성자
function asm_can_use() {
    global $OJ_NAME;
    return asm_is_admin() || isset($_SESSION[$OJ_NAME.'_'.'teacher']) || isset($_SESSION[$OJ_NAME.'_'.'contest_creator']);
}
// 수정·회차 관리·결과 보기는 만든 사람과 관리자만
function asm_can_manage($a) {
    return asm_is_admin() || ($a && $a['owner'] === asm_me());
}
function asm_get($id) {
    $r = pdo_query("select * from assessment where assessment_id=? and defunct='N'", $id);
    return empty($r) ? null : $r[0];
}
function asm_settings($a) {
    global $ASM_DEFAULT_SETTINGS;
    $s = $a && $a['settings'] ? json_decode($a['settings'], true) : array();
    return array_merge($ASM_DEFAULT_SETTINGS, is_array($s) ? $s : array());
}
function asm_problems($id) {
    $r = pdo_query("select ap.num, ap.problem_id, p.title, p.defunct from assessment_problem ap left join problem p on p.problem_id=ap.problem_id where ap.assessment_id=? order by ap.num", $id);
    return empty($r) ? array() : $r;
}
function asm_sessions($id) {
    $r = pdo_query("select s.session_id, s.contest_id, s.group_name, c.title, c.start_time, c.end_time, c.defunct,
            case when c.start_time<=now() and c.end_time>now() then 'running' when c.start_time>now() then 'upcoming' else 'ended' end as state,
            (select count(*) from privilege where rightstr=concat('c', s.contest_id)) as invited,
            (select count(distinct user_id) from solution where contest_id=s.contest_id) as submitters,
            (select count(*) from solution where contest_id=s.contest_id) as submits
        from assessment_session s join contest c on c.contest_id=s.contest_id
        where s.assessment_id=? order by c.start_time, s.group_name", $id);
    return empty($r) ? array() : $r;
}
function asm_group_members($group) {
    $r = pdo_query("select user_id from users where group_name=? and defunct='N'", $group);
    return empty($r) ? array() : array_map(function ($x) { return $x[0]; }, $r);
}
// 문제 번호 목록 문자열을 검사해 [유효한 번호들, 없는 번호들]을 돌려준다.
function asm_parse_problems($text) {
    $ok = array(); $missing = array();
    foreach (preg_split('/[\s,]+/', trim($text)) as $p) {
        if ($p === "") continue;
        $pid = intval($p);
        if ($pid <= 0 || in_array($pid, $ok)) continue;
        $has = pdo_query("select count(*) from problem where problem_id=?", $pid);
        if (!empty($has) && intval($has[0][0]) > 0) $ok[] = $pid; else $missing[] = $p;
    }
    return array($ok, $missing);
}
function asm_save_problems($id, $pids) {
    pdo_query("delete from assessment_problem where assessment_id=?", $id);
    foreach (array_values($pids) as $num => $pid) pdo_query("insert into assessment_problem(assessment_id,num,problem_id) values(?,?,?)", $id, $num, $pid);
}
// 아직 시작하지 않은 회차의 대회 문제를 수행평가 문제 목록과 맞춘다. 시작한 회차는 제출이 섞이므로 건드리지 않는다.
function asm_sync_session_problems($id) {
    $pids = array_map(function ($r) { return intval($r['problem_id']); }, asm_problems($id));
    $done = 0;
    foreach (asm_sessions($id) as $s) {
        if ($s['state'] !== 'upcoming') continue;
        pdo_query("delete from contest_problem where contest_id=?", $s['contest_id']);
        foreach ($pids as $num => $pid) pdo_query("insert into contest_problem(contest_id,problem_id,num) values(?,?,?)", $s['contest_id'], $pid, $num);
        $done++;
    }
    return $done;
}
// 반 학생 중 아직 참가 권한(cNNNN)이 없는 학생을 추가한다. 반을 옮긴 학생의 권한은 진행 중 혼란을 막기 위해 지우지 않는다.
function asm_sync_participants($cid, $group) {
    $have = pdo_query("select user_id from privilege where rightstr=?", "c".$cid);
    $have = empty($have) ? array() : array_map(function ($x) { return $x[0]; }, $have);
    $added = 0;
    foreach (asm_group_members($group) as $u) {
        if (in_array($u, $have, true)) continue;
        pdo_query("insert into privilege(user_id,rightstr) values(?,?)", $u, "c".$cid);
        $added++;
    }
    return $added;
}
function asm_create_session($a, $group, $start, $end) {
    global $ASM_CONTEST_TYPE, $OJ_NAME;
    $title = $a['title']." - ".$group;
    $cid = pdo_query("insert into contest(title,start_time,end_time,private,langmask,description,password,subnet,contest_type,user_id,defunct) values(?,?,?,1,0,?,'','',?,?,'N')",
        $title, $start, $end, (string)$a['description'], $ASM_CONTEST_TYPE, $a['owner']);
    $cid = intval($cid);
    foreach (asm_problems($a['assessment_id']) as $p) pdo_query("insert into contest_problem(contest_id,problem_id,num) values(?,?,?)", $cid, $p['problem_id'], $p['num']);
    // 만든 사람(교사)을 이 회차 대회의 관리자(mNNNN)로 지정한다.
    pdo_query("insert into privilege(user_id,rightstr) values(?,?)", $a['owner'], "m".$cid);
    if ($a['owner'] === asm_me()) $_SESSION[$OJ_NAME.'_'."m".$cid] = true;
    pdo_query("insert into assessment_session(assessment_id,contest_id,group_name,created_at) values(?,?,?,now())", $a['assessment_id'], $cid, $group);
    $added = asm_sync_participants($cid, $group);
    return array($cid, $added);
}
// 제출이 없는 회차만 삭제한다(대회·문제·권한·회차 연결). 제출이 있으면 기록 보존을 위해 지우지 않는다.
function asm_delete_session($a, $cid) {
    $s = pdo_query("select count(*) from assessment_session where assessment_id=? and contest_id=?", $a['assessment_id'], $cid);
    if (empty($s) || intval($s[0][0]) == 0) return "회차를 찾을 수 없습니다.";
    $n = pdo_query("select count(*) from solution where contest_id=?", $cid);
    if (!empty($n) && intval($n[0][0]) > 0) return "제출이 있는 회차는 삭제할 수 없습니다. 기록을 보존하려면 대회 목록에서 비활성으로 바꾸세요.";
    pdo_query("delete from contest_problem where contest_id=?", $cid);
    pdo_query("delete from privilege where rightstr=? or rightstr=?", "c".$cid, "m".$cid);
    pdo_query("delete from assessment_session where contest_id=?", $cid);
    pdo_query("delete from contest where contest_id=?", $cid);
    return "";
}
function asm_valid_datetime($v) {
    $v = str_replace("T", " ", trim($v));
    if (strlen($v) == 16) $v .= ":00";
    $d = DateTime::createFromFormat("Y-m-d H:i:s", $v);
    return ($d && $d->format("Y-m-d H:i:s") === $v) ? $v : "";
}
function asm_h($s) {
    return htmlentities((string)$s, ENT_QUOTES, 'UTF-8');
}
