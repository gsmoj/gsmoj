<?php
// 관리자 첫 화면의 서버 모니터링 대시보드.
// 같은 파일이 화면 출력과 주기적 갱신용 JSON(action=stats)을 함께 처리한다.
require_once("../include/db_info.inc.php");
require_once("../include/my_func.inc.php");
if (file_exists("../lang/$OJ_LANG.php")) require_once("../lang/$OJ_LANG.php");
require_once("../include/const.inc.php");

// 서버 상태와 접속 기록은 운영 정보이므로 관리자만 볼 수 있다.
if (!isset($_SESSION[$OJ_NAME.'_'.'administrator'])) {
    if (isset($_GET['action'])) {
        http_response_code(403);
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode(array("ok" => false, "error" => "관리자만 볼 수 있습니다."), JSON_UNESCAPED_UNICODE);
        exit;
    }
    require("admin-header.php");
    echo "<div class='admin-content-page'><div class='admin-page-heading'><div><h3>서버 모니터링</h3><p>관리자만 볼 수 있는 화면입니다. 왼쪽 메뉴에서 작업을 선택하세요.</p></div></div></div>";
    exit;
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
        array("key" => "nginx", "label" => "웹 서버 (nginx)", "count" => $count(array('/^nginx$/'))),
        array("key" => "php", "label" => "PHP-FPM ".PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION, "count" => $count(array('/^php-fpm/'))),
        array("key" => "db", "label" => "데이터베이스 (MariaDB)", "count" => $count(array('/^mariadbd$/', '/^mysqld$/'))),
        array("key" => "judged", "label" => "채점 데몬 (judged)", "count" => $count(array('/^judged$/'))),
        array("key" => "docker", "label" => "Docker (채점 컨테이너)", "count" => $count(array('/^dockerd$/'))),
        array("key" => "memcached", "label" => "캐시 (memcached)", "count" => $count(array('/^memcached$/'))),
        array("key" => "cron", "label" => "예약 작업 (cron)", "count" => $count(array('/^cron$/'))),
    );
    return array("list" => $services, "judging_now" => $count(array('/^judge_client$/')));
}

function mon_judging() {
    global $judge_result;
    $one = function ($sql) { $r = pdo_query($sql); return empty($r) ? 0 : intval($r[0][0]); };
    $by_result = array();
    $rows = pdo_query("select result, count(*) from solution where problem_id>0 and in_date>=curdate() group by result order by count(*) desc");
    if (!empty($rows)) foreach ($rows as $r) {
        $code = intval($r[0]);
        $by_result[] = array("code" => $code, "label" => isset($judge_result[$code]) ? $judge_result[$code] : strval($code), "count" => intval($r[1]));
    }
    $recent = array();
    $rows = pdo_query("select solution_id, user_id, problem_id, contest_id, result, time, memory, in_date from solution where problem_id>0 order by solution_id desc limit 10");
    if (!empty($rows)) foreach ($rows as $r) {
        $code = intval($r['result']);
        $recent[] = array(
            "id" => intval($r['solution_id']),
            "user" => $r['user_id'],
            "problem" => intval($r['problem_id']),
            "contest" => intval($r['contest_id']),
            "result" => $code,
            "label" => isset($judge_result[$code]) ? $judge_result[$code] : strval($code),
            "time" => intval($r['time']),
            "memory" => intval($r['memory']),
            "in_date" => $r['in_date'],
        );
    }
    return array(
        "queue" => $one("select count(*) from solution where problem_id>0 and result<4"),
        "last_hour" => $one("select count(*) from solution where problem_id>0 and in_date>=now()-interval 1 hour"),
        "today" => $one("select count(*) from solution where problem_id>0 and in_date>=curdate()"),
        "today_ac" => $one("select count(*) from solution where problem_id>0 and in_date>=curdate() and result=4"),
        "by_result" => $by_result,
        "recent" => $recent,
    );
}

function mon_operations() {
    global $DB_NAME;
    $one = function ($sql) { $r = pdo_query($sql); return empty($r) ? 0 : intval($r[0][0]); };
    $backup = null;
    $files = glob("/var/backups/hustoj_*.tar.bz2");
    if (!empty($files)) {
        usort($files, function ($a, $b) { return filemtime($b) - filemtime($a); });
        $backup = array("name" => basename($files[0]), "size" => filesize($files[0]), "mtime" => filemtime($files[0]), "count" => count($files));
    }
    $logins = array();
    $rows = pdo_query("select user_id, ip, time from loginlog where password<>'login fail' order by time desc limit 8");
    if (!empty($rows)) foreach ($rows as $r) $logins[] = array("user" => $r['user_id'], "ip" => $r['ip'], "time" => $r['time']);
    $db_size = pdo_query("select sum(data_length+index_length) from information_schema.tables where table_schema=?", $DB_NAME);
    return array(
        "backup" => $backup,
        "users" => $one("select count(*) from users"),
        "problems" => $one("select count(*) from problem"),
        "problems_hidden" => $one("select count(*) from problem where defunct='Y'"),
        "contests" => $one("select count(*) from contest where defunct='N'"),
        "login_fail_today" => $one("select count(*) from loginlog where password='login fail' and time>=curdate()"),
        "db_size" => empty($db_size) ? 0 : intval($db_size[0][0]),
        "logins" => $logins,
    );
}

if (isset($_GET['action']) && $_GET['action'] === "stats") {
    $db_version = pdo_query("select version()");
    $db_now = pdo_query("select now()");
    header("Content-Type: application/json; charset=utf-8");
    header("Cache-Control: no-store");
    echo json_encode(array(
        "ok" => true,
        "now" => empty($db_now) ? "" : $db_now[0][0],
        "resources" => mon_resources(),
        "services" => mon_services(),
        "judging" => mon_judging(),
        "operations" => mon_operations(),
        "versions" => array(
            "php" => PHP_VERSION,
            "web" => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : "",
            "db" => empty($db_version) ? "" : $db_version[0][0],
        ),
    ), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

require("admin-header.php");
?>
<title>서버 모니터링</title>
<style>
  .mon-grid { display: grid; gap: 16px; }
  .mon-kpis { grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 16px; }
  .mon-two { grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); margin-bottom: 16px; }
  .mon-kpi .admin-card-title { margin-bottom: 6px; }
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
  .mon-results { display: grid; gap: 6px; margin: 0; padding: 0; list-style: none; }
  .mon-results li { display: grid; grid-template-columns: 120px minmax(0, 1fr) 40px; align-items: center; gap: 10px; font-size: 13px; }
  .mon-results .mon-bar { margin: 0; }
  .mon-results em { color: var(--admin-muted); font-style: normal; text-align: right; }
  .mon-table { margin: 0 !important; font-size: 13px; }
  .mon-table th, .mon-table td { white-space: nowrap; }
  .mon-empty { padding: 18px 0; color: var(--admin-muted); text-align: center; }
  .mon-updated { color: var(--admin-muted); font-size: 13px; }
  .mon-updated.is-error { color: #b91c1c; }
</style>
<div class="admin-content-page">
  <div class="admin-page-heading">
    <div>
      <h3>서버 모니터링</h3>
      <p>서버 자원, 서비스, 채점 상황을 10초마다 자동으로 갱신합니다.</p>
    </div>
    <div class="admin-heading-actions">
      <span class="mon-updated" id="mon-updated">불러오는 중…</span>
      <button type="button" class="btn btn-default" id="mon-refresh">지금 새로고침</button>
    </div>
  </div>

  <div class="mon-grid mon-kpis">
    <section class="admin-card mon-kpi"><div class="admin-card-title">CPU 부하</div><div class="mon-kpi-value" id="kpi-cpu">-</div><div class="mon-kpi-sub" id="kpi-cpu-sub"></div><div class="mon-bar" id="bar-cpu"><span style="width:0"></span></div></section>
    <section class="admin-card mon-kpi"><div class="admin-card-title">메모리</div><div class="mon-kpi-value" id="kpi-mem">-</div><div class="mon-kpi-sub" id="kpi-mem-sub"></div><div class="mon-bar" id="bar-mem"><span style="width:0"></span></div></section>
    <section class="admin-card mon-kpi"><div class="admin-card-title">디스크 (/)</div><div class="mon-kpi-value" id="kpi-disk">-</div><div class="mon-kpi-sub" id="kpi-disk-sub"></div><div class="mon-bar" id="bar-disk"><span style="width:0"></span></div></section>
    <section class="admin-card mon-kpi"><div class="admin-card-title">가동 시간</div><div class="mon-kpi-value" id="kpi-uptime">-</div><div class="mon-kpi-sub" id="kpi-uptime-sub"></div></section>
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

  <div class="mon-grid mon-two">
    <section class="admin-card">
      <div class="admin-card-title">최근 제출</div>
      <div class="admin-table-wrap">
        <table class="table mon-table">
          <thead><tr><th>번호</th><th>사용자</th><th>문제</th><th>결과</th><th>시간</th><th>메모리</th><th>제출 시각</th></tr></thead>
          <tbody id="mon-recent"></tbody>
        </table>
      </div>
    </section>
    <section class="admin-card">
      <div class="admin-card-title">최근 로그인</div>
      <div class="admin-table-wrap">
        <table class="table mon-table">
          <thead><tr><th>사용자</th><th>IP</th><th>시각</th></tr></thead>
          <tbody id="mon-logins"></tbody>
        </table>
      </div>
    </section>
  </div>
</div>
<script>
(function () {
  var timer = null;
  function $(id) { return document.getElementById(id); }
  function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function bytes(n) {
    var u = ['B', 'KB', 'MB', 'GB', 'TB'], i = 0;
    while (n >= 1024 && i < u.length - 1) { n /= 1024; i++; }
    return (i ? n.toFixed(1) : n) + u[i];
  }
  function duration(s) {
    var d = Math.floor(s / 86400), h = Math.floor(s % 86400 / 3600), m = Math.floor(s % 3600 / 60);
    return d ? d + '일 ' + h + '시간' : (h ? h + '시간 ' + m + '분' : m + '분');
  }
  function bar(id, pct) {
    var el = $(id);
    el.className = 'mon-bar' + (pct >= 90 ? ' is-danger' : (pct >= 75 ? ' is-warn' : ''));
    el.firstChild.style.width = Math.min(100, Math.max(0, pct)).toFixed(1) + '%';
  }
  function resultClass(code) { return code === 4 ? 'admin-badge--on' : (code < 4 ? 'admin-badge--info' : 'admin-badge--warn'); }

  function render(d) {
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
    $('kpi-uptime-sub').textContent = '서버 시각(DB) ' + d.now;

    $('mon-services').innerHTML = d.services.list.map(function (s) {
      var up = s.count > 0;
      return '<li class="mon-service' + (up ? '' : ' is-down') + '"><span><i class="mon-dot"></i><b>' + esc(s.label) + '</b></span>' +
        '<span class="admin-badge ' + (up ? 'admin-badge--on' : 'admin-badge--warn') + '">' + (up ? '실행 중' : '중지됨') + '</span></li>';
    }).join('');
    $('mon-versions').textContent = 'PHP ' + d.versions.php + ' · ' + d.versions.web + ' · DB ' + d.versions.db + ' · 지금 채점 중인 프로세스 ' + d.services.judging_now + '개';

    var j = d.judging;
    $('mon-judge-stats').innerHTML = [
      ['채점 대기', j.queue], ['최근 1시간 제출', j.last_hour], ['오늘 제출', j.today], ['오늘 정답', j.today_ac]
    ].map(function (x) { return '<div class="mon-stat"><span>' + x[0] + '</span><b>' + x[1] + '</b></div>'; }).join('');
    var max = j.by_result.reduce(function (m, x) { return Math.max(m, x.count); }, 0);
    $('mon-results').innerHTML = j.by_result.length ? j.by_result.map(function (x) {
      return '<li><span>' + esc(x.label) + '</span><div class="mon-bar"><span style="width:' + (x.count / max * 100).toFixed(1) + '%"></span></div><em>' + x.count + '</em></li>';
    }).join('') : '<li class="mon-empty" style="display:block">오늘 제출이 없습니다.</li>';
    $('mon-recent').innerHTML = j.recent.length ? j.recent.map(function (x) {
      return '<tr><td><a href="../status.php?top=' + x.id + '" target="_blank">' + x.id + '</a></td><td>' + esc(x.user) + '</td>' +
        '<td><a href="../problem.php?id=' + x.problem + '" target="_blank">' + x.problem + '</a>' + (x.contest ? ' <span class="admin-field-help">(대회 ' + x.contest + ')</span>' : '') + '</td>' +
        '<td><span class="admin-badge ' + resultClass(x.result) + '">' + esc(x.label) + '</span></td>' +
        '<td>' + x.time + 'ms</td><td>' + bytes(x.memory * 1024) + '</td><td>' + esc(x.in_date) + '</td></tr>';
    }).join('') : '<tr><td colspan="7" class="mon-empty">제출 기록이 없습니다.</td></tr>';

    var o = d.operations;
    $('mon-ops-stats').innerHTML = [
      ['사용자', o.users], ['문제', o.problems + (o.problems_hidden ? ' <small class="admin-field-help">(비공개 ' + o.problems_hidden + ')</small>' : '')],
      ['공개 대회', o.contests], ['DB 크기', bytes(o.db_size)], ['오늘 로그인 실패', o.login_fail_today]
    ].map(function (x) { return '<div class="mon-stat"><span>' + x[0] + '</span><b>' + x[1] + '</b></div>'; }).join('');
    if (o.backup) {
      var ageH = (Date.now() / 1000 - o.backup.mtime) / 3600;
      $('mon-backup').innerHTML = '마지막 야간 백업: <b>' + esc(o.backup.name) + '</b> · ' + bytes(o.backup.size) + ' · ' + (ageH < 1 ? '1시간 이내' : Math.floor(ageH) + '시간 전') +
        ' · 보관 ' + o.backup.count + '개' + (ageH > 30 ? ' <span class="admin-badge admin-badge--warn">하루 넘게 새 백업이 없습니다</span>' : '');
    } else {
      $('mon-backup').innerHTML = '<span class="admin-badge admin-badge--warn">/var/backups에서 야간 백업 파일을 찾을 수 없습니다</span>';
    }
    $('mon-logins').innerHTML = o.logins.length ? o.logins.map(function (x) {
      return '<tr><td>' + esc(x.user) + '</td><td>' + esc(x.ip) + '</td><td>' + esc(x.time) + '</td></tr>';
    }).join('') : '<tr><td colspan="3" class="mon-empty">로그인 기록이 없습니다.</td></tr>';
  }

  function refresh() {
    clearTimeout(timer);
    fetch('monitor.php?action=stats', { credentials: 'same-origin', cache: 'no-store' }).then(function (r) { return r.json(); }).then(function (d) {
      if (!d.ok) throw new Error(d.error || '불러오지 못했습니다.');
      render(d);
      var el = $('mon-updated');
      el.className = 'mon-updated';
      el.textContent = '마지막 갱신 ' + new Date().toLocaleTimeString('ko-KR');
    }).catch(function (e) {
      var el = $('mon-updated');
      el.className = 'mon-updated is-error';
      el.textContent = '갱신 실패: ' + e.message;
    }).then(function () {
      if (!document.hidden) timer = setTimeout(refresh, 10000);
    });
  }
  document.addEventListener('visibilitychange', function () { if (!document.hidden) refresh(); });
  $('mon-refresh').addEventListener('click', refresh);
  refresh();
})();
</script>
