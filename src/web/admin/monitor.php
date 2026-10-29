<?php
// 관리자 첫 화면의 서버 모니터링.
// 대회·수행평가 중 생기는 문제(채점 지연, 제출 이상, 접속 이상)를 확인하고 조치할 수 있도록 탭으로 나눈다.
// 같은 파일이 화면 출력, 탭별 JSON(GET action=...), 조치(POST action=...)를 함께 처리한다.
require_once("../include/db_info.inc.php");
require_once("../include/my_func.inc.php");
if (file_exists("../lang/$OJ_LANG.php")) require_once("../lang/$OJ_LANG.php");
require_once("../include/const.inc.php");

function mon_fail($code, $message) {
    http_response_code($code);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(array("ok" => false, "error" => $message), JSON_UNESCAPED_UNICODE);
    exit;
}
function mon_json($data) {
    header("Content-Type: application/json; charset=utf-8");
    header("Cache-Control: no-store");
    echo json_encode(array_merge(array("ok" => true), $data), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

// 서버 상태와 접속 기록은 운영 정보이므로 관리자만 볼 수 있다.
if (!isset($_SESSION[$OJ_NAME.'_'.'administrator'])) {
    if (isset($_GET['action'])) mon_fail(403, "관리자만 볼 수 있습니다.");
    require("admin-header.php");
    echo "<div class='admin-content-page'><div class='admin-page-heading'><div><h3>서버 모니터링</h3><p>관리자만 볼 수 있는 화면입니다. 왼쪽 메뉴에서 작업을 선택하세요.</p></div></div></div>";
    exit;
}

// 조치 요청 위조를 막기 위한 페이지 토큰(재채점 등 연속 요청을 보내므로 세션 단위 토큰을 쓴다).
if (empty($_SESSION[$OJ_NAME.'_'.'monitor_token'])) $_SESSION[$OJ_NAME.'_'.'monitor_token'] = bin2hex(random_bytes(16));
$mon_token = $_SESSION[$OJ_NAME.'_'.'monitor_token'];

// 채점 대기로 보는 결과 코드: 대기, 재채점 대기, 컴파일 중, 채점 중, 제출 중, 원격 대기, 원격 채점 중
$MON_WAITING = array(0, 1, 2, 3, 15, 16, 17);
$MON_STUCK_WARN = 60;     // 이 시간(초) 넘게 기다리면 경고
$MON_STUCK_DANGER = 180;  // 이 시간(초) 넘게 기다리면 위험

// 시각 기준: 웹의 DB 연결은 init.php에서 time_zone을 PHP 시간대(+8:00)로 맞춘다.
// in_date(DATETIME)·loginlog·NOW()는 이 기준으로 기록되고, judgetime(TIMESTAMP)은 UTC로 저장되지만
// 읽을 때 연결 시간대로 바뀌므로 웹 연결 안에서는 모두 같은 기준으로 바로 비교할 수 있다.
function mon_now() {
    $r = pdo_query("select now()");
    return empty($r) ? date("Y-m-d H:i:s") : $r[0][0];
}
function mon_label($code) {
    global $judge_result;
    return isset($judge_result[$code]) ? $judge_result[$code] : strval($code);
}
function mon_lang($code) {
    global $language_name;
    return isset($language_name[$code]) ? $language_name[$code] : strval($code);
}
function mon_in($values) {
    return implode(",", array_map("intval", $values));
}

function mon_read($path) {
    $text = @file_get_contents($path);
    return $text === false ? "" : $text;
}

function mon_resources() {
    $load = preg_split('/\s+/', trim(mon_read("/proc/loadavg")));
    $cores = max(1, preg_match_all('/^processor\s*:/m', mon_read("/proc/cpuinfo")));
    $mem = array();
    foreach (explode("\n", mon_read("/proc/meminfo")) as $line) {
        if (preg_match('/^(\w+):\s+(\d+)/', $line, $m)) $mem[$m[1]] = intval($m[2]) * 1024;
    }
    $uptime = floatval(explode(" ", mon_read("/proc/uptime"))[0]);
    $disk_total = @disk_total_space("/");
    $disk_free = @disk_free_space("/");
    return array(
        "load" => array(floatval($load[0]), floatval($load[1]), floatval($load[2])),
        "cores" => $cores,
        "mem_total" => isset($mem['MemTotal']) ? $mem['MemTotal'] : 0,
        "mem_available" => isset($mem['MemAvailable']) ? $mem['MemAvailable'] : 0,
        "swap_total" => isset($mem['SwapTotal']) ? $mem['SwapTotal'] : 0,
        "swap_free" => isset($mem['SwapFree']) ? $mem['SwapFree'] : 0,
        "disk_total" => $disk_total ?: 0,
        "disk_free" => $disk_free ?: 0,
        "uptime" => intval($uptime),
    );
}

// /proc의 프로세스 이름으로 서비스 실행 여부를 확인한다. (웹 서버 권한으로는 systemctl을 쓰지 않는다)
// 채점 컨테이너 안의 judge_client도 호스트의 /proc에 보이므로 지금 채점 중인 개수를 셀 수 있다.
function mon_services() {
    $names = array();
    foreach (glob("/proc/[0-9]*/comm") as $comm) {
        $name = trim(mon_read($comm));
        if ($name !== "") $names[$name] = (isset($names[$name]) ? $names[$name] : 0) + 1;
    }
    $count = function ($patterns) use ($names) {
        $n = 0;
        foreach ($names as $name => $c) {
            foreach ($patterns as $p) if (preg_match($p, $name)) { $n += $c; break; }
        }
        return $n;
    };
    $services = array(
        array("key" => "nginx", "label" => "웹 서버 (nginx)", "critical" => true, "count" => $count(array('/^nginx$/'))),
        array("key" => "php", "label" => "PHP-FPM ".PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION, "critical" => true, "count" => $count(array('/^php-fpm/'))),
        array("key" => "db", "label" => "데이터베이스 (MariaDB)", "critical" => true, "count" => $count(array('/^mariadbd$/', '/^mysqld$/'))),
        array("key" => "judged", "label" => "채점 데몬 (judged)", "critical" => true, "count" => $count(array('/^judged$/'))),
        array("key" => "docker", "label" => "Docker (채점 컨테이너)", "critical" => true, "count" => $count(array('/^dockerd$/'))),
        array("key" => "memcached", "label" => "캐시 (memcached)", "critical" => false, "count" => $count(array('/^memcached$/'))),
        array("key" => "cron", "label" => "예약 작업 (cron)", "critical" => false, "count" => $count(array('/^cron$/'))),
    );
    return array("list" => $services, "judging_now" => $count(array('/^judge_client$/')));
}

function mon_one($sql) {
    $args = func_get_args();
    $r = call_user_func_array("pdo_query", $args);
    return empty($r) ? 0 : intval($r[0][0]);
}

function mon_queue_summary() {
    global $MON_WAITING;
    $in = mon_in($MON_WAITING);
    $r = pdo_query("select count(*), timestampdiff(second, min(in_date), now()) from solution where result in ($in) and problem_id<>0");
    $done15 = mon_one("select count(*) from solution where problem_id>0 and result>=4 and judgetime >= now() - interval 15 minute");
    $wait = pdo_query("select avg(greatest(0, timestampdiff(second, in_date, judgetime))), max(greatest(0, timestampdiff(second, in_date, judgetime))) from solution where problem_id>0 and result>=4 and in_date >= now() - interval 30 minute and judgetime is not null");
    return array(
        "count" => empty($r) ? 0 : intval($r[0][0]),
        "oldest" => (empty($r) || $r[0][1] === null) ? 0 : intval($r[0][1]),
        "per_min" => round($done15 / 15, 1),
        "avg_wait" => (empty($wait) || $wait[0][0] === null) ? null : round(floatval($wait[0][0]), 1),
        "max_wait" => (empty($wait) || $wait[0][1] === null) ? null : intval($wait[0][1]),
    );
}

function mon_judging() {
    $by_result = array();
    $rows = pdo_query("select result, count(*) from solution where problem_id>0 and in_date>=curdate() group by result order by count(*) desc");
    if (!empty($rows)) foreach ($rows as $r) {
        $by_result[] = array("code" => intval($r[0]), "label" => mon_label(intval($r[0])), "count" => intval($r[1]));
    }
    return array(
        "last_hour" => mon_one("select count(*) from solution where problem_id>0 and in_date>=now()-interval 1 hour"),
        "today" => mon_one("select count(*) from solution where problem_id>0 and in_date>=curdate()"),
        "today_ac" => mon_one("select count(*) from solution where problem_id>0 and in_date>=curdate() and result=4"),
        "by_result" => $by_result,
    );
}

function mon_operations() {
    global $DB_NAME;
    $backup = null;
    $files = glob("/var/backups/hustoj_*.tar.bz2");
    if (!empty($files)) {
        usort($files, function ($a, $b) { return filemtime($b) - filemtime($a); });
        $backup = array("name" => basename($files[0]), "size" => filesize($files[0]), "mtime" => filemtime($files[0]), "count" => count($files));
    }
    $db_size = pdo_query("select sum(data_length+index_length) from information_schema.tables where table_schema=?", $DB_NAME);
    return array(
        "backup" => $backup,
        "users" => mon_one("select count(*) from users"),
        "problems" => mon_one("select count(*) from problem"),
        "problems_hidden" => mon_one("select count(*) from problem where defunct='Y'"),
        "contests" => mon_one("select count(*) from contest where defunct='N'"),
        "contests_running" => mon_one("select count(*) from contest where defunct='N' and start_time<=now() and end_time>now()"),
        "login_fail_today" => mon_one("select count(*) from loginlog where password like 'login fail%' and time>=curdate()"),
        "login_fail_10m" => mon_one("select count(*) from loginlog where password like 'login fail%' and time>=now()-interval 10 minute"),
        "db_size" => empty($db_size) ? 0 : intval($db_size[0][0]),
    );
}

// 문제가 생겼을 때 화면 맨 위에 띄울 경고. level: danger(빨강) / warn(노랑), tab: 눌렀을 때 이동할 탭
function mon_alerts($res, $svc, $queue, $ops) {
    global $MON_STUCK_WARN, $MON_STUCK_DANGER;
    $alerts = array();
    $add = function ($level, $text, $tab) use (&$alerts) { $alerts[] = array("level" => $level, "text" => $text, "tab" => $tab); };
    foreach ($svc["list"] as $s) {
        if ($s["count"] == 0) $add($s["critical"] ? "danger" : "warn", $s["label"]." 이(가) 실행 중이 아닙니다.", "overview");
    }
    if ($queue["oldest"] >= $MON_STUCK_DANGER) $add("danger", "채점이 ".$queue["oldest"]."초째 멈춰 있는 제출이 있습니다. (대기 ".$queue["count"]."건)", "queue");
    else if ($queue["oldest"] >= $MON_STUCK_WARN) $add("warn", "채점 대기가 ".$queue["oldest"]."초째 이어지고 있습니다. (대기 ".$queue["count"]."건)", "queue");
    else if ($queue["count"] >= 20) $add("warn", "채점 대기 제출이 ".$queue["count"]."건 쌓였습니다.", "queue");
    if ($queue["count"] > 0 && $svc["judging_now"] == 0 && $queue["oldest"] >= 30) $add("danger", "대기 제출이 있는데 채점 중인 프로세스가 없습니다. 채점 데몬을 확인하세요.", "queue");
    $disk = $res["disk_total"] ? ($res["disk_total"] - $res["disk_free"]) / $res["disk_total"] * 100 : 0;
    if ($disk >= 90) $add("danger", "디스크 사용량이 ".round($disk)."%입니다.", "overview");
    $mem = $res["mem_total"] ? ($res["mem_total"] - $res["mem_available"]) / $res["mem_total"] * 100 : 0;
    if ($mem >= 90) $add("warn", "메모리 사용량이 ".round($mem)."%입니다.", "overview");
    if ($res["load"][0] / $res["cores"] >= 1.5) $add("warn", "CPU 부하가 높습니다. (1분 평균 ".$res["load"][0].", 코어 ".$res["cores"]."개)", "overview");
    if ($ops["backup"] === null) $add("warn", "야간 백업 파일을 찾을 수 없습니다.", "overview");
    else if (time() - $ops["backup"]["mtime"] > 30 * 3600) $add("warn", "하루 넘게 새 야간 백업이 없습니다.", "overview");
    if ($ops["login_fail_10m"] >= 20) $add("warn", "최근 10분 동안 로그인 실패가 ".$ops["login_fail_10m"]."건입니다.", "access");
    return $alerts;
}

// ---------- 탭별 데이터 ----------
function mon_queue_list() {
    global $MON_WAITING;
    $in = mon_in($MON_WAITING);
    $rows = pdo_query("select s.solution_id, s.user_id, s.problem_id, s.contest_id, s.num, s.language, s.result, s.in_date,
            timestampdiff(second, s.in_date, now()) as wait, s.judger, u.group_name
        from solution s left join users u on u.user_id=s.user_id
        where s.result in ($in) and s.problem_id<>0 order by s.solution_id asc limit 200");
    $list = array();
    if (!empty($rows)) foreach ($rows as $r) {
        $list[] = array(
            "id" => intval($r['solution_id']), "user" => $r['user_id'], "group" => $r['group_name'],
            "problem" => intval($r['problem_id']), "contest" => intval($r['contest_id']), "num" => intval($r['num']),
            "lang" => mon_lang(intval($r['language'])), "result" => intval($r['result']), "label" => mon_label(intval($r['result'])),
            "in_date" => $r['in_date'], "wait" => intval($r['wait']), "judger" => $r['judger'],
            "kind" => intval($r['problem_id']) < 0 ? "출력 생성" : "제출",
        );
    }
    return $list;
}

// 제출 로그 필터: 대회, 사용자, 반, 문제, 결과, 언어, IP, 기간. before는 이 번호보다 이전 제출(더 보기)
function mon_log() {
    $where = array("s.problem_id>0");
    $args = array();
    $get = function ($k) { return isset($_GET[$k]) ? trim($_GET[$k]) : ""; };
    if ($get('cid') !== "") { $where[] = "s.contest_id=?"; $args[] = intval($get('cid')); }
    if ($get('user') !== "") {
        $u = $get('user');
        if (substr($u, -1) === "*") { $where[] = "s.user_id like ?"; $args[] = str_replace(array("%", "_"), array("\\%", "\\_"), substr($u, 0, -1))."%"; }
        else { $where[] = "s.user_id=?"; $args[] = $u; }
    }
    if ($get('group') !== "") { $where[] = "u.group_name=?"; $args[] = $get('group'); }
    if ($get('pid') !== "") { $where[] = "s.problem_id=?"; $args[] = intval($get('pid')); }
    if ($get('result') !== "") {
        if ($get('result') === "waiting") { global $MON_WAITING; $where[] = "s.result in (".mon_in($MON_WAITING).")"; }
        else { $where[] = "s.result=?"; $args[] = intval($get('result')); }
    }
    if ($get('lang') !== "") { $where[] = "s.language=?"; $args[] = intval($get('lang')); }
    if ($get('ip') !== "") { $where[] = "s.ip like ?"; $args[] = str_replace(array("%", "_"), array("\\%", "\\_"), $get('ip'))."%"; }
    if ($get('from') !== "") { $where[] = "s.in_date>=?"; $args[] = str_replace("T", " ", $get('from')); }
    if ($get('to') !== "") { $where[] = "s.in_date<=?"; $args[] = str_replace("T", " ", $get('to')); }
    if ($get('before') !== "") { $where[] = "s.solution_id<?"; $args[] = intval($get('before')); }
    if ($get('after') !== "") { $where[] = "s.solution_id>?"; $args[] = intval($get('after')); }
    $sql = "select s.solution_id, s.user_id, u.nick, u.group_name, s.problem_id, s.contest_id, s.num, s.language, s.result,
            s.time, s.memory, s.code_length, s.ip, s.in_date, s.pass_rate
        from solution s left join users u on u.user_id=s.user_id
        where ".implode(" and ", $where)." order by s.solution_id desc limit 51";
    $rows = call_user_func_array("pdo_query", array_merge(array($sql), $args));
    $list = array();
    if (!empty($rows)) foreach ($rows as $r) {
        $list[] = array(
            "id" => intval($r['solution_id']), "user" => $r['user_id'], "nick" => $r['nick'], "group" => $r['group_name'],
            "problem" => intval($r['problem_id']), "contest" => intval($r['contest_id']), "num" => intval($r['num']),
            "lang" => mon_lang(intval($r['language'])), "result" => intval($r['result']), "label" => mon_label(intval($r['result'])),
            "time" => intval($r['time']), "memory" => intval($r['memory']), "length" => intval($r['code_length']),
            "ip" => $r['ip'], "in_date" => $r['in_date'], "pass_rate" => floatval($r['pass_rate']),
        );
    }
    $more = count($list) > 50;
    if ($more) array_pop($list);
    return array("list" => $list, "more" => $more);
}

function mon_contest_list() {
    $rows = pdo_query("select contest_id, title, start_time, end_time, private, contest_type, defunct,
            case when start_time<=now() and end_time>now() then 'running' when start_time>now() then 'upcoming' else 'ended' end as state
        from contest
        order by (case when start_time<=now() and end_time>now() then 0 when start_time>now() then 1 else 2 end), end_time desc limit 40");
    $list = array();
    if (!empty($rows)) foreach ($rows as $r) {
        $list[] = array("id" => intval($r['contest_id']), "title" => $r['title'], "start" => $r['start_time'], "end" => $r['end_time'],
            "state" => $r['state'], "private" => intval($r['private']), "defunct" => $r['defunct'] === 'Y',
            "assessment" => (intval($r['contest_type']) & (1 << 9)) ? true : false);
    }
    return $list;
}

function mon_contest_detail($cid) {
    global $MON_WAITING;
    $c = pdo_query("select contest_id, title, start_time, end_time, private, contest_type, now() as now,
            case when start_time<=now() and end_time>now() then 'running' when start_time>now() then 'upcoming' else 'ended' end as state
        from contest where contest_id=?", $cid);
    if (empty($c)) mon_fail(404, "대회를 찾을 수 없습니다.");
    $c = $c[0];
    $in = mon_in($MON_WAITING);
    $problems = array();
    $rows = pdo_query("select cp.num, cp.problem_id, p.title,
            count(s.solution_id) as submits,
            count(distinct s.user_id) as users,
            count(distinct case when s.result=4 then s.user_id end) as ac_users,
            sum(s.result=11) as ce, sum(s.result=10) as re, sum(s.result in ($in)) as waiting
        from contest_problem cp left join problem p on p.problem_id=cp.problem_id
        left join solution s on s.contest_id=cp.contest_id and s.num=cp.num
        where cp.contest_id=? group by cp.num, cp.problem_id, p.title order by cp.num", $cid);
    if (!empty($rows)) foreach ($rows as $r) {
        $problems[] = array("num" => intval($r['num']), "letter" => chr(65 + intval($r['num'])), "problem" => intval($r['problem_id']), "title" => $r['title'],
            "submits" => intval($r['submits']), "users" => intval($r['users']), "ac_users" => intval($r['ac_users']),
            "ce" => intval($r['ce']), "re" => intval($r['re']), "waiting" => intval($r['waiting']));
    }
    // 분당 제출 수: 진행 중이면 최근 60분, 끝났으면 마지막 60분
    $until = $c['state'] === 'running' ? $c['now'] : $c['end_time'];
    $timeline = array();
    $rows = pdo_query("select timestampdiff(minute, in_date, ?) as ago, count(*) as n, sum(result=11 or result=10) as err
        from solution where contest_id=? and in_date<=? and in_date>? - interval 60 minute group by ago", $until, $cid, $until, $until);
    $byAgo = array();
    if (!empty($rows)) foreach ($rows as $r) $byAgo[intval($r['ago'])] = array(intval($r['n']), intval($r['err']));
    for ($i = 59; $i >= 0; $i--) $timeline[] = isset($byAgo[$i]) ? $byAgo[$i] : array(0, 0);
    $by_result = array();
    $rows = pdo_query("select result, count(*) from solution where contest_id=? group by result order by count(*) desc", $cid);
    if (!empty($rows)) foreach ($rows as $r) $by_result[] = array("code" => intval($r[0]), "label" => mon_label(intval($r[0])), "count" => intval($r[1]));
    $top = array();
    $rows = pdo_query("select s.user_id, u.group_name, count(*) as n, count(distinct s.ip) as ips, max(s.in_date) as last
        from solution s left join users u on u.user_id=s.user_id where s.contest_id=? group by s.user_id, u.group_name order by n desc limit 15", $cid);
    if (!empty($rows)) foreach ($rows as $r) $top[] = array("user" => $r['user_id'], "group" => $r['group_name'], "count" => intval($r['n']), "ips" => intval($r['ips']), "last" => $r['last']);
    $err10 = pdo_query("select count(*), sum(result=11), sum(result=10) from solution where contest_id=? and in_date>=now()-interval 10 minute", $cid);
    return array(
        "contest" => array("id" => intval($c['contest_id']), "title" => $c['title'], "start" => $c['start_time'], "end" => $c['end_time'],
            "state" => $c['state'], "private" => intval($c['private']), "assessment" => (intval($c['contest_type']) & (1 << 9)) ? true : false),
        "participants" => mon_one("select count(distinct user_id) from solution where contest_id=?", $cid),
        "active_10m" => mon_one("select count(distinct user_id) from solution where contest_id=? and in_date>=now()-interval 10 minute", $cid),
        "invited" => mon_one("select count(*) from privilege where rightstr=?", "c".$cid),
        "waiting" => mon_one("select count(*) from solution where contest_id=? and result in ($in)", $cid),
        "recent10" => array("total" => empty($err10) ? 0 : intval($err10[0][0]), "ce" => empty($err10) ? 0 : intval($err10[0][1]), "re" => empty($err10) ? 0 : intval($err10[0][2])),
        "problems" => $problems, "timeline" => $timeline, "by_result" => $by_result, "top" => $top,
    );
}

// 접속·이상 징후: 기간은 최근 N시간 또는 특정 대회 시간
function mon_access() {
    $hours = isset($_GET['hours']) ? max(1, min(168, intval($_GET['hours']))) : 3;
    $cid = isset($_GET['cid']) ? intval($_GET['cid']) : 0;
    $from = null; $to = null; $scope = "최근 ".$hours."시간";
    if ($cid > 0) {
        $c = pdo_query("select title, start_time, end_time from contest where contest_id=?", $cid);
        if (empty($c)) mon_fail(404, "대회를 찾을 수 없습니다.");
        $from = $c[0]['start_time']; $to = $c[0]['end_time']; $scope = "대회 ".$cid." (".$c[0]['title'].") 시간";
    } else {
        $r = pdo_query("select now() - interval ? hour, now()", $hours);
        $from = $r[0][0]; $to = $r[0][1];
    }
    $sol_cond = $cid > 0 ? "contest_id=? and in_date between ? and ?" : "in_date between ? and ?";
    $sol_args = $cid > 0 ? array($cid, $from, $to) : array($from, $to);

    $online = array();
    $rows = pdo_query("select ip, uri, ua, lastmove, firsttime from online where lastmove>=? order by lastmove desc limit 100", time() - 600);
    if (!empty($rows)) foreach ($rows as $r) $online[] = array("ip" => $r['ip'], "uri" => $r['uri'], "ua" => $r['ua'], "ago" => time() - intval($r['lastmove']), "since" => intval($r['firsttime']));

    $fails = array();
    $rows = pdo_query("select user_id, ip, count(*) as n, max(time) as last from loginlog where password like 'login fail%' and time between ? and ? group by user_id, ip order by n desc, last desc limit 50", $from, $to);
    if (!empty($rows)) foreach ($rows as $r) $fails[] = array("user" => $r['user_id'], "ip" => $r['ip'], "count" => intval($r['n']), "last" => $r['last']);

    // 한 IP에서 여러 계정: 제출 IP와 로그인 성공 IP를 함께 본다.
    $shared = array();
    $rows = call_user_func_array("pdo_query", array_merge(array("select ip, count(distinct user_id) as n, group_concat(distinct user_id order by user_id separator ', ') as users
        from (select ip, user_id from solution where $sol_cond
              union all select ip, user_id from loginlog where password='login ok' and time between ? and ?) t
        where ip<>'' group by ip having n>=2 order by n desc limit 50"), $sol_args, array($from, $to)));
    if (!empty($rows)) foreach ($rows as $r) $shared[] = array("ip" => $r['ip'], "count" => intval($r['n']), "users" => $r['users']);

    // 한 계정이 여러 IP: 대회 중 자리 이동이나 계정 공유를 확인한다.
    $multi = array();
    $rows = call_user_func_array("pdo_query", array_merge(array("select user_id, count(distinct ip) as n, group_concat(distinct ip order by ip separator ', ') as ips
        from (select ip, user_id from solution where $sol_cond
              union all select ip, user_id from loginlog where password='login ok' and time between ? and ?) t
        where ip<>'' group by user_id having n>=2 order by n desc limit 50"), $sol_args, array($from, $to)));
    if (!empty($rows)) foreach ($rows as $r) $multi[] = array("user" => $r['user_id'], "count" => intval($r['n']), "ips" => $r['ips']);

    $logins = array();
    $rows = pdo_query("select user_id, ip, time from loginlog where password='login ok' and time between ? and ? order by time desc limit 30", $from, $to);
    if (!empty($rows)) foreach ($rows as $r) $logins[] = array("user" => $r['user_id'], "ip" => $r['ip'], "time" => $r['time']);

    return array("scope" => $scope, "from" => $from, "to" => $to, "online" => $online, "fails" => $fails, "shared_ip" => $shared, "multi_ip" => $multi, "logins" => $logins);
}

// ---------- 조치 ----------
function mon_ids($body) {
    $ids = isset($body['ids']) && is_array($body['ids']) ? array_values(array_unique(array_map("intval", $body['ids']))) : array();
    $ids = array_values(array_filter($ids, function ($v) { return $v > 0; }));
    if (count($ids) == 0) mon_fail(400, "제출을 선택해 주세요.");
    if (count($ids) > 200) mon_fail(400, "한 번에 200건까지만 처리할 수 있습니다.");
    return $ids;
}

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    if ($_SERVER['REQUEST_METHOD'] === "POST") {
        $sent = isset($_SERVER['HTTP_X_MONITOR_TOKEN']) ? $_SERVER['HTTP_X_MONITOR_TOKEN'] : "";
        if (!hash_equals($mon_token, $sent)) mon_fail(403, "요청이 만료되었습니다. 페이지를 새로고침해 주세요.");
        $body = json_decode(file_get_contents("php://input"), true);
        if (!is_array($body)) $body = array();
        switch ($action) {
        case "rejudge":
            // admin/rejudge.php의 "제출 재채점"과 같은 처리: 표절 검사 결과를 지우고 재채점 대기로 바꾼다.
            $ids = mon_ids($body);
            $done = 0;
            foreach ($ids as $id) {
                pdo_query("delete from `sim` where `s_id`=?", $id);
                $n = pdo_query("update `solution` set `result`=1, pass_rate=0 where `solution_id`=? and problem_id>0", $id);
                if ($n) { $done++; trigger_judge($id); }
            }
            mon_json(array("done" => $done));
        case "retrigger":
            // 결과는 그대로 두고, 대기(0)·재채점 대기(1) 제출을 채점기에 다시 알린다(UDP 신호가 유실된 경우).
            // 컴파일 중(2)·채점 중(3)에서 멈춘 제출은 채점기가 다시 가져가지 않으므로 재채점해야 한다.
            $ids = mon_ids($body);
            $rows = pdo_query("select solution_id from solution where solution_id in (".mon_in($ids).") and result in (0,1)");
            $done = 0;
            if (!empty($rows)) foreach ($rows as $r) { trigger_judge(intval($r[0])); $done++; }
            mon_json(array("done" => $done, "skipped" => count($ids) - $done));
        case "rejudge_problem":
            // admin/rejudge.php의 "대회 재채점(선택 문제)"과 같은 처리
            $cid = isset($body['cid']) ? intval($body['cid']) : 0;
            $num = isset($body['num']) ? intval($body['num']) : -1;
            if ($cid <= 0 || $num < 0) mon_fail(400, "대회와 문제를 확인해 주세요.");
            $rows = pdo_query("select solution_id from solution where contest_id=? and num=? and problem_id>0", $cid, $num);
            pdo_query("update `solution` set `result`=1, pass_rate=0 where `contest_id`=? and num=? and problem_id>0", $cid, $num);
            if (!empty($rows)) trigger_judge(intval($rows[0][0]));
            mon_json(array("done" => empty($rows) ? 0 : count($rows)));
        }
        mon_fail(400, "알 수 없는 작업입니다.");
    }
    switch ($action) {
    case "stats":
        $res = mon_resources();
        $svc = mon_services();
        $queue = mon_queue_summary();
        $ops = mon_operations();
        $db_version = pdo_query("select version()");
        mon_json(array(
            "now" => mon_now(),
            "alerts" => mon_alerts($res, $svc, $queue, $ops),
            "resources" => $res, "services" => $svc, "queue" => $queue,
            "judging" => mon_judging(), "operations" => $ops,
            "versions" => array("php" => PHP_VERSION, "web" => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : "", "db" => empty($db_version) ? "" : $db_version[0][0]),
        ));
    case "queue":
        mon_json(array("now" => mon_now(), "summary" => mon_queue_summary(), "services" => mon_services(), "list" => mon_queue_list(),
            "warn" => $MON_STUCK_WARN, "danger" => $MON_STUCK_DANGER));
    case "log":
        mon_json(mon_log());
    case "contests":
        mon_json(array("list" => mon_contest_list()));
    case "contest":
        mon_json(mon_contest_detail(isset($_GET['cid']) ? intval($_GET['cid']) : 0));
    case "access":
        mon_json(mon_access());
    }
    mon_fail(400, "알 수 없는 요청입니다.");
}

$mon_groups = pdo_query("select distinct group_name from users where group_name<>'' order by group_name");
require("admin-header.php");
?>
<title>서버 모니터링</title>
<style>
  .mon-tabs { display: flex; flex-wrap: wrap; gap: 4px; margin: -4px 0 18px; border-bottom: 1px solid var(--admin-border); }
  .mon-tabs button { position: relative; height: auto !important; min-height: 0 !important; margin-bottom: -1px; padding: 10px 14px !important; border: 0; border-bottom: 2px solid transparent; border-radius: 0; background: none; color: var(--admin-muted); font-size: 14px; font-weight: 600; cursor: pointer; }
  .mon-tabs button:hover { color: var(--admin-text); }
  .mon-tabs button.is-active { border-bottom-color: var(--admin-primary); color: var(--admin-primary); }
  /* admin.css의 공통 버튼 스타일(테두리·배경·최소 너비)이 탭에 섞이지 않게 덮는다. */
  body:not(.admin-menu-frame) .mon-tabs button { min-width: 0 !important; border: 0 !important; border-bottom: 2px solid transparent !important; border-radius: 0 !important; background: none !important; box-shadow: none !important; }
  body:not(.admin-menu-frame) .mon-tabs button.is-active { border-bottom-color: var(--admin-primary) !important; }
  .mon-tabs .mon-tab-count { margin-left: 6px; padding: 1px 7px; border-radius: 999px; background: #fee2e2; color: #b91c1c; font-size: 11px; }
  .mon-panel { display: none; }
  .mon-panel.is-active { display: block; }
  .mon-alerts { display: grid; gap: 8px; margin: 0 0 16px; }
  .mon-alert { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 14px; border: 1px solid #fde68a; border-radius: 10px; background: #fffbeb; color: #92400e; font-size: 14px; font-weight: 600; }
  .mon-alert.is-danger { border-color: #fecaca; background: #fef2f2; color: #b91c1c; }
  .mon-alert button { height: 30px !important; min-height: 30px !important; padding: 0 10px !important; font-size: 12px; }
  .mon-grid { display: grid; gap: 16px; }
  .mon-kpis { grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 16px; }
  .mon-two { grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); margin-bottom: 16px; }
  .mon-kpi-value { color: var(--admin-text); font-size: 26px; font-weight: 700; line-height: 1.2; }
  .mon-kpi-sub { margin-top: 4px; color: var(--admin-muted); font-size: 13px; }
  .mon-bar { height: 8px; margin-top: 12px; overflow: hidden; border-radius: 999px; background: #e9eef5; }
  .mon-bar span { display: block; height: 100%; border-radius: inherit; background: var(--admin-primary); transition: width .4s ease; }
  .mon-bar.is-warn span { background: #f59e0b; }
  .mon-bar.is-danger span { background: #dc2626; }
  .mon-services { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 10px; margin: 0; padding: 0; list-style: none; }
  .mon-service { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 12px; border: 1px solid var(--admin-border); border-radius: 10px; }
  .mon-service b { font-weight: 600; }
  .mon-dot { display: inline-block; width: 9px; height: 9px; margin-right: 8px; border-radius: 50%; background: #16a34a; box-shadow: 0 0 0 3px #dcfce7; }
  .mon-service.is-down { border-color: #fecaca; background: #fef2f2; }
  .mon-service.is-down .mon-dot { background: #dc2626; box-shadow: 0 0 0 3px #fee2e2; }
  .mon-versions { margin: 12px 0 0; color: var(--admin-muted); font-size: 13px; }
  .mon-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; margin-bottom: 14px; }
  .mon-stat { padding: 10px 12px; border-radius: 10px; background: #f8fafc; }
  .mon-stat span { display: block; color: var(--admin-muted); font-size: 12px; }
  .mon-stat b { font-size: 20px; }
  .mon-stat.is-warn { background: #fffbeb; }
  .mon-stat.is-danger { background: #fef2f2; }
  .mon-stat.is-danger b { color: #b91c1c; }
  .mon-results { display: grid; gap: 6px; margin: 0; padding: 0; list-style: none; }
  .mon-results li { display: grid; grid-template-columns: 120px minmax(0, 1fr) 40px; align-items: center; gap: 10px; font-size: 13px; }
  .mon-results .mon-bar { margin: 0; }
  .mon-results em { color: var(--admin-muted); font-style: normal; text-align: right; }
  .mon-table { margin: 0 !important; font-size: 13px; }
  .mon-table th, .mon-table td { white-space: nowrap; }
  .mon-table td.mon-wrap { white-space: normal; word-break: break-all; }
  .mon-table tr.is-warn td { background: #fffbeb; }
  .mon-table tr.is-danger td { background: #fef2f2; }
  .mon-empty { padding: 18px 0 !important; color: var(--admin-muted); text-align: center; white-space: normal !important; }
  .mon-updated { color: var(--admin-muted); font-size: 13px; }
  .mon-updated.is-error { color: #b91c1c; }
  .mon-toolbar { display: flex; flex-wrap: wrap; align-items: end; gap: 8px 10px; margin: 0 0 12px; }
  .mon-toolbar label { display: grid; gap: 4px; margin: 0; color: var(--admin-muted); font-size: 12px; font-weight: 600; }
  .mon-toolbar input, .mon-toolbar select { width: auto !important; min-width: 0; height: 34px !important; min-height: 34px !important; font-size: 13px !important; }
  .mon-toolbar .btn { height: 34px !important; min-height: 34px !important; padding: 0 12px !important; font-size: 13px !important; }
  .mon-toolbar .mon-spacer { flex: 1 1 auto; }
  .mon-check { display: inline-flex !important; align-items: center; gap: 6px; height: 34px; color: var(--admin-text) !important; font-size: 13px !important; }
  .mon-check input { height: auto !important; min-height: 0 !important; }
  .mon-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin: 10px 0 0; }
  .mon-actions .btn { height: 32px !important; min-height: 32px !important; padding: 0 12px !important; font-size: 13px !important; }
  .mon-timeline { display: flex; align-items: flex-end; gap: 2px; height: 70px; margin: 6px 0 4px; }
  .mon-timeline span { flex: 1 1 0; min-height: 1px; border-radius: 2px 2px 0 0; background: #93c5fd; }
  .mon-timeline span.has-err { background: #f59e0b; }
  .mon-timeline-axis { display: flex; justify-content: space-between; color: var(--admin-muted); font-size: 11px; }
  .mon-contest-pick { display: grid; gap: 6px; margin: 0; padding: 0; list-style: none; }
  .mon-contest-pick button { display: flex; width: 100%; height: auto !important; min-height: 0 !important; align-items: center; justify-content: space-between; gap: 8px; padding: 9px 12px !important; border: 1px solid var(--admin-border); border-radius: 10px; background: #fff; color: var(--admin-text); font-size: 13px; text-align: left; cursor: pointer; }
  .mon-contest-pick button.is-active { border-color: var(--admin-primary); background: #eff6ff; }
  .mon-contest-layout { display: grid; grid-template-columns: 280px minmax(0, 1fr); gap: 16px; align-items: start; }
  .mon-muted { color: var(--admin-muted); }
  .mon-ua { max-width: 360px; overflow: hidden; text-overflow: ellipsis; }
  @media (max-width: 991px) { .mon-contest-layout { grid-template-columns: minmax(0, 1fr); } }
</style>
<div class="admin-content-page">
  <div class="admin-page-heading">
    <div>
      <h3>서버 모니터링</h3>
      <p>서버 상태와 대회·수행평가 중 채점·제출·접속 상황을 확인하고 조치합니다. 열려 있는 탭은 10초마다 갱신됩니다.</p>
    </div>
    <div class="admin-heading-actions">
      <span class="mon-updated" id="mon-updated">불러오는 중…</span>
      <button type="button" class="btn btn-default" id="mon-refresh">지금 새로고침</button>
    </div>
  </div>

  <div class="mon-alerts" id="mon-alerts" role="status" aria-live="polite"></div>

  <nav class="mon-tabs" role="tablist">
    <button type="button" data-tab="overview" class="is-active">개요</button>
    <button type="button" data-tab="queue">채점 큐<span class="mon-tab-count" id="tab-queue-count" hidden></span></button>
    <button type="button" data-tab="log">제출 로그</button>
    <button type="button" data-tab="contest">대회·수행평가</button>
    <button type="button" data-tab="access">접속·이상 징후</button>
  </nav>

  <!-- 개요 -->
  <section class="mon-panel is-active" id="panel-overview">
    <div class="mon-grid mon-kpis">
      <section class="admin-card"><div class="admin-card-title">CPU 부하</div><div class="mon-kpi-value" id="kpi-cpu">-</div><div class="mon-kpi-sub" id="kpi-cpu-sub"></div><div class="mon-bar" id="bar-cpu"><span style="width:0"></span></div></section>
      <section class="admin-card"><div class="admin-card-title">메모리</div><div class="mon-kpi-value" id="kpi-mem">-</div><div class="mon-kpi-sub" id="kpi-mem-sub"></div><div class="mon-bar" id="bar-mem"><span style="width:0"></span></div></section>
      <section class="admin-card"><div class="admin-card-title">디스크 (/)</div><div class="mon-kpi-value" id="kpi-disk">-</div><div class="mon-kpi-sub" id="kpi-disk-sub"></div><div class="mon-bar" id="bar-disk"><span style="width:0"></span></div></section>
      <section class="admin-card"><div class="admin-card-title">가동 시간</div><div class="mon-kpi-value" id="kpi-uptime">-</div><div class="mon-kpi-sub" id="kpi-uptime-sub"></div></section>
    </div>
    <section class="admin-card" style="margin-bottom:16px">
      <div class="admin-card-title">서비스 상태</div>
      <ul class="mon-services" id="mon-services"></ul>
      <p class="mon-versions" id="mon-versions"></p>
    </section>
    <div class="mon-grid mon-two">
      <section class="admin-card">
        <div class="admin-card-title">채점 현황</div>
        <div class="mon-stats" id="mon-judge-stats"></div>
        <div class="admin-field-help" style="margin-bottom:8px">오늘 채점 결과</div>
        <ul class="mon-results" id="mon-results"></ul>
      </section>
      <section class="admin-card">
        <div class="admin-card-title">운영 정보</div>
        <div class="mon-stats" id="mon-ops-stats"></div>
        <div class="admin-field-help" id="mon-backup"></div>
      </section>
    </div>
  </section>

  <!-- 채점 큐 -->
  <section class="mon-panel" id="panel-queue">
    <div class="mon-stats" id="queue-stats"></div>
    <section class="admin-card">
      <div class="admin-card-title">채점 대기 중인 제출 (대기·재채점 대기·컴파일 중·채점 중, 오래된 순)</div>
      <div class="admin-table-wrap">
        <table class="table mon-table">
          <thead><tr><th><input type="checkbox" data-check-all="queue" aria-label="전체 선택"></th><th>번호</th><th>종류</th><th>사용자</th><th>반</th><th>문제</th><th>대회</th><th>언어</th><th>상태</th><th>제출 시각</th><th>대기</th></tr></thead>
          <tbody id="queue-list"></tbody>
        </table>
      </div>
      <div class="mon-actions">
        <button type="button" class="btn btn-default" data-act="retrigger" data-from="queue">선택 제출 채점기 다시 호출</button>
        <button type="button" class="btn btn-warning" data-act="rejudge" data-from="queue">선택 제출 재채점</button>
        <span class="admin-field-help">채점기 다시 호출은 결과를 바꾸지 않고 대기·재채점 대기 제출에 채점 신호만 다시 보냅니다. 컴파일 중·채점 중에서 멈춘 제출은 재채점하세요. <span id="queue-threshold"></span></span>
      </div>
    </section>
  </section>

  <!-- 제출 로그 -->
  <section class="mon-panel" id="panel-log">
    <section class="admin-card">
      <form class="mon-toolbar" id="log-filter">
        <label>대회 <input type="number" name="cid" min="1" placeholder="번호" style="width:90px"></label>
        <label>사용자 <input type="text" name="user" placeholder="아이디 (s25* 접두)" style="width:150px"></label>
        <label>반 <select name="group"><option value="">전체</option><?php if (!empty($mon_groups)) foreach ($mon_groups as $g) echo "<option>".htmlentities($g[0], ENT_QUOTES, 'UTF-8')."</option>"; ?></select></label>
        <label>문제 <input type="number" name="pid" min="1" placeholder="번호" style="width:90px"></label>
        <label>결과 <select name="result"><option value="">전체</option><option value="waiting">채점 대기 전체</option><?php foreach ($judge_result as $code => $label) echo "<option value='$code'>".htmlentities($label, ENT_QUOTES, 'UTF-8')."</option>"; ?></select></label>
        <label>언어 <select name="lang"><option value="">전체</option><?php foreach ($language_name as $code => $label) echo "<option value='$code'>".htmlentities($label, ENT_QUOTES, 'UTF-8')."</option>"; ?></select></label>
        <label>IP <input type="text" name="ip" placeholder="앞부분" style="width:120px"></label>
        <label>시작 <input type="datetime-local" name="from"></label>
        <label>끝 <input type="datetime-local" name="to"></label>
        <button type="submit" class="btn btn-primary">조회</button>
        <button type="button" class="btn btn-default" id="log-reset">초기화</button>
        <span class="mon-spacer"></span>
        <label class="mon-check"><input type="checkbox" id="log-live" checked> 새 제출 자동 추가</label>
      </form>
      <div class="admin-table-wrap">
        <table class="table mon-table">
          <thead><tr><th><input type="checkbox" data-check-all="log" aria-label="전체 선택"></th><th>번호</th><th>제출 시각</th><th>사용자</th><th>반</th><th>문제</th><th>대회</th><th>언어</th><th>결과</th><th>시간</th><th>메모리</th><th>길이</th><th>IP</th><th>보기</th></tr></thead>
          <tbody id="log-list"></tbody>
        </table>
      </div>
      <div class="mon-actions">
        <button type="button" class="btn btn-warning" data-act="rejudge" data-from="log">선택 제출 재채점</button>
        <button type="button" class="btn btn-default" id="log-more" hidden>더 보기</button>
        <span class="admin-field-help" id="log-info"></span>
      </div>
    </section>
  </section>

  <!-- 대회·수행평가 -->
  <section class="mon-panel" id="panel-contest">
    <div class="mon-contest-layout">
      <section class="admin-card">
        <div class="admin-card-title">대회·수행평가 선택 (진행 중 → 예정 → 종료)</div>
        <ul class="mon-contest-pick" id="contest-pick"></ul>
      </section>
      <div id="contest-detail"><section class="admin-card"><p class="mon-empty">왼쪽에서 대회나 수행평가를 고르세요.</p></section></div>
    </div>
  </section>

  <!-- 접속·이상 징후 -->
  <section class="mon-panel" id="panel-access">
    <section class="admin-card" style="margin-bottom:16px">
      <form class="mon-toolbar" id="access-filter">
        <label>기간 <select name="hours"><option value="1">최근 1시간</option><option value="3" selected>최근 3시간</option><option value="24">최근 24시간</option><option value="168">최근 7일</option></select></label>
        <label>또는 대회 시간 <select name="cid" id="access-cid"><option value="">선택 안 함</option></select></label>
        <button type="submit" class="btn btn-primary">조회</button>
        <span class="admin-field-help" id="access-scope"></span>
      </form>
    </section>
    <div class="mon-grid mon-two">
      <section class="admin-card">
        <div class="admin-card-title">한 IP에서 여러 계정 (제출·로그인 기준)</div>
        <div class="admin-table-wrap"><table class="table mon-table"><thead><tr><th>IP</th><th>계정 수</th><th>계정</th></tr></thead><tbody id="access-shared"></tbody></table></div>
      </section>
      <section class="admin-card">
        <div class="admin-card-title">한 계정이 여러 IP (제출·로그인 기준)</div>
        <div class="admin-table-wrap"><table class="table mon-table"><thead><tr><th>사용자</th><th>IP 수</th><th>IP</th></tr></thead><tbody id="access-multi"></tbody></table></div>
      </section>
    </div>
    <div class="mon-grid mon-two">
      <section class="admin-card">
        <div class="admin-card-title">로그인 실패</div>
        <div class="admin-table-wrap"><table class="table mon-table"><thead><tr><th>사용자</th><th>IP</th><th>횟수</th><th>마지막</th></tr></thead><tbody id="access-fails"></tbody></table></div>
      </section>
      <section class="admin-card">
        <div class="admin-card-title">로그인 기록</div>
        <div class="admin-table-wrap"><table class="table mon-table"><thead><tr><th>사용자</th><th>IP</th><th>시각</th></tr></thead><tbody id="access-logins"></tbody></table></div>
      </section>
    </div>
    <section class="admin-card">
      <div class="admin-card-title">지금 접속 중 (최근 10분 안에 움직인 방문자, 로그인 여부와 무관)</div>
      <div class="admin-table-wrap"><table class="table mon-table"><thead><tr><th>IP</th><th>보고 있는 주소</th><th>마지막 활동</th><th>브라우저</th></tr></thead><tbody id="access-online"></tbody></table></div>
    </section>
  </section>
</div>
<script>
(function () {
  var TOKEN = <?php echo json_encode($mon_token)?>;
  var state = { tab: 'overview', timer: null, contest: 0, logMaxId: 0, logMinId: 0, logQuery: '' };

  function $(id) { return document.getElementById(id); }
  function esc(s) { return String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function bytes(n) {
    var u = ['B', 'KB', 'MB', 'GB', 'TB'], i = 0;
    while (n >= 1024 && i < u.length - 1) { n /= 1024; i++; }
    return (i ? n.toFixed(1) : n) + u[i];
  }
  function duration(s) {
    s = Math.max(0, Math.round(s));
    var d = Math.floor(s / 86400), h = Math.floor(s % 86400 / 3600), m = Math.floor(s % 3600 / 60);
    if (d) return d + '일 ' + h + '시간';
    if (h) return h + '시간 ' + m + '분';
    if (m) return m + '분 ' + (s % 60) + '초';
    return s + '초';
  }
  function bar(id, pct) {
    var el = $(id);
    el.className = 'mon-bar' + (pct >= 90 ? ' is-danger' : (pct >= 75 ? ' is-warn' : ''));
    el.firstChild.style.width = Math.min(100, Math.max(0, pct)).toFixed(1) + '%';
  }
  function badgeClass(code) { return code === 4 ? 'admin-badge--on' : (code < 4 || code >= 14 ? 'admin-badge--info' : (code === 11 ? 'admin-badge--off' : 'admin-badge--warn')); }
  function stat(label, value, level) { return '<div class="mon-stat' + (level ? ' is-' + level : '') + '"><span>' + label + '</span><b>' + value + '</b></div>'; }
  function empty(cols, text) { return '<tr><td colspan="' + cols + '" class="mon-empty">' + text + '</td></tr>'; }
  function problemCell(x) { return '<a href="../problem.php?id=' + x.problem + '" target="_blank">' + x.problem + '</a>'; }
  function contestCell(x) { return x.contest ? '<a href="#" data-goto-contest="' + x.contest + '">' + x.contest + (x.num >= 0 ? ' ' + String.fromCharCode(65 + x.num) : '') + '</a>' : '<span class="mon-muted">-</span>'; }
  function userCell(u) { return '<a href="#" data-goto-user="' + esc(u) + '">' + esc(u) + '</a>'; }

  function get(action, params) {
    var q = 'monitor.php?action=' + action + (params ? '&' + params : '');
    return fetch(q, { credentials: 'same-origin', cache: 'no-store' }).then(function (r) { return r.json(); }).then(function (d) {
      if (!d.ok) throw new Error(d.error || '불러오지 못했습니다.');
      return d;
    });
  }
  function post(action, body) {
    return fetch('monitor.php?action=' + action, {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-Monitor-Token': TOKEN }, body: JSON.stringify(body || {})
    }).then(function (r) { return r.json(); }).then(function (d) {
      if (!d.ok) throw new Error(d.error || '처리하지 못했습니다.');
      return d;
    });
  }
  function setUpdated(err) {
    var el = $('mon-updated');
    el.className = 'mon-updated' + (err ? ' is-error' : '');
    el.textContent = err ? '갱신 실패: ' + err : '마지막 갱신 ' + new Date().toLocaleTimeString('ko-KR');
  }

  // ---------- 개요 + 경고 ----------
  function renderAlerts(alerts) {
    $('mon-alerts').innerHTML = alerts.map(function (a) {
      return '<div class="mon-alert' + (a.level === 'danger' ? ' is-danger' : '') + '"><span>' + (a.level === 'danger' ? '⛔ ' : '⚠️ ') + esc(a.text) + '</span>' +
        (a.tab !== state.tab ? '<button type="button" class="btn btn-default" data-goto-tab="' + a.tab + '">확인하기</button>' : '') + '</div>';
    }).join('');
  }
  function renderOverview(d) {
    var r = d.resources;
    var cpu = r.load[0] / r.cores * 100;
    $('kpi-cpu').textContent = cpu.toFixed(0) + '%';
    $('kpi-cpu-sub').textContent = '부하 ' + r.load.map(function (x) { return x.toFixed(2); }).join(' / ') + ' (1·5·15분) · 코어 ' + r.cores + '개';
    bar('bar-cpu', cpu);
    var memUsed = r.mem_total - r.mem_available, memPct = r.mem_total ? memUsed / r.mem_total * 100 : 0;
    $('kpi-mem').textContent = memPct.toFixed(0) + '%';
    $('kpi-mem-sub').textContent = bytes(memUsed) + ' / ' + bytes(r.mem_total) + (r.swap_total ? ' · 스왑 ' + bytes(r.swap_total - r.swap_free) + ' 사용' : '');
    bar('bar-mem', memPct);
    var diskUsed = r.disk_total - r.disk_free, diskPct = r.disk_total ? diskUsed / r.disk_total * 100 : 0;
    $('kpi-disk').textContent = diskPct.toFixed(0) + '%';
    $('kpi-disk-sub').textContent = bytes(diskUsed) + ' / ' + bytes(r.disk_total) + ' · 남은 공간 ' + bytes(r.disk_free);
    bar('bar-disk', diskPct);
    $('kpi-uptime').textContent = duration(r.uptime);
    $('kpi-uptime-sub').textContent = '서버 시각 ' + d.now;

    $('mon-services').innerHTML = d.services.list.map(function (s) {
      var up = s.count > 0;
      return '<li class="mon-service' + (up ? '' : ' is-down') + '"><span><i class="mon-dot"></i><b>' + esc(s.label) + '</b></span>' +
        '<span class="admin-badge ' + (up ? 'admin-badge--on' : 'admin-badge--warn') + '">' + (up ? '실행 중' : '중지됨') + '</span></li>';
    }).join('');
    $('mon-versions').textContent = 'PHP ' + d.versions.php + ' · ' + d.versions.web + ' · DB ' + d.versions.db + ' · 지금 채점 중인 프로세스 ' + d.services.judging_now + '개';

    var q = d.queue, j = d.judging;
    $('mon-judge-stats').innerHTML =
      stat('채점 대기', q.count, q.oldest >= 180 ? 'danger' : (q.oldest >= 60 || q.count >= 20 ? 'warn' : '')) +
      stat('가장 오래 기다린', q.count ? duration(q.oldest) : '-', q.oldest >= 180 ? 'danger' : (q.oldest >= 60 ? 'warn' : '')) +
      stat('최근 1시간 제출', j.last_hour) + stat('오늘 제출', j.today) + stat('오늘 정답', j.today_ac);
    var max = j.by_result.reduce(function (m, x) { return Math.max(m, x.count); }, 0);
    $('mon-results').innerHTML = j.by_result.length ? j.by_result.map(function (x) {
      return '<li><span>' + esc(x.label) + '</span><div class="mon-bar"><span style="width:' + (x.count / max * 100).toFixed(1) + '%"></span></div><em>' + x.count + '</em></li>';
    }).join('') : '<li class="mon-empty" style="display:block">오늘 제출이 없습니다.</li>';

    var o = d.operations;
    $('mon-ops-stats').innerHTML =
      stat('사용자', o.users) + stat('문제', o.problems + (o.problems_hidden ? ' <small class="admin-field-help">(비공개 ' + o.problems_hidden + ')</small>' : '')) +
      stat('공개 대회', o.contests) + stat('진행 중 대회', o.contests_running) + stat('DB 크기', bytes(o.db_size)) +
      stat('오늘 로그인 실패', o.login_fail_today, o.login_fail_10m >= 20 ? 'warn' : '');
    if (o.backup) {
      var ageH = (Date.now() / 1000 - o.backup.mtime) / 3600;
      $('mon-backup').innerHTML = '마지막 야간 백업: <b>' + esc(o.backup.name) + '</b> · ' + bytes(o.backup.size) + ' · ' + (ageH < 1 ? '1시간 이내' : Math.floor(ageH) + '시간 전') + ' · 보관 ' + o.backup.count + '개';
    } else {
      $('mon-backup').textContent = '/var/backups에서 야간 백업 파일을 찾을 수 없습니다.';
    }
    var qc = $('tab-queue-count');
    qc.hidden = !q.count;
    qc.textContent = q.count;
  }
  function loadStats() {
    return get('stats').then(function (d) { renderAlerts(d.alerts); renderOverview(d); });
  }

  // ---------- 채점 큐 ----------
  function loadQueue() {
    return get('queue').then(function (d) {
      var s = d.summary;
      $('queue-stats').innerHTML =
        stat('대기 중', s.count, s.oldest >= d.danger ? 'danger' : (s.oldest >= d.warn ? 'warn' : '')) +
        stat('가장 오래 기다린', s.count ? duration(s.oldest) : '-', s.oldest >= d.danger ? 'danger' : (s.oldest >= d.warn ? 'warn' : '')) +
        stat('지금 채점 중인 프로세스', d.services.judging_now, s.count && !d.services.judging_now ? 'warn' : '') +
        stat('처리량 (최근 15분)', s.per_min + '건/분') +
        stat('평균 대기 (최근 30분)', s.avg_wait === null ? '-' : duration(s.avg_wait)) +
        stat('최대 대기 (최근 30분)', s.max_wait === null ? '-' : duration(s.max_wait));
      $('queue-threshold').textContent = d.warn + '초 넘게 기다리면 노란색, ' + d.danger + '초 넘으면 빨간색으로 표시합니다.';
      $('queue-list').innerHTML = d.list.length ? d.list.map(function (x) {
        var level = x.wait >= d.danger ? 'is-danger' : (x.wait >= d.warn ? 'is-warn' : '');
        return '<tr class="' + level + '"><td><input type="checkbox" data-row="queue" value="' + x.id + '"></td><td>' + x.id + '</td><td>' + x.kind + '</td>' +
          '<td>' + userCell(x.user) + '</td><td>' + esc(x.group || '-') + '</td><td>' + (x.problem > 0 ? problemCell(x) : esc(-x.problem) + ' (데이터)') + '</td>' +
          '<td>' + contestCell(x) + '</td><td>' + esc(x.lang) + '</td><td><span class="admin-badge admin-badge--info">' + esc(x.label) + '</span></td>' +
          '<td>' + esc(x.in_date) + '</td><td><b>' + duration(x.wait) + '</b></td></tr>';
      }).join('') : empty(11, '채점을 기다리는 제출이 없습니다.');
    });
  }

  // ---------- 제출 로그 ----------
  function logQuery() {
    var fd = new FormData($('log-filter')), parts = [];
    fd.forEach(function (v, k) { if (String(v).trim() !== '') parts.push(encodeURIComponent(k) + '=' + encodeURIComponent(String(v).trim())); });
    return parts.join('&');
  }
  function logRow(x) {
    return '<tr data-id="' + x.id + '"><td><input type="checkbox" data-row="log" value="' + x.id + '"></td><td>' + x.id + '</td><td>' + esc(x.in_date) + '</td>' +
      '<td>' + userCell(x.user) + (x.nick && x.nick !== x.user ? ' <span class="mon-muted">' + esc(x.nick) + '</span>' : '') + '</td><td>' + esc(x.group || '-') + '</td>' +
      '<td>' + problemCell(x) + '</td><td>' + contestCell(x) + '</td><td>' + esc(x.lang) + '</td>' +
      '<td><span class="admin-badge ' + badgeClass(x.result) + '">' + esc(x.label) + '</span>' + (x.result > 4 && x.result < 10 && x.pass_rate ? ' <span class="mon-muted">' + Math.round(x.pass_rate * 100) + '%</span>' : '') + '</td>' +
      '<td>' + x.time + 'ms</td><td>' + bytes(x.memory * 1024) + '</td><td>' + x.length + 'B</td><td>' + esc(x.ip) + '</td>' +
      '<td><a href="../showsource.php?id=' + x.id + '" target="_blank">코드</a>' +
      (x.result === 11 ? ' · <a href="../ceinfo.php?sid=' + x.id + '" target="_blank">컴파일 오류</a>' : '') +
      (x.result >= 5 && x.result <= 10 ? ' · <a href="../reinfo.php?sid=' + x.id + '" target="_blank">실행 결과</a>' : '') + '</td></tr>';
  }
  function loadLog(reset) {
    if (reset) { state.logQuery = logQuery(); state.logMaxId = 0; state.logMinId = 0; }
    return get('log', state.logQuery).then(function (d) {
      $('log-list').innerHTML = d.list.length ? d.list.map(logRow).join('') : empty(14, '조건에 맞는 제출이 없습니다.');
      state.logMaxId = d.list.length ? d.list[0].id : 0;
      state.logMinId = d.list.length ? d.list[d.list.length - 1].id : 0;
      $('log-more').hidden = !d.more;
      $('log-info').textContent = d.list.length ? d.list.length + '건 표시' + (d.more ? ' (더 있음)' : '') : '';
    });
  }
  function pollLog() {
    if (!$('log-live').checked) return Promise.resolve();
    if (!state.logMaxId) return loadLog(false);
    return get('log', state.logQuery + (state.logQuery ? '&' : '') + 'after=' + state.logMaxId).then(function (d) {
      if (!d.list.length) return;
      var body = $('log-list');
      if (body.querySelector('.mon-empty')) body.innerHTML = '';
      body.insertAdjacentHTML('afterbegin', d.list.map(logRow).join(''));
      state.logMaxId = d.list[0].id;
      // 진행 중인 채점 결과도 바뀌므로 화면에 있는 행은 다음 전체 조회 때 갱신한다.
    });
  }

  // ---------- 대회·수행평가 ----------
  var STATE_LABEL = { running: '진행 중', upcoming: '예정', ended: '종료' };
  function loadContests() {
    return get('contests').then(function (d) {
      $('contest-pick').innerHTML = d.list.length ? d.list.map(function (c) {
        return '<li><button type="button" data-contest="' + c.id + '" class="' + (c.id === state.contest ? 'is-active' : '') + '">' +
          '<span><b>' + c.id + '</b> ' + esc(c.title) + (c.assessment ? ' <span class="admin-badge admin-badge--info">수행평가</span>' : '') + (c.defunct ? ' <span class="admin-badge admin-badge--off">비활성</span>' : '') + '</span>' +
          '<span class="admin-badge ' + (c.state === 'running' ? 'admin-badge--on' : 'admin-badge--off') + '">' + STATE_LABEL[c.state] + '</span></button></li>';
      }).join('') : '<li class="mon-empty">대회가 없습니다.</li>';
      var sel = $('access-cid'), keep = sel.value;
      sel.innerHTML = '<option value="">선택 안 함</option>' + d.list.map(function (c) { return '<option value="' + c.id + '">' + c.id + ' ' + esc(c.title) + ' (' + STATE_LABEL[c.state] + ')</option>'; }).join('');
      sel.value = keep;
      if (!state.contest) {
        var running = d.list.filter(function (c) { return c.state === 'running'; })[0];
        if (running) { state.contest = running.id; return loadContests(); }
      }
    });
  }
  function loadContestDetail() {
    if (!state.contest) return Promise.resolve();
    return get('contest', 'cid=' + state.contest).then(function (d) {
      var c = d.contest, r = d.recent10;
      var maxN = d.timeline.reduce(function (m, x) { return Math.max(m, x[0]); }, 0) || 1;
      var errSpike = r.total >= 5 && (r.ce + r.re) / r.total >= 0.5;
      $('contest-detail').innerHTML =
        '<section class="admin-card" style="margin-bottom:16px"><div class="admin-card-title">' + (c.assessment ? '수행평가' : '대회') + ' ' + c.id + ' · ' + STATE_LABEL[c.state] + '</div>' +
        '<h4 style="margin:0 0 6px">' + esc(c.title) + '</h4><p class="admin-field-help" style="margin:0 0 14px">' + esc(c.start) + ' ~ ' + esc(c.end) + (c.private ? ' · 비공개(참가자 ' + d.invited + '명 지정)' : ' · 공개') +
        ' · <a href="../contest.php?cid=' + c.id + '" target="_blank">대회 화면</a> · <a href="../contestrank.php?cid=' + c.id + '" target="_blank">순위</a> · <a href="#" data-log-contest="' + c.id + '">제출 로그</a> · <a href="#" data-access-contest="' + c.id + '">접속 이상 징후</a></p>' +
        '<div class="mon-stats">' + stat('제출한 참가자', d.participants) + stat('최근 10분 활동', d.active_10m) + stat('채점 대기', d.waiting, d.waiting ? 'warn' : '') +
        stat('최근 10분 제출', r.total) + stat('최근 10분 컴파일·런타임 오류', (r.ce + r.re), errSpike ? 'danger' : '') + '</div>' +
        (errSpike ? '<div class="mon-alert is-danger" style="margin-bottom:12px"><span>⛔ 최근 10분 제출의 절반 이상이 컴파일·런타임 오류입니다. 문제 설정(입출력, 시간 제한, 언어)을 확인하세요.</span></div>' : '') +
        '<div class="admin-field-help">분당 제출 수 (' + (c.state === 'running' ? '최근 60분' : '마지막 60분') + ', 주황 = 오류 포함)</div>' +
        '<div class="mon-timeline">' + d.timeline.map(function (x) { return '<span class="' + (x[1] ? 'has-err' : '') + '" style="height:' + (x[0] / maxN * 100) + '%" title="' + x[0] + '건' + (x[1] ? ', 오류 ' + x[1] + '건' : '') + '"></span>'; }).join('') + '</div>' +
        '<div class="mon-timeline-axis"><span>60분 전</span><span>지금</span></div></section>' +
        '<section class="admin-card" style="margin-bottom:16px"><div class="admin-card-title">문제별 현황</div><div class="admin-table-wrap"><table class="table mon-table"><thead><tr><th>문제</th><th>제목</th><th>제출</th><th>시도한 사람</th><th>맞은 사람</th><th>컴파일 오류</th><th>런타임 오류</th><th>대기</th><th>조치</th></tr></thead><tbody>' +
        (d.problems.length ? d.problems.map(function (p) {
          return '<tr><td><b>' + p.letter + '</b> <a href="../problem.php?id=' + p.problem + '" target="_blank">' + p.problem + '</a></td><td class="mon-wrap">' + esc(p.title) + '</td><td>' + p.submits + '</td><td>' + p.users + '</td><td>' + p.ac_users + '</td>' +
            '<td>' + p.ce + '</td><td>' + p.re + '</td><td>' + p.waiting + '</td><td><button type="button" class="btn btn-default" data-rejudge-problem="' + p.num + '" data-letter="' + p.letter + '" style="height:28px;min-height:28px;padding:0 10px;font-size:12px">이 문제 재채점</button></td></tr>';
        }).join('') : empty(9, '문제가 없습니다.')) + '</tbody></table></div></section>' +
        '<div class="mon-grid mon-two"><section class="admin-card"><div class="admin-card-title">결과 분포</div><ul class="mon-results">' +
        (d.by_result.length ? (function () { var mx = d.by_result[0].count; return d.by_result.map(function (x) { return '<li><span>' + esc(x.label) + '</span><div class="mon-bar"><span style="width:' + (x.count / mx * 100) + '%"></span></div><em>' + x.count + '</em></li>'; }).join(''); })() : '<li class="mon-empty" style="display:block">제출이 없습니다.</li>') +
        '</ul></section><section class="admin-card"><div class="admin-card-title">제출이 많은 참가자 (IP 2개 이상은 노란색)</div><div class="admin-table-wrap"><table class="table mon-table"><thead><tr><th>사용자</th><th>반</th><th>제출</th><th>IP 수</th><th>마지막 제출</th></tr></thead><tbody>' +
        (d.top.length ? d.top.map(function (u) { return '<tr class="' + (u.ips >= 2 ? 'is-warn' : '') + '"><td>' + userCell(u.user) + '</td><td>' + esc(u.group || '-') + '</td><td>' + u.count + '</td><td>' + u.ips + '</td><td>' + esc(u.last) + '</td></tr>'; }).join('') : empty(5, '제출이 없습니다.')) +
        '</tbody></table></div></section></div>';
    });
  }

  // ---------- 접속·이상 징후 ----------
  function loadAccess() {
    var fd = new FormData($('access-filter'));
    var cid = fd.get('cid');
    return get('access', cid ? 'cid=' + encodeURIComponent(cid) : 'hours=' + encodeURIComponent(fd.get('hours'))).then(function (d) {
      $('access-scope').textContent = d.scope + ' · ' + d.from + ' ~ ' + d.to;
      $('access-shared').innerHTML = d.shared_ip.length ? d.shared_ip.map(function (x) { return '<tr class="is-warn"><td>' + esc(x.ip) + '</td><td>' + x.count + '</td><td class="mon-wrap">' + esc(x.users) + '</td></tr>'; }).join('') : empty(3, '해당 기간에 여러 계정이 쓴 IP가 없습니다.');
      $('access-multi').innerHTML = d.multi_ip.length ? d.multi_ip.map(function (x) { return '<tr class="is-warn"><td>' + userCell(x.user) + '</td><td>' + x.count + '</td><td class="mon-wrap">' + esc(x.ips) + '</td></tr>'; }).join('') : empty(3, '해당 기간에 여러 IP를 쓴 계정이 없습니다.');
      $('access-fails').innerHTML = d.fails.length ? d.fails.map(function (x) { return '<tr class="' + (x.count >= 5 ? 'is-warn' : '') + '"><td>' + esc(x.user) + '</td><td>' + esc(x.ip) + '</td><td>' + x.count + '</td><td>' + esc(x.last) + '</td></tr>'; }).join('') : empty(4, '로그인 실패가 없습니다.');
      $('access-logins').innerHTML = d.logins.length ? d.logins.map(function (x) { return '<tr><td>' + userCell(x.user) + '</td><td>' + esc(x.ip) + '</td><td>' + esc(x.time) + '</td></tr>'; }).join('') : empty(3, '로그인 기록이 없습니다.');
      $('access-online').innerHTML = d.online.length ? d.online.map(function (x) { return '<tr><td>' + esc(x.ip) + '</td><td class="mon-wrap">' + esc(x.uri) + '</td><td>' + duration(x.ago) + ' 전</td><td><div class="mon-ua" title="' + esc(x.ua) + '">' + esc(x.ua) + '</div></td></tr>'; }).join('') : empty(4, '최근 10분 안에 접속한 방문자가 없습니다.');
    });
  }

  // ---------- 탭과 갱신 ----------
  var LOADERS = {
    overview: function () { return Promise.resolve(); },
    queue: loadQueue,
    log: function () { return state.logMaxId ? pollLog() : loadLog(true); },
    contest: function () { return loadContests().then(loadContestDetail); },
    access: function () { return Promise.resolve(); }
  };
  function refresh(full) {
    clearTimeout(state.timer);
    var jobs = [loadStats()];
    if (state.tab === 'access') { if (full) jobs.push(loadAccess()); }
    else if (state.tab === 'log' && full) jobs.push(loadLog(true));
    else jobs.push(LOADERS[state.tab]());
    return Promise.all(jobs).then(function () { setUpdated(); }).catch(function (e) { setUpdated(e.message); }).then(function () {
      if (!document.hidden) state.timer = setTimeout(function () { refresh(false); }, 10000);
    });
  }
  function showTab(tab, noLoad) {
    state.tab = tab;
    document.querySelectorAll('.mon-tabs button').forEach(function (b) { b.classList.toggle('is-active', b.getAttribute('data-tab') === tab); });
    document.querySelectorAll('.mon-panel').forEach(function (p) { p.classList.toggle('is-active', p.id === 'panel-' + tab); });
    if (history.replaceState) history.replaceState(null, '', '#' + tab);
    if (!noLoad) refresh(true);
  }
  function setLogFilter(values) {
    var f = $('log-filter');
    f.reset();
    Object.keys(values).forEach(function (k) { if (f.elements[k]) f.elements[k].value = values[k]; });
    state.logMaxId = 0;
    showTab('log');
  }

  document.querySelector('.mon-tabs').addEventListener('click', function (e) {
    var b = e.target.closest('button[data-tab]');
    if (b) showTab(b.getAttribute('data-tab'));
  });
  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-goto-tab],[data-goto-contest],[data-goto-user],[data-contest],[data-log-contest],[data-access-contest],[data-rejudge-problem],[data-act],[data-check-all]');
    if (!t) return;
    if (t.hasAttribute('data-check-all')) {
      var on = t.checked;
      document.querySelectorAll('input[data-row="' + t.getAttribute('data-check-all') + '"]').forEach(function (c) { c.checked = on; });
      return;
    }
    e.preventDefault();
    if (t.hasAttribute('data-goto-tab')) showTab(t.getAttribute('data-goto-tab'));
    else if (t.hasAttribute('data-goto-contest') || t.hasAttribute('data-contest')) {
      state.contest = parseInt(t.getAttribute('data-goto-contest') || t.getAttribute('data-contest'), 10);
      showTab('contest');
    } else if (t.hasAttribute('data-goto-user')) setLogFilter({ user: t.getAttribute('data-goto-user') });
    else if (t.hasAttribute('data-log-contest')) setLogFilter({ cid: t.getAttribute('data-log-contest') });
    else if (t.hasAttribute('data-access-contest')) { showTab('access', true); $('access-cid').value = t.getAttribute('data-access-contest'); refresh(true); }
    else if (t.hasAttribute('data-rejudge-problem')) {
      var num = parseInt(t.getAttribute('data-rejudge-problem'), 10);
      if (!confirm('대회 ' + state.contest + '의 ' + t.getAttribute('data-letter') + '번 문제 제출을 모두 재채점할까요? 채점이 끝날 때까지 결과가 "재채점 대기"로 바뀝니다.')) return;
      post('rejudge_problem', { cid: state.contest, num: num }).then(function (r) { alert(r.done + '건을 재채점 대기로 바꿨습니다.'); refresh(true); }).catch(function (err) { alert(err.message); });
    } else if (t.hasAttribute('data-act')) {
      var from = t.getAttribute('data-from'), act = t.getAttribute('data-act');
      var ids = Array.prototype.map.call(document.querySelectorAll('input[data-row="' + from + '"]:checked'), function (c) { return parseInt(c.value, 10); });
      if (!ids.length) { alert('제출을 먼저 선택하세요.'); return; }
      var msg = act === 'rejudge' ? '선택한 제출 ' + ids.length + '건을 재채점할까요? 결과가 "재채점 대기"로 바뀐 뒤 다시 채점됩니다.' : '선택한 제출 ' + ids.length + '건을 채점기에 다시 알릴까요? (결과는 바뀌지 않습니다)';
      if (!confirm(msg)) return;
      post(act, { ids: ids }).then(function (r) {
        alert(r.done + '건을 처리했습니다.' + (r.skipped ? '\n' + r.skipped + '건은 컴파일 중·채점 중에서 멈춘 제출이라 다시 호출할 수 없습니다. 재채점을 사용하세요.' : ''));
        refresh(true);
      }).catch(function (err) { alert(err.message); });
    }
  });
  $('log-filter').addEventListener('submit', function (e) { e.preventDefault(); refresh(true); });
  $('log-reset').addEventListener('click', function () { $('log-filter').reset(); refresh(true); });
  $('log-more').addEventListener('click', function () {
    var btn = this;
    btn.disabled = true;
    get('log', state.logQuery + (state.logQuery ? '&' : '') + 'before=' + state.logMinId).then(function (d) {
      $('log-list').insertAdjacentHTML('beforeend', d.list.map(logRow).join(''));
      if (d.list.length) state.logMinId = d.list[d.list.length - 1].id;
      btn.hidden = !d.more;
    }).catch(function (err) { alert(err.message); }).then(function () { btn.disabled = false; });
  });
  $('access-filter').addEventListener('submit', function (e) { e.preventDefault(); refresh(true); });
  $('mon-refresh').addEventListener('click', function () { refresh(true); });
  document.addEventListener('visibilitychange', function () { if (!document.hidden) refresh(false); });

  window.addEventListener('hashchange', function () {
    var tab = (location.hash || '').replace('#', '');
    if (LOADERS[tab] && tab !== state.tab) showTab(tab);
  });
  // 수행평가 회차 등에서 ?cid=번호#contest로 들어오면 그 대회를 바로 보여 준다.
  var initCid = parseInt((location.search.match(/[?&]cid=(\d+)/) || [])[1] || '0', 10);
  if (initCid) state.contest = initCid;
  var initial = (location.hash || '').replace('#', '');
  showTab(LOADERS[initial] ? initial : 'overview');
})();
</script>
