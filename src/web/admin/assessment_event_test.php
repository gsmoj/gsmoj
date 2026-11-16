<?php
// 수행평가 부정행위 감지 테스트 화면(관리자 전용).
// 1) 서버 환경(감지 기록 테이블, 시각, 프록시 Host)을 확인하고, 2) 이 브라우저에서 감지 기록 주소로 실제 요청을 보내 보고,
// 3) 회차와 학생을 고르면 그 학생의 감지 기록이 남는 조건을 단계별로 확인한다. 진단은 기록을 남기지 않는다.
require("admin-header.php");
require_once("assessment_lib.php");
if (!isset($_SESSION[$OJ_NAME.'_'.'administrator'])) {
    echo "<div class='admin-content-page'><div class='alert alert-danger'>관리자만 사용할 수 있습니다.</div></div>";
    exit(1);
}
$POP_UPED = true;   // 테이블이 없을 때 pdo_query가 화면 위에 SQL 오류를 찍지 않게 한다. 아래에서 직접 안내한다.

$cid = intval($_GET['cid'] ?? 0);
$view_user = trim(strval($_GET['user'] ?? ""));
$checks = array();
function aet_row($ok, $label, $detail) {
    $badge = $ok === true ? "<span class='admin-badge admin-badge--on'>정상</span>" : ($ok === false ? "<span class='admin-badge admin-badge--off'>문제</span>" : "<span class='admin-badge admin-badge--info'>참고</span>");
    return "<tr><td>".$badge."</td><td>".asm_h($label)."</td><td class='aet-detail'>".$detail."</td></tr>";
}

// 1. 서버 환경
$env = array();
$cols = pdo_query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='assessment_event'");
$cols = is_array($cols) ? array_map(function ($r) { return $r[0]; }, $cols) : array();
$need = array("event_id", "contest_id", "num", "user_id", "event_type", "amount", "line_count", "place", "ip", "created_at");
$missing = array_diff($need, $cols);
if (!$cols) $env[] = aet_row(false, "감지 기록 테이블", "assessment_event 테이블이 없습니다. <a href='update_db.php'>DB 업데이트</a>를 실행하세요.");
else if ($missing) $env[] = aet_row(false, "감지 기록 테이블", "빠진 열: ".asm_h(implode(", ", $missing)).". <a href='update_db.php'>DB 업데이트</a>를 실행하세요.");
else {
    $total = pdo_query("SELECT count(*), max(created_at) FROM assessment_event");
    $env[] = aet_row(true, "감지 기록 테이블", "전체 ".intval($total[0][0])."건, 마지막 기록 ".asm_h($total[0][1] ? $total[0][1] : "없음"));
}
$db_now = pdo_query("SELECT NOW()");
$db_now = is_array($db_now) ? $db_now[0][0] : "";
$php_now = date("Y-m-d H:i:s");
$skew = $db_now ? abs(strtotime($db_now) - time()) : 0;
$env[] = aet_row($skew <= 60, "서버 시각", "PHP ".asm_h($php_now)." / DB ".asm_h($db_now).($skew > 60 ? " — ".$skew."초 차이. 회차 진행 여부와 1분 제한 판단이 어긋날 수 있습니다." : ""));
$env[] = aet_row(null, "요청 Host", "Host: ".asm_h($_SERVER['HTTP_HOST'] ?? "")
    .(empty($_SERVER['HTTP_X_FORWARDED_HOST']) ? "" : " / X-Forwarded-Host: ".asm_h($_SERVER['HTTP_X_FORWARDED_HOST']))
    ." — 아래 전송 시험에서 브라우저 Origin과 비교합니다.");

// 2. 회차 목록(진행 중·최근 수행평가)
$contests = pdo_query("SELECT c.contest_id, c.title, c.start_time, c.end_time, s.group_name,
        c.start_time<=NOW() AND c.end_time>NOW() AS running
    FROM contest c LEFT JOIN assessment_session s ON s.contest_id=c.contest_id
    WHERE (c.contest_type & 512)<>0 AND c.defunct='N' ORDER BY running DESC, c.end_time DESC LIMIT 50");
$contests = is_array($contests) ? $contests : array();

// 3. 학생 진단: assessment_monitor.php(스크립트 출력)와 assessment_event.php(기록)의 조건을 DB 기준으로 다시 확인한다.
$diag = array();
$verdict = null;
$events = array();
if ($cid > 0) {
    $c = pdo_query("SELECT contest_id, title, start_time, end_time, private, defunct, contest_type, user_id FROM contest WHERE contest_id=?", $cid);
    $c = is_array($c) && $c ? $c[0] : null;
    $ok = true;
    if (!$c) { $diag[] = aet_row(false, "회차", "회차를 찾을 수 없습니다."); $ok = false; }
    else {
        $is_asm = (intval($c['contest_type']) & 512) != 0;
        $diag[] = aet_row($is_asm, "수행평가 모드", $is_asm ? "contest_type에 수행평가(512)가 켜져 있습니다." : "수행평가 모드가 아닌 대회라 감지 스크립트가 나오지 않습니다.");
        $diag[] = aet_row($c['defunct'] === 'N', "회차 사용 여부", $c['defunct'] === 'N' ? "사용 중" : "비활성(defunct=Y)이라 기록하지 않습니다.");
        $st = strtotime($c['start_time']);
        $et = strtotime($c['end_time']);
        $running = time() >= $st && time() < $et;
        $diag[] = aet_row($running, "진행 시간", asm_h($c['start_time']." ~ ".$c['end_time']).($running ? " (진행 중)" : (time() < $st ? " — 아직 시작하지 않았습니다." : " — 이미 끝났습니다.")." 진행 중에만 기록합니다."));
        $ok = $ok && $is_asm && $c['defunct'] === 'N' && $running;
        $sess = pdo_query("SELECT group_name FROM assessment_session WHERE contest_id=?", $cid);
        $group = is_array($sess) && $sess ? (string)$sess[0][0] : "";
        $diag[] = aet_row(null, "대상 반", $group !== "" ? asm_h($group) : "지정 안 됨(참가 권한 c".$cid."로 판단)");

        if ($view_user !== "") {
            $u = pdo_query("SELECT user_id, defunct, group_name FROM users WHERE user_id=?", $view_user);
            $u = is_array($u) && $u ? $u[0] : null;
            if (!$u) { $diag[] = aet_row(false, "학생 계정", "아이디 ".asm_h($view_user)."를 찾을 수 없습니다."); $ok = false; }
            else {
                $uid = $u['user_id'];
                $dg = pdo_query("SELECT object_type FROM datagsm_user WHERE user_id=?", $uid);
                $dg = is_array($dg) && $dg ? $dg[0][0] : "";
                $diag[] = aet_row($u['defunct'] === 'N', "학생 계정", asm_h($uid).($dg !== "" ? " (DataGSM 연결: ".asm_h($dg).")" : " (DataGSM 연결 없음)").($u['defunct'] === 'N' ? "" : " — 사용 중지된 계정입니다."));
                $privs = pdo_query("SELECT rightstr FROM privilege WHERE user_id=? AND rightstr IN ('administrator', ?, ?)", $uid, "m".$cid, "c".$cid);
                $privs = is_array($privs) ? array_map(function ($r) { return $r[0]; }, $privs) : array();
                $manager = in_array("administrator", $privs, true) || in_array("m".$cid, $privs, true);
                $diag[] = aet_row(!$manager, "관리자 제외", $manager
                    ? "이 계정은 ".(in_array("administrator", $privs, true) ? "관리자" : "이 회차의 관리자(만든 교사)")."라서 감지 스크립트를 내보내지 않고 기록도 하지 않습니다. 학생 계정으로 시험하세요."
                    : "관리자·회차 관리자가 아닙니다.");
                // can_access_assessment()와 같은 순서로 확인한다.
                if ($group !== "") {
                    $in = (string)$u['group_name'] === $group;
                    $diag[] = aet_row($in, "참가 권한", $in ? "학생 반(".asm_h($u['group_name']).")이 대상 반과 같습니다." : "학생 반은 '".asm_h($u['group_name'])."'이고 대상 반은 '".asm_h($group)."'입니다. 입장할 수 없어 기록되지 않습니다.");
                } else {
                    $in = in_array("c".$cid, $privs, true);
                    $diag[] = aet_row($in, "참가 권한", $in ? "참가 권한 c".$cid."가 있습니다." : "참가 권한 c".$cid."가 없습니다.");
                }
                $ok = $ok && $u['defunct'] === 'N' && !$manager && $in;
                $diag[] = aet_row(null, "세션 조건", "참가 세션(c".$cid.")은 학생이 회차 화면이나 문제 화면(problem.php?cid=".$cid."&amp;pid=…)을 열 때 들어갑니다. 일반 문제 주소(problem.php?id=…)에서는 감지하지 않습니다.");
                $events = pdo_query("SELECT event_type, amount, line_count, place, num, ip, created_at FROM assessment_event WHERE contest_id=? AND user_id=? ORDER BY event_id DESC LIMIT 30", $cid, $uid);
                $events = is_array($events) ? $events : array();
                $verdict = $ok;
            }
        } else {
            $events = pdo_query("SELECT user_id, event_type, amount, line_count, place, num, ip, created_at FROM assessment_event WHERE contest_id=? ORDER BY event_id DESC LIMIT 30", $cid);
            $events = is_array($events) ? $events : array();
        }
    }
}
$names = array("leave" => "화면 이탈", "return" => "화면 복귀", "paste" => "붙여넣기", "burst" => "코드 급증", "copy" => "복사");
?>
<title>감지 테스트</title>
<style>
  .aet-table td { vertical-align: middle !important; }
  .aet-table td:first-child { width: 70px; white-space: nowrap; }
  .aet-table td:nth-child(2) { width: 160px; white-space: nowrap; font-weight: 700; }
  .aet-detail { white-space: normal; }
  .aet-form { display: flex; flex-wrap: wrap; align-items: end; gap: 10px; }
  .aet-form label { display: grid; gap: 4px; margin: 0; color: var(--admin-muted); font-size: 12px; font-weight: 600; }
  .aet-form select, .aet-form input { width: auto !important; min-width: 200px; }
  .aet-log { margin: 10px 0 0; padding: 10px 12px; border-radius: 8px; background: #f8fafc; white-space: pre-wrap; font-size: 12px; min-height: 40px; }
</style>
<div class="admin-content-page">
  <div class="admin-page-heading">
    <div>
      <h3>수행평가 감지 테스트</h3>
      <p>부정행위 감지 기록이 남지 않을 때 원인을 찾습니다. 이 화면의 확인은 감지 기록을 남기지 않습니다.</p>
    </div>
    <a class="btn btn-default" href="assessment_list.php">수행평가 목록</a>
  </div>

  <section class="admin-card" style="margin-bottom:16px">
    <div class="admin-card-title">1. 서버 환경</div>
    <div class="admin-table-wrap"><table class="table aet-table"><?php echo implode("", $env)?></table></div>
  </section>

  <section class="admin-card" style="margin-bottom:16px">
    <div class="admin-card-title">2. 브라우저 전송 시험</div>
    <p class="admin-field-help">이 브라우저에서 감지 기록 주소(assessment_event.php)로 학생 화면과 같은 방식의 요청을 보냅니다. 서버는 다른 사이트 요청 차단(Origin)과 DB 쓰기를 확인한 뒤 시험 행을 지웁니다.</p>
    <button type="button" class="btn btn-primary btn-sm" id="aet-send">전송 시험</button>
    <div class="aet-log" id="aet-log">아직 보내지 않았습니다.</div>
  </section>

  <section class="admin-card" style="margin-bottom:16px">
    <div class="admin-card-title">3. 학생 진단</div>
    <form method="get" class="aet-form">
      <label>회차
        <select name="cid" class="form-control">
          <option value="0">회차 선택</option>
          <?php foreach ($contests as $r) { ?>
          <option value="<?php echo intval($r['contest_id'])?>" <?php if (intval($r['contest_id']) === $cid) echo "selected"?>><?php echo intval($r['contest_id'])?>. <?php echo asm_h(html_entity_decode((string)$r['title'], ENT_QUOTES, 'UTF-8'))?><?php echo intval($r['running']) ? " (진행 중)" : ""?></option>
          <?php } ?>
        </select>
      </label>
      <label>학생 아이디 <input type="text" name="user" class="form-control" value="<?php echo asm_h($view_user)?>" placeholder="비우면 회차 전체 기록"></label>
      <button type="submit" class="btn btn-primary btn-sm">확인</button>
    </form>
    <?php if ($cid > 0) { ?>
    <div class="admin-table-wrap" style="margin-top:12px"><table class="table aet-table"><?php echo implode("", $diag)?></table></div>
    <?php if ($verdict !== null) { ?>
    <div class="alert <?php echo $verdict ? "alert-success" : "alert-danger"?>" style="margin:10px 0 0">
      <?php echo $verdict
        ? "서버 조건으로는 이 학생의 감지 기록이 남아야 합니다. 그래도 기록이 없으면 학생 브라우저 개발자 도구 Network 탭에서 assessment_event.php 응답의 X-GS-Event 헤더를 확인하세요(origin·no-access·not-running·db 등)."
        : "위에서 '문제'로 표시된 조건 때문에 이 학생의 감지 기록이 남지 않습니다."?>
    </div>
    <?php } ?>
    <div class="admin-card-title" style="margin-top:16px">최근 감지 기록 (<?php echo count($events)?>건, 최대 30건)</div>
    <div class="admin-table-wrap"><table class="table">
      <tr><?php if ($view_user === "") echo "<td>아이디</td>"; ?><td>종류</td><td>양</td><td>줄</td><td>위치</td><td>문제</td><td>IP</td><td>시각</td></tr>
      <?php foreach ($events as $e) {
          echo "<tr>".($view_user === "" ? "<td>".asm_h($e['user_id'])."</td>" : "")
            ."<td>".asm_h($names[$e['event_type']] ?? $e['event_type'])."</td><td>".intval($e['amount'])."</td><td>".intval($e['line_count'])."</td>"
            ."<td>".asm_h($e['place'])."</td><td>".(intval($e['num']) >= 0 ? chr(65 + intval($e['num'])) : "-")."</td><td>".asm_h($e['ip'])."</td><td>".asm_h($e['created_at'])."</td></tr>";
      } if (!$events) echo "<tr><td colspan='8' class='admin-field-help' style='text-align:center'>기록이 없습니다.</td></tr>"; ?>
    </table></div>
    <?php } ?>
  </section>
</div>
<script>
(function(){
  var log = document.getElementById("aet-log");
  document.getElementById("aet-send").addEventListener("click", function(){
    log.textContent = "보내는 중…";
    var data = new FormData();
    data.append("test", "1");
    var lines = ["sendBeacon 지원: " + (navigator.sendBeacon ? "예" : "아니오 (fetch로 대신 보냅니다)")];
    fetch("../assessment_event.php", {method: "POST", body: data, credentials: "same-origin"}).then(function(res){
      lines.push("HTTP " + res.status + ", X-GS-Event: " + (res.headers.get("X-GS-Event") || "(없음)"));
      return res.text();
    }).then(function(text){
      try {
        var r = JSON.parse(text);
        var reasons = {ok: "정상: 학생 화면의 요청도 서버까지 도착하고 DB에 기록할 수 있습니다.", origin: "Origin 차단: 브라우저 주소의 호스트와 서버가 받은 Host가 다릅니다. 역방향 프록시의 Host 전달 설정을 확인하세요.", db: "DB 쓰기 실패: assessment_event 테이블과 DB 권한을 확인하세요.", login: "로그인 세션이 요청에 실리지 않았습니다. 쿠키 설정을 확인하세요."};
        if (r.origin) lines.push("브라우저 Origin: " + (r.origin.origin || "(보내지 않음)") + " / 서버 Host: " + (r.origin.hosts || []).join(", "));
        lines.push(reasons[r.result] || ("결과: " + r.result));
      } catch (e) {
        lines.push("JSON이 아닌 응답입니다. 서버에 이 변경이 배포되었는지 확인하세요.");
        lines.push(text.slice(0, 300));
      }
      log.textContent = lines.join("\n");
    }).catch(function(e){
      lines.push("요청 실패: " + e);
      log.textContent = lines.join("\n");
    });
  });
})();
</script>
