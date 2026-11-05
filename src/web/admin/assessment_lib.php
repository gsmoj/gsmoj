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
function asm_save_problems($id, $pids) {
    pdo_query("delete from assessment_problem where assessment_id=?", $id);
    foreach (array_values($pids) as $num => $pid) pdo_query("insert into assessment_problem(assessment_id,num,problem_id) values(?,?,?)", $id, $num, $pid);
}
// 수행평가 전용 문제는 problem.defunct='A'로 구분한다. 학생 화면은 defunct='N'만 보여 주므로 문제 목록에 나오지 않고,
// 대회 경로(problem.php?cid=)는 defunct를 보지 않으므로 회차 안에서는 그대로 풀 수 있다.
function asm_dedicated_ids($pids) {
    $pids = array_filter(array_map("intval", $pids), function ($v) { return $v > 0; });
    if (!$pids) return array();
    $r = pdo_query("select problem_id from problem where defunct='A' and problem_id in (".implode(",", array_unique($pids)).")");
    return empty($r) ? array() : array_map(function ($x) { return intval($x[0]); }, $r);
}
function asm_grant_problem($user, $pid) {
    global $OJ_NAME;
    $has = pdo_query("select count(*) from privilege where user_id=? and rightstr=?", $user, "p".$pid);
    if (empty($has) || intval($has[0][0]) == 0) pdo_query("insert into privilege(user_id,rightstr) values(?,?)", $user, "p".$pid);
    if ($user === asm_me()) $_SESSION[$OJ_NAME.'_'."p".$pid] = true;
}
// 새 문제를 전용 문제로 표시해 수행평가 끝에 붙인다. 관리자가 대신 추가해도 만든 교사가 관리할 수 있게 권한을 준다.
function asm_attach_problem($a, $pid) {
    $id = intval($a['assessment_id']);
    pdo_query("update problem set defunct='A' where problem_id=?", $pid);
    asm_grant_problem($a['owner'], $pid);
    $pids = array_map(function ($r) { return intval($r['problem_id']); }, asm_problems($id));
    $pids[] = intval($pid);
    asm_save_problems($id, $pids);
    return asm_sync_session_problems($id);
}
// 전용 문제가 속한 수행평가 번호. 수정·채점 데이터 화면에서 돌아갈 곳을 찾는 데 쓴다.
function asm_of_problem($pid) {
    $r = pdo_query("select ap.assessment_id from assessment_problem ap join problem p on p.problem_id=ap.problem_id where ap.problem_id=? and p.defunct='A' limit 1", $pid);
    return empty($r) ? 0 : intval($r[0][0]);
}
// 문제를 뺄 수 없는 이유를 돌려준다. 뺄 수 있으면 빈 문자열.
function asm_problem_lock($id, $p) {
    $pid = intval($p['problem_id']);
    $used = pdo_query("select count(*) from assessment_session s
            join contest c on c.contest_id=s.contest_id
            join contest_problem cp on cp.contest_id=s.contest_id and cp.problem_id=?
        where s.assessment_id=? and c.start_time<=now()", $pid, $id);
    if (!empty($used) && intval($used[0][0]) > 0) return "시작한 회차에 쓰인 문제";
    if ($p['defunct'] === 'A') {
        $n = pdo_query("select count(*) from solution where problem_id=?", $pid);
        if (!empty($n) && intval($n[0][0]) > 0) return "제출이 있는 문제";
    }
    return "";
}
function asm_remove_dir($dir) {
    if (!is_dir($dir) || is_link($dir)) return;
    foreach (scandir($dir) as $name) {
        if ($name === "." || $name === "..") continue;
        $path = $dir."/".$name;
        if (is_dir($path) && !is_link($path)) asm_remove_dir($path); else @unlink($path);
    }
    @rmdir($dir);
}
// 시작 전 회차에서 문제를 뺀다. 전용 문제는 문제 행과 채점 데이터까지 지우고, 기존 문제는 연결만 끊는다.
function asm_remove_problem($a, $pid) {
    global $OJ_DATA;
    $id = intval($a['assessment_id']);
    $pid = intval($pid);
    $target = null;
    $rest = array();
    foreach (asm_problems($id) as $p) {
        if (intval($p['problem_id']) === $pid) $target = $p; else $rest[] = intval($p['problem_id']);
    }
    if (!$target) return "문제를 찾을 수 없습니다.";
    $lock = asm_problem_lock($id, $target);
    if ($lock !== "") return $lock."는 삭제할 수 없습니다.";
    asm_save_problems($id, $rest);
    asm_sync_session_problems($id);
    if ($target['defunct'] === 'A') {
        pdo_query("delete from problem where problem_id=?", $pid);
        pdo_query("delete from privilege where rightstr=?", "p".$pid);
        if ($pid > 0 && strlen($OJ_DATA) > 8) asm_remove_dir(rtrim($OJ_DATA, "/")."/".$pid);
    }
    return "";
}
function asm_copy_dir($from, $to) {
    if (!is_dir($from)) return true;   // 채점 데이터를 아직 넣지 않은 문제
    if (!is_dir($to) && !@mkdir($to, 0775, true)) return false;
    @chmod($to, fileperms($from) & 0777);
    $ok = true;
    foreach (scandir($from) as $name) {
        if ($name === "." || $name === "..") continue;
        $src = $from."/".$name;
        $dst = $to."/".$name;
        if (is_link($src)) continue;
        if (is_dir($src)) $ok = asm_copy_dir($src, $dst) && $ok;
        else if (@copy($src, $dst)) @chmod($dst, fileperms($src) & 0777);
        else $ok = false;
    }
    return $ok;
}
// 문제 행과 채점 데이터를 복제해 전용 문제를 만든다. 제출 통계는 복제하지 않는다. [새 번호, 데이터 복사 성공 여부]를 돌려준다.
function asm_clone_problem($pid, $owner) {
    global $OJ_DATA;
    $new = intval(pdo_query("insert into problem(title,description,input,output,sample_input,sample_output,spj,hint,source,in_date,time_limit,memory_limit,defunct,coin,remote_oj,remote_id)
        select title,description,input,output,sample_input,sample_output,spj,hint,source,now(),time_limit,memory_limit,'A',coin,remote_oj,remote_id from problem where problem_id=?", $pid));
    asm_grant_problem($owner, $new);
    $data = rtrim($OJ_DATA, "/");
    return array($new, asm_copy_dir($data."/".intval($pid), $data."/".$new));
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
    // 대회 화면들은 contest.title을 이스케이프 없이 출력하므로 교사가 입력한 제목을 저장 시 이스케이프한다.
    $title = htmlspecialchars($a['title']." - ".$group, ENT_QUOTES, 'UTF-8', false);
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
    if (!empty($n) && intval($n[0][0]) > 0) return "제출이 있는 회차는 삭제할 수 없습니다. 기록을 보존하려면 수행평가 목록에서 비활성으로 바꾸세요.";
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
