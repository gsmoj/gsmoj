<?php
// 수행평가 만들기·수정·복사와 반별 회차 관리.
// 저장 요청(POST)은 화면을 그리기 전에 처리하고, 결과 메시지를 세션에 담아 같은 화면으로 돌아간다.
require_once("../include/db_info.inc.php");
require_once("../include/my_func.inc.php");
if (file_exists("../lang/$OJ_LANG.php")) require_once("../lang/$OJ_LANG.php");
require_once("assessment_lib.php");
$MSG_ASSESSMENT = isset($MSG_ASSESSMENT) ? $MSG_ASSESSMENT : "수행평가";
$MSG_CLASS = isset($MSG_CLASS) ? $MSG_CLASS : "반";

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$a = $id ? asm_get($id) : null;

function asm_back($id, $ok, $msg) {
    global $OJ_NAME;
    $_SESSION[$OJ_NAME.'_'.'asm_flash'] = array($ok, $msg);
    header("Location: assessment_edit.php".($id ? "?id=".intval($id) : ""));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['action'])) {
    if (!asm_can_use()) exit(1);
    require_once("../include/check_post_key.php");
    $action = $_POST['action'];

    if ($action === "save") {
        // 삭제됐거나 없는 수행평가 번호로 저장하면 새로 만들지 않고 오류를 돌려준다.
        if ($id && !$a) asm_back(0, false, "저장할 ".$MSG_ASSESSMENT."를 찾을 수 없습니다. 삭제되었을 수 있습니다.");
        if ($a && !asm_can_manage($a)) asm_back($id, false, "만든 사람과 관리자만 수정할 수 있습니다.");
        $title = trim(isset($_POST['title']) ? $_POST['title'] : "");
        if ($title === "" || mb_strlen($title, "UTF-8") > 200) asm_back($id, false, "제목을 200자 이내로 입력하세요.");
        $settings = array();
        foreach (asm_settings(null) as $k => $v) $settings[$k] = max(0, min(100000, intval(isset($_POST[$k]) ? $_POST[$k] : $v)));
        $desc = isset($_POST['description']) ? $_POST['description'] : "";
        if (!$a) {
            $id = intval(pdo_query("insert into assessment(title,description,owner,settings,created_at,defunct) values(?,?,?,?,now(),'N')", $title, $desc, asm_me(), json_encode($settings)));
            asm_back($id, true, "새 ".$MSG_ASSESSMENT."를 만들었습니다. 아래에서 문제와 ".$MSG_CLASS."별 회차를 추가하세요.");
        }
        pdo_query("update assessment set title=?, description=?, settings=? where assessment_id=?", $title, $desc, json_encode($settings), $id);
        asm_back($id, true, "저장했습니다.");
    }

    if ($action === "copy") {
        if (!$a) asm_back(0, false, "복사할 ".$MSG_ASSESSMENT."를 찾을 수 없습니다.");
        $new = intval(pdo_query("insert into assessment(title,description,owner,settings,created_at,defunct) values(?,?,?,?,now(),'N')",
            $a['title']." (복사)", (string)$a['description'], asm_me(), (string)$a['settings']));
        // 복사본의 문제를 고쳐도 원본이 바뀌지 않도록 문제를 공유하지 않고 채점 데이터까지 전용 문제로 복제한다.
        $pids = array();
        $failed = array();
        foreach (asm_problems($id) as $p) {
            if ($p['title'] === null) continue;
            list($clone, $ok) = asm_clone_problem(intval($p['problem_id']), asm_me());
            $pids[] = $clone;
            if (!$ok) $failed[] = $clone;
        }
        asm_save_problems($new, $pids);
        if ($failed) asm_back($new, false, "'".$a['title']."'를 복사했지만 문제 ".implode(", ", $failed)."번의 채점 데이터를 복사하지 못했습니다. 채점 데이터를 확인하세요.");
        asm_back($new, true, "'".$a['title']."'를 복사했습니다. 문제는 채점 데이터까지 복제되었고, 회차는 새로 추가하세요.");
    }

    if (!$a || !asm_can_manage($a)) asm_back($id, false, "만든 사람과 관리자만 회차를 관리할 수 있습니다.");
    $cid = isset($_POST['cid']) ? intval($_POST['cid']) : 0;
    $own_session = function ($cid) use ($id) {
        $r = pdo_query("select group_name from assessment_session where assessment_id=? and contest_id=?", $id, $cid);
        return empty($r) ? null : $r[0][0];
    };

    if ($action === "move_problem") {
        $pids = array_map(function ($r) { return intval($r['problem_id']); }, asm_problems($id));
        $i = array_search(isset($_POST['pid']) ? intval($_POST['pid']) : 0, $pids, true);
        if ($i === false) asm_back($id, false, "문제를 찾을 수 없습니다.");
        $j = $i + (isset($_POST['dir']) && $_POST['dir'] === "down" ? 1 : -1);
        if (!isset($pids[$j])) asm_back($id, false, "더 옮길 수 없습니다.");
        list($pids[$i], $pids[$j]) = array($pids[$j], $pids[$i]);
        asm_save_problems($id, $pids);
        asm_back($id, true, "문제 순서를 바꾸고 시작 전 회차 ".asm_sync_session_problems($id)."개에 반영했습니다.");
    }
    if ($action === "delete_problem") {
        $err = asm_remove_problem($a, isset($_POST['pid']) ? intval($_POST['pid']) : 0);
        asm_back($id, $err === "", $err === "" ? "문제를 삭제했습니다." : $err);
    }

    if ($action === "add_session") {
        $group = trim(isset($_POST['group']) ? $_POST['group'] : "");
        $start = asm_valid_datetime(isset($_POST['start']) ? $_POST['start'] : "");
        $end = asm_valid_datetime(isset($_POST['end']) ? $_POST['end'] : "");
        if ($group === "") asm_back($id, false, $MSG_CLASS."을 고르세요.");
        if (!$start || !$end || $start >= $end) asm_back($id, false, "시작·끝 시각을 확인하세요. 끝은 시작보다 늦어야 합니다.");
        if (count(asm_problems($id)) == 0) asm_back($id, false, "먼저 문제를 추가하세요.");
        $members = count(asm_group_members($group));
        if ($members == 0) asm_back($id, false, "'".$group."' ".$MSG_CLASS."에 학생이 없습니다. 사용자 → ".$MSG_CLASS." 지정에서 먼저 지정하세요.");
        list($new_cid, $added) = asm_create_session($a, $group, $start, $end);
        asm_back($id, true, "'".$group."' 회차(".$MSG_ASSESSMENT." ".$new_cid.")를 만들고 학생 ".$added."명을 참가자로 지정했습니다.");
    }
    if ($action === "update_time") {
        $group = $own_session($cid);
        if ($group === null) asm_back($id, false, "회차를 찾을 수 없습니다.");
        $start = asm_valid_datetime(isset($_POST['start']) ? $_POST['start'] : "");
        $end = asm_valid_datetime(isset($_POST['end']) ? $_POST['end'] : "");
        if (!$start || !$end || $start >= $end) asm_back($id, false, "시작·끝 시각을 확인하세요.");
        // 시작한 회차의 시작 시각은 바꿀 수 없고, 끝난 회차는 관리자만 끝 시각을 옮겨 다시 열 수 있다.
        $cur = pdo_query("select start_time, end_time, start_time<=now() as started, end_time<=now() as ended from contest where contest_id=?", $cid);
        if (empty($cur)) asm_back($id, false, "회차를 찾을 수 없습니다.");
        $cur = $cur[0];
        if (intval($cur['started'])) {
            if (substr($start, 0, 16) !== substr((string)$cur['start_time'], 0, 16)) asm_back($id, false, "이미 시작한 회차의 시작 시각은 바꿀 수 없습니다.");
            $start = $cur['start_time'];
        }
        if (intval($cur['ended']) && !asm_is_admin()) asm_back($id, false, "끝난 회차의 시간은 관리자만 바꿀 수 있습니다.");
        pdo_query("update contest set start_time=?, end_time=? where contest_id=?", $start, $end, $cid);
        asm_back($id, true, "'".$group."' 회차 시간을 바꿨습니다.");
    }
    if ($action === "sync") {
        $group = $own_session($cid);
        if ($group === null) asm_back($id, false, "회차를 찾을 수 없습니다.");
        $added = asm_sync_participants($cid, $group);
        asm_back($id, true, "'".$group."' 회차에 새로 들어온 학생 ".$added."명을 참가자로 추가했습니다.");
    }
    if ($action === "delete_session") {
        $err = asm_delete_session($a, $cid);
        asm_back($id, $err === "", $err === "" ? "회차를 삭제했습니다." : $err);
    }
    if ($action === "delete") {
        $has = pdo_query("select count(*) from assessment_session where assessment_id=?", $id);
        if (!empty($has) && intval($has[0][0]) > 0) asm_back($id, false, "회차가 있는 ".$MSG_ASSESSMENT."는 삭제할 수 없습니다. 회차를 먼저 삭제하세요.");
        pdo_query("update assessment set defunct='Y' where assessment_id=?", $id);
        $_SESSION[$OJ_NAME.'_'.'asm_flash'] = array(true, "'".$a['title']."'를 삭제했습니다.");
        header("Location: assessment_list.php");
        exit;
    }
    asm_back($id, false, "알 수 없는 요청입니다.");
}

require("admin-header.php");
if (!asm_can_use()) {
    echo "<div class='admin-content-page'><div class='alert alert-danger'>관리자, 교사, 대회 생성자만 사용할 수 있습니다.</div></div>";
    exit(1);
}
if ($id && !$a) {
    echo "<div class='admin-content-page'><div class='alert alert-danger'>".$MSG_ASSESSMENT."를 찾을 수 없습니다. <a href='assessment_list.php'>목록으로</a></div></div>";
    exit(1);
}
$flash = isset($_SESSION[$OJ_NAME.'_'.'asm_flash']) ? $_SESSION[$OJ_NAME.'_'.'asm_flash'] : null;
unset($_SESSION[$OJ_NAME.'_'.'asm_flash']);
$can = !$a || asm_can_manage($a);
$problems = $a ? asm_problems($id) : array();
// 관리자가 대신 추가한 전용 문제의 권한은 다음 로그인 전까지 세션에 없으므로, 만든 사람이 이 화면을 열 때 싣는다.
if ($a && $a['owner'] === asm_me()) {
    foreach ($problems as $p) if ($p['defunct'] === 'A') $_SESSION[$OJ_NAME.'_'."p".intval($p['problem_id'])] = true;
}
$sessions =$a ? asm_sessions($id) : array();
$settings = asm_settings($a);
$groups = pdo_query("select group_name, count(*) from users where defunct='N' and group_name<>'' group by group_name order by group_name");
$used_groups = array_map(function ($s) { return $s['group_name']; }, $sessions);
$STATE = array("running" => array("진행 중", "admin-badge--on"), "upcoming" => array("예정", "admin-badge--info"), "ended" => array("종료", "admin-badge--off"));
function asm_dt($v) { return str_replace(" ", "T", substr((string)$v, 0, 16)); }
?>
<title><?php echo $a ? asm_h($a['title']) : "새 ".$MSG_ASSESSMENT?></title>
<style>
  .asm-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 14px 18px; }
  .asm-field { display: grid; gap: 6px; }
  .asm-field label { margin: 0; font-weight: 700; }
  .asm-wide { grid-column: 1 / -1; }
  .asm-thresholds { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
  .asm-thresholds label { display: grid; gap: 4px; margin: 0; color: var(--admin-muted); font-size: 12px; font-weight: 600; }
  .asm-ptable td, .asm-ptable th, .asm-stable td, .asm-stable th { vertical-align: middle !important; white-space: nowrap; }
  .asm-ptable .asm-ptitle { width: 100%; min-width: 200px; white-space: normal; }
  .asm-pactions { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; }
  .asm-pactions form { margin: 0; }
  .asm-card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
  .asm-card-head .admin-card-title { margin-bottom: 0; }
  .asm-stable form { display: inline-flex; flex-wrap: wrap; align-items: center; gap: 6px; margin: 0; }
  .asm-stable input[type=datetime-local] { width: 190px !important; height: 30px !important; min-height: 30px !important; font-size: 12px !important; }
  .asm-add { display: flex; flex-wrap: wrap; align-items: end; gap: 10px; margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--admin-border); }
  .asm-add label { display: grid; gap: 4px; margin: 0; color: var(--admin-muted); font-size: 12px; font-weight: 600; }
  .asm-add select, .asm-add input { width: auto !important; }
  .asm-readonly { padding: 12px 14px; border-radius: 10px; background: #f8fafc; color: var(--admin-muted); }
  @media (max-width: 900px) { .asm-grid, .asm-thresholds { grid-template-columns: minmax(0, 1fr); } }
</style>
<div class="admin-content-page">
  <div class="admin-page-heading">
    <div>
      <h3><?php echo $a ? asm_h($a['title']) : "새 ".$MSG_ASSESSMENT?></h3>
      <p><?php echo $a ? "만든 사람 ".asm_h($a['owner'])." · ".asm_h($a['created_at']).($a['published_at'] ? " · 공개됨 ".asm_h($a['published_at']) : " · 비공개") : "제목을 정해 저장한 뒤 문제와 ".$MSG_CLASS."별 회차를 추가합니다."?></p>
    </div>
    <div class="admin-heading-actions">
      <a class="btn btn-default" href="assessment_list.php">목록</a>
      <?php if ($a) { ?>
      <form method="post" action="assessment_edit.php?id=<?php echo $id?>" style="margin:0" onsubmit="return confirm('이 <?php echo $MSG_ASSESSMENT?>의 문제(채점 데이터 포함)와 설정을 복제해 내 <?php echo $MSG_ASSESSMENT?>로 만들까요?')">
        <?php require("../include/set_post_key.php"); ?><input type="hidden" name="action" value="copy">
        <button type="submit" class="btn btn-default">복사해서 새로 만들기</button>
      </form>
      <?php } ?>
    </div>
  </div>

  <?php if ($flash) echo "<div class='alert ".($flash[0] ? "alert-success" : "alert-danger")."'>".asm_h($flash[1])."</div>"; ?>
  <?php if (!$can) echo "<div class='alert alert-info'>다른 교사의 ".$MSG_ASSESSMENT."입니다. 내용을 보고 복사할 수 있으며, 수정·회차 관리·결과는 만든 사람과 관리자만 할 수 있습니다.</div>"; ?>

  <form method="post" action="assessment_edit.php<?php echo $id ? "?id=".$id : ""?>" class="admin-card" style="max-width:none">
    <?php require("../include/set_post_key.php"); ?>
    <input type="hidden" name="action" value="save">
    <div class="asm-grid">
      <div class="asm-field asm-wide">
        <label for="asm-title">제목</label>
        <input type="text" id="asm-title" name="title" maxlength="200" required value="<?php echo $a ? asm_h($a['title']) : ""?>" placeholder="예: 1학기 2차 수행평가 - 반복문" <?php if (!$can) echo "readonly"?>>
      </div>
      <div class="asm-field asm-wide">
        <label for="asm-desc">안내문 <span class="admin-field-help">(회차 화면에 보이는 설명, 회차를 만들 때 복사됩니다)</span></label>
        <textarea id="asm-desc" name="description" rows="4" <?php if (!$can) echo "readonly"?>><?php echo $a ? asm_h($a['description']) : ""?></textarea>
      </div>
      <div class="asm-field asm-wide">
        <label>부정행위 감지 기준 <span class="admin-field-help">(이 값 이상이면 의심으로 표시, 미만도 기록)</span></label>
        <div class="asm-thresholds">
          <label>화면 이탈(초) <input type="number" name="leave_seconds" min="0" value="<?php echo intval($settings['leave_seconds'])?>" <?php if (!$can) echo "readonly"?>></label>
          <label>붙여넣기(글자) <input type="number" name="paste_chars" min="0" value="<?php echo intval($settings['paste_chars'])?>" <?php if (!$can) echo "readonly"?>></label>
          <label>급격한 증가(글자/2초) <input type="number" name="burst_chars" min="0" value="<?php echo intval($settings['burst_chars'])?>" <?php if (!$can) echo "readonly"?>></label>
          <label>복사(글자) <input type="number" name="copy_chars" min="0" value="<?php echo intval($settings['copy_chars'])?>" <?php if (!$can) echo "readonly"?>></label>
        </div>
      </div>
    </div>
    <?php if ($can) { ?>
    <div class="admin-form-actions"><button type="submit" class="btn btn-primary"><?php echo $a ? "저장" : "만들기"?></button></div>
    <?php } ?>
  </form>

  <?php if ($a) { ?>
  <section class="admin-card" style="margin-bottom:16px">
    <div class="asm-card-head">
      <div class="admin-card-title">문제 (<?php echo count($problems)?>개)</div>
      <?php if ($can) { ?><a class="btn btn-primary btn-sm" href="problem_add_page.php?assessment=<?php echo $id?>">문제 추가</a><?php } ?>
    </div>
    <div class="admin-table-wrap">
      <table class="table asm-ptable">
        <thead><tr><th>순서</th><th>번호</th><th>제목</th><th>구분</th><?php if ($can) echo "<th>관리</th>"; ?></tr></thead>
        <tbody>
        <?php foreach ($problems as $i => $p) {
            $pid = intval($p['problem_id']);
            $dedicated = $p['defunct'] === 'A';
            $title = asm_h(str_replace("&#44;", ",", (string)$p['title']));
            $can_edit = $can && (asm_is_admin() || isset($_SESSION[$OJ_NAME.'_'."p".$pid]));
            $lock = $can ? asm_problem_lock($id, $p) : "";
        ?>
          <tr><td><b><?php echo chr(65 + intval($p['num']))?></b></td><td><?php echo $pid?></td>
            <td class="asm-ptitle"><?php echo ($dedicated && !$can) ? $title : "<a href='../problem.php?id=".$pid."' target='_blank'>".$title."</a>"?></td>
            <td><?php
              if ($dedicated) echo "<span class='admin-badge admin-badge--info'>전용</span>";
              else if ($p['defunct'] === 'N') echo "<span class='admin-badge admin-badge--warn'>공개 중</span>";
              else echo "<span class='admin-badge admin-badge--off'>비공개</span>";
            ?></td>
            <?php if ($can) { ?>
            <td>
              <div class="asm-pactions">
                <?php if ($can_edit) { ?>
                <a class="btn btn-default btn-sm" href="problem_edit.php?id=<?php echo $pid?>">수정</a>
                <a class="btn btn-default btn-sm" href="testdata.php?pid=<?php echo $pid?>">채점 데이터</a>
                <?php } ?>
                <?php foreach (array("up" => array("▲", "위로", $i > 0), "down" => array("▼", "아래로", $i < count($problems) - 1)) as $dir => $m) { if (!$m[2]) continue; ?>
                <form method="post" action="assessment_edit.php?id=<?php echo $id?>">
                  <?php require("../include/set_post_key.php"); ?><input type="hidden" name="action" value="move_problem"><input type="hidden" name="pid" value="<?php echo $pid?>"><input type="hidden" name="dir" value="<?php echo $dir?>">
                  <button type="submit" class="btn btn-default btn-sm" title="<?php echo $m[1]?>" aria-label="<?php echo $m[1]?>"><?php echo $m[0]?></button>
                </form>
                <?php } ?>
                <?php if ($lock === "") { ?>
                <form method="post" action="assessment_edit.php?id=<?php echo $id?>" onsubmit="return confirm(<?php echo asm_h(json_encode($dedicated ? "이 전용 문제와 채점 데이터를 삭제할까요?" : "이 문제를 수행평가에서 뺄까요? 문제 자체는 지워지지 않습니다.", JSON_UNESCAPED_UNICODE))?>)">
                  <?php require("../include/set_post_key.php"); ?><input type="hidden" name="action" value="delete_problem"><input type="hidden" name="pid" value="<?php echo $pid?>">
                  <button type="submit" class="btn btn-danger btn-sm">삭제</button>
                </form>
                <?php } else { ?>
                <span class="admin-field-help"><?php echo $lock?>는 삭제 불가</span>
                <?php } ?>
              </div>
            </td>
            <?php } ?>
          </tr>
        <?php } if (!$problems) echo "<tr><td colspan='".($can ? 5 : 4)."' class='admin-field-help' style='padding:16px;text-align:center'>아직 문제가 없습니다.".($can ? " \"문제 추가\"로 이 ".$MSG_ASSESSMENT."에서만 쓰는 전용 문제를 만드세요." : "")."</td></tr>"; ?>
        </tbody>
      </table>
    </div>
    <p class="admin-field-help" style="margin:8px 0 0">문제를 추가·삭제하거나 순서를 바꾸면 아직 시작하지 않은 회차에만 반영됩니다. 전용 문제는 문제 목록에 나오지 않고 이 <?php echo $MSG_ASSESSMENT?>의 회차 안에서만 풀 수 있습니다.</p>
    <?php if (array_filter($problems, function ($p) { return $p['defunct'] === 'N'; })) { ?>
    <p class="admin-field-help" style="margin:8px 0 0">"공개 중"인 문제는 문제 목록에서 누구나 볼 수 있습니다. <?php echo $MSG_ASSESSMENT?> 전에 비공개로 바꾸려면 관리자에게 요청하세요.</p>
    <?php } ?>
  </section>

  <section class="admin-card">
    <div class="admin-card-title"><?php echo $MSG_CLASS?>별 회차 (<?php echo count($sessions)?>회)</div>
    <div class="admin-table-wrap">
      <table class="table asm-stable">
        <thead><tr><th><?php echo $MSG_CLASS?></th><th><?php echo $MSG_ASSESSMENT?></th><th>상태</th><th>시간</th><th>참가자 / <?php echo $MSG_CLASS?> 인원</th><th>제출</th><th>보기</th><?php if ($can) echo "<th>관리</th>"; ?></tr></thead>
        <tbody>
        <?php foreach ($sessions as $s) {
            $cid = intval($s['contest_id']);
            $members = count(asm_group_members($s['group_name']));
            $st = $STATE[$s['state']];
        ?>
          <tr>
            <td><b><?php echo asm_h($s['group_name'])?></b></td>
            <td><?php echo $cid?></td>
            <td><span class="admin-badge <?php echo $st[1]?>"><?php echo $st[0]?></span><?php if ($s['defunct'] === 'Y') echo " <span class='admin-badge admin-badge--off'>비활성</span>"; ?></td>
            <td>
              <?php if ($can && $s['state'] !== 'ended') { ?>
              <form method="post" action="assessment_edit.php?id=<?php echo $id?>">
                <?php require("../include/set_post_key.php"); ?><input type="hidden" name="action" value="update_time"><input type="hidden" name="cid" value="<?php echo $cid?>">
                <input type="datetime-local" name="start" value="<?php echo asm_dt($s['start_time'])?>" <?php if ($s['state'] === 'running') echo "readonly"?>>
                ~ <input type="datetime-local" name="end" value="<?php echo asm_dt($s['end_time'])?>">
                <button type="submit" class="btn btn-default btn-sm">시간 변경</button>
              </form>
              <?php } else { echo asm_h($s['start_time'])." ~ ".asm_h($s['end_time']); } ?>
            </td>
            <td><?php echo intval($s['invited'])?> / <?php echo $members?><?php if ($members > intval($s['invited'])) echo " <span class='admin-badge admin-badge--warn'>".($members - intval($s['invited']))."명 빠짐</span>"; ?></td>
            <td><?php echo intval($s['submitters'])?>명 · <?php echo intval($s['submits'])?>회</td>
            <td>
              <a href="../contest.php?cid=<?php echo $cid?>" target="_blank">회차 화면</a> ·
              <a href="../status.php?cid=<?php echo $cid?>" target="_blank">채점 현황</a> ·
              <a href="../contestrank.php?cid=<?php echo $cid?>" target="_blank">순위</a>
              <?php if (asm_is_admin()) { ?> · <a href="monitor.php?cid=<?php echo $cid?>#contest">모니터링</a><?php } ?>
            </td>
            <?php if ($can) { ?>
            <td>
              <form method="post" action="assessment_edit.php?id=<?php echo $id?>">
                <?php require("../include/set_post_key.php"); ?><input type="hidden" name="action" value="sync"><input type="hidden" name="cid" value="<?php echo $cid?>">
                <button type="submit" class="btn btn-default btn-sm" title="<?php echo $MSG_CLASS?>에 새로 들어온 학생을 참가자로 추가">참가자 맞추기</button>
              </form>
              <?php if (intval($s['submits']) == 0) { ?>
              <form method="post" action="assessment_edit.php?id=<?php echo $id?>" onsubmit="return confirm('<?php echo asm_h($s['group_name'])?> 회차를 삭제할까요?')">
                <?php require("../include/set_post_key.php"); ?><input type="hidden" name="action" value="delete_session"><input type="hidden" name="cid" value="<?php echo $cid?>">
                <button type="submit" class="btn btn-danger btn-sm">삭제</button>
              </form>
              <?php } ?>
            </td>
            <?php } ?>
          </tr>
        <?php } if (!$sessions) echo "<tr><td colspan='8' class='admin-field-help' style='padding:16px;text-align:center'>아직 회차가 없습니다.".($can ? " 아래에서 ".$MSG_CLASS."과 시간을 정해 추가하세요." : "")."</td></tr>"; ?>
        </tbody>
      </table>
    </div>
    <?php if ($can) { ?>
    <form method="post" action="assessment_edit.php?id=<?php echo $id?>" class="asm-add">
      <?php require("../include/set_post_key.php"); ?>
      <input type="hidden" name="action" value="add_session">
      <label><?php echo $MSG_CLASS?>
        <select name="group" required>
          <option value="">선택</option>
          <?php if (!empty($groups)) foreach ($groups as $g) { ?>
          <option value="<?php echo asm_h($g[0])?>"><?php echo asm_h($g[0])?> (<?php echo intval($g[1])?>명)<?php if (in_array($g[0], $used_groups, true)) echo " · 회차 있음"; ?></option>
          <?php } ?>
        </select>
      </label>
      <label>시작 <input type="datetime-local" name="start" required></label>
      <label>끝 <input type="datetime-local" name="end" required></label>
      <button type="submit" class="btn btn-primary">회차 추가</button>
      <span class="admin-field-help"><?php echo $MSG_CLASS?>이 없으면 <a href="user_group.php">사용자 → <?php echo $MSG_CLASS?> 지정</a>에서 먼저 학생을 묶으세요. 회차마다 비공개 <?php echo $MSG_ASSESSMENT?>가 하나씩 만들어지고, 그 <?php echo $MSG_CLASS?> 학생만 참가할 수 있습니다.</span>
    </form>
    <?php } ?>
  </section>

  <?php if ($can && !$sessions) { ?>
  <form method="post" action="assessment_edit.php?id=<?php echo $id?>" style="margin-top:16px" onsubmit="return confirm('이 <?php echo $MSG_ASSESSMENT?>를 삭제할까요?')">
    <?php require("../include/set_post_key.php"); ?><input type="hidden" name="action" value="delete">
    <button type="submit" class="btn btn-danger btn-sm"><?php echo $MSG_ASSESSMENT?> 삭제</button>
  </form>
  <?php } ?>
  <?php } ?>
</div>
