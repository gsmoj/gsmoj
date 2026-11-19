<?php
// 문제집 만들기·수정. 관리자만 쓸 수 있다.
// 저장 요청(POST)은 화면을 그리기 전에 처리하고, 결과 메시지를 세션에 담아 같은 화면으로 돌아간다.
require_once("../include/db_info.inc.php");
require_once("../include/my_func.inc.php");
if (file_exists("../lang/$OJ_LANG.php")) require_once("../lang/$OJ_LANG.php");
require_once("../include/workbook.inc.php");

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$is_admin = isset($_SESSION[$OJ_NAME.'_'.'administrator']);

function wb_back($id, $ok, $msg) {
    global $OJ_NAME;
    $_SESSION[$OJ_NAME.'_'.'wb_flash'] = array($ok, $msg);
    header("Location: workbook_edit.php".($id ? "?id=".intval($id) : ""));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['action'])) {
    if (!$is_admin || !wb_ready()) exit(1);
    require_once("../include/check_post_key.php");
    $action = $_POST['action'];
    $wb = $id ? wb_get($id) : null;
    if ($id && !$wb) wb_back(0, false, "문제집을 찾을 수 없습니다. 삭제되었을 수 있습니다.");

    if ($action === "save") {
        $title = trim(isset($_POST['title']) ? (string)$_POST['title'] : "");
        $desc = isset($_POST['description']) ? (string)$_POST['description'] : "";
        if ($title === "") wb_back($id, false, "제목을 입력하세요.");
        $title = mb_substr($title, 0, 200, 'UTF-8');
        if ($wb) {
            pdo_query("UPDATE `workbook` SET `title`=?, `description`=?, `updated_at`=NOW() WHERE `workbook_id`=?", $title, $desc, $id);
            wb_back($id, true, "저장했습니다.");
        }
        $new = intval(pdo_query("INSERT INTO `workbook`(`title`,`description`,`owner`,`created_at`,`updated_at`,`defunct`) VALUES(?,?,?,NOW(),NOW(),'N')",
            $title, $desc, $_SESSION[$OJ_NAME.'_'.'user_id']));
        wb_back($new, $new > 0, $new > 0 ? "문제집을 만들었습니다. 이제 문제 번호를 순서대로 넣으세요." : "문제집을 만들지 못했습니다. 데이터베이스 업데이트를 확인하세요.");
    }
    // 아래 작업은 이미 만든 문제집에서만 한다.
    if (!$wb) wb_back(0, false, "문제집을 먼저 만드세요.");

    // 입력한 순서대로 문제 목록을 통째로 바꾼다. 없는 문제와 수행평가 전용 문제는 빼고 알려 준다.
    if ($action === "set_problems") {
        $pids = wb_parse_ids(isset($_POST['pids']) ? $_POST['pids'] : "");
        $ok = array();
        $missing = array();
        $dedicated = array();
        if ($pids) {
            $in = implode(",", array_fill(0, count($pids), "?"));
            $rows = pdo_query("SELECT `problem_id`, `defunct` FROM `problem` WHERE `problem_id` IN ($in)", $pids);
            $found = array();
            if (is_array($rows)) foreach ($rows as $r) $found[intval($r['problem_id'])] = $r['defunct'];
            foreach ($pids as $pid) {
                if (!isset($found[$pid])) $missing[] = $pid;
                else if ($found[$pid] === 'A') $dedicated[] = $pid;
                else $ok[] = $pid;
            }
        }
        wb_save_problems($id, $ok);
        $msg = "문제 ".count($ok)."개를 입력한 순서대로 저장했습니다.";
        if ($missing) $msg .= " 없는 문제 번호라 뺐습니다: ".implode(", ", $missing).".";
        if ($dedicated) $msg .= " 수행평가 전용 문제라 뺐습니다: ".implode(", ", $dedicated).".";
        wb_back($id, !$missing && !$dedicated, $msg);
    }

    if ($action === "move" || $action === "remove") {
        $pids = array_map(function ($r) { return intval($r['problem_id']); }, wb_problems($id));
        $i = array_search(isset($_POST['pid']) ? intval($_POST['pid']) : 0, $pids, true);
        if ($i === false) wb_back($id, false, "문제를 찾을 수 없습니다.");
        if ($action === "remove") {
            array_splice($pids, $i, 1);
            wb_save_problems($id, $pids);
            wb_back($id, true, "문제집에서 뺐습니다. 문제 자체는 지워지지 않습니다.");
        }
        $j = $i + (isset($_POST['dir']) && $_POST['dir'] === "down" ? 1 : -1);
        if (!isset($pids[$j])) wb_back($id, false, "더 옮길 수 없습니다.");
        list($pids[$i], $pids[$j]) = array($pids[$j], $pids[$i]);
        wb_save_problems($id, $pids);
        wb_back($id, true, "순서를 바꿨습니다.");
    }

    // 문제집은 학생 화면에서만 숨기고(defunct='Y') 문제 목록 기록은 남긴다.
    if ($action === "delete") {
        pdo_query("UPDATE `workbook` SET `defunct`='Y', `updated_at`=NOW() WHERE `workbook_id`=?", $id);
        $_SESSION[$OJ_NAME.'_'.'wb_flash'] = array(true, "문제집을 삭제했습니다.");
        header("Location: workbook_list.php");
        exit;
    }
    wb_back($id, false, "알 수 없는 요청입니다.");
}

require("admin-header.php");
if (!$is_admin) {
    echo "<div class='admin-content-page'><div class='alert alert-danger'>관리자만 사용할 수 있습니다.</div></div>";
    exit(1);
}
if (!wb_ready()) {
    echo "<div class='admin-content-page'><div class='alert alert-warning'>문제집 테이블이 아직 없습니다. <a href='update_db.php'>데이터베이스 업데이트</a>를 먼저 실행하세요.</div></div>";
    exit(1);
}
$wb = $id ? wb_get($id) : null;
if ($id && !$wb) {
    echo "<div class='admin-content-page'><div class='alert alert-danger'>문제집을 찾을 수 없습니다. <a href='workbook_list.php'>목록으로</a></div></div>";
    exit(1);
}
$flash = isset($_SESSION[$OJ_NAME.'_'.'wb_flash']) ? $_SESSION[$OJ_NAME.'_'.'wb_flash'] : null;
unset($_SESSION[$OJ_NAME.'_'.'wb_flash']);
$problems = $wb ? wb_problems($id) : array();
$pid_text = implode("\n", array_map(function ($p) { return intval($p['problem_id']); }, $problems));
?>
<title><?php echo $wb ? wb_h($wb['title']) : "새 문제집"?></title>
<style>
  .wb-grid { display: grid; gap: 14px; }
  .wb-field { display: grid; gap: 6px; }
  .wb-field label { margin: 0; font-weight: 700; }
  .wb-card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
  .wb-card-head .admin-card-title { margin-bottom: 0; }
  .wb-layout { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 16px; align-items: start; }
  .wb-table td, .wb-table th { vertical-align: middle !important; white-space: nowrap; }
  .wb-table .wb-title { width: 100%; min-width: 200px; white-space: normal; }
  .wb-actions { display: flex; flex-wrap: nowrap; align-items: center; gap: 6px; }
  .wb-actions form { margin: 0; }
  .wb-ids textarea { width: 100%; min-height: 320px; font-family: Consolas, "D2Coding", monospace; }
  @media (max-width: 900px) { .wb-layout { grid-template-columns: minmax(0, 1fr); } }
</style>
<div class="admin-content-page">
  <div class="admin-page-heading">
    <div>
      <h3><?php echo $wb ? wb_h($wb['title']) : "새 문제집"?></h3>
      <p><?php echo $wb ? "만든 사람 ".wb_h($wb['owner'])." · 문제 ".count($problems)."개" : "제목을 정해 만든 뒤, 문제 번호를 풀 순서대로 넣습니다."?></p>
    </div>
    <div class="admin-heading-actions">
      <a class="btn btn-default" href="workbook_list.php">목록</a>
      <?php if ($wb) { ?>
      <a class="btn btn-default" href="../workbook.php?id=<?php echo $id?>" target="_blank">학생 화면 보기</a>
      <form method="post" action="workbook_edit.php?id=<?php echo $id?>" style="margin:0" onsubmit="return confirm('이 문제집을 삭제할까요? 문제 자체는 지워지지 않습니다.')">
        <?php require("../include/set_post_key.php"); ?><input type="hidden" name="action" value="delete">
        <button type="submit" class="btn btn-danger">삭제</button>
      </form>
      <?php } ?>
    </div>
  </div>

  <?php if ($flash) echo "<div class='alert ".($flash[0] ? "alert-success" : "alert-warning")."'>".wb_h($flash[1])."</div>"; ?>

  <form method="post" action="workbook_edit.php<?php echo $id ? "?id=".$id : ""?>" class="admin-card" style="max-width:none; margin-bottom:16px">
    <?php require("../include/set_post_key.php"); ?>
    <input type="hidden" name="action" value="save">
    <div class="wb-grid">
      <div class="wb-field">
        <label for="wb-title">제목</label>
        <input type="text" id="wb-title" name="title" maxlength="200" required value="<?php echo $wb ? wb_h($wb['title']) : ""?>" placeholder="예: 기초 100제">
      </div>
      <div class="wb-field">
        <label for="wb-desc">설명 <span class="admin-field-help">(문제집 목록과 문제집 화면 위에 보입니다)</span></label>
        <textarea id="wb-desc" name="description" rows="3" placeholder="예: 입출력부터 반복문까지 순서대로 풀어 보세요."><?php echo $wb ? wb_h($wb['description']) : ""?></textarea>
      </div>
    </div>
    <div class="admin-form-actions"><button type="submit" class="btn btn-primary"><?php echo $wb ? "저장" : "만들기"?></button></div>
  </form>

  <?php if ($wb) { ?>
  <div class="wb-layout">
    <section class="admin-card">
      <div class="wb-card-head"><div class="admin-card-title">문제 순서 (<?php echo count($problems)?>개)</div></div>
      <div class="admin-table-wrap">
        <table class="table wb-table">
          <thead><tr><th>순서</th><th>번호</th><th>제목</th><th>공개</th><th>관리</th></tr></thead>
          <tbody>
          <?php foreach ($problems as $i => $p) {
              $pid = intval($p['problem_id']);
              $title = $p['title'] === null ? "(없는 문제)" : str_replace("&#44;", ",", (string)$p['title']);
          ?>
            <tr>
              <td><b><?php echo $i + 1?></b></td>
              <td><?php echo $pid?></td>
              <td class="wb-title"><a href="../problem.php?id=<?php echo $pid?>" target="_blank"><?php echo wb_h($title)?></a></td>
              <td><?php
                if ($p['defunct'] === 'N') echo "<span class='admin-badge admin-badge--on'>공개</span>";
                else if ($p['defunct'] === null) echo "<span class='admin-badge admin-badge--off'>없음</span>";
                else echo "<span class='admin-badge admin-badge--off'>비공개</span>";
              ?></td>
              <td>
                <div class="wb-actions">
                  <?php foreach (array("up" => array("▲", "위로", $i > 0), "down" => array("▼", "아래로", $i < count($problems) - 1)) as $dir => $m) { if (!$m[2]) continue; ?>
                  <form method="post" action="workbook_edit.php?id=<?php echo $id?>">
                    <?php require("../include/set_post_key.php"); ?><input type="hidden" name="action" value="move"><input type="hidden" name="pid" value="<?php echo $pid?>"><input type="hidden" name="dir" value="<?php echo $dir?>">
                    <button type="submit" class="btn btn-default btn-sm" title="<?php echo $m[1]?>" aria-label="<?php echo $m[1]?>"><?php echo $m[0]?></button>
                  </form>
                  <?php } ?>
                  <form method="post" action="workbook_edit.php?id=<?php echo $id?>" onsubmit="return confirm('이 문제를 문제집에서 뺄까요? 문제 자체는 지워지지 않습니다.')">
                    <?php require("../include/set_post_key.php"); ?><input type="hidden" name="action" value="remove"><input type="hidden" name="pid" value="<?php echo $pid?>">
                    <button type="submit" class="btn btn-default btn-sm">빼기</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php } ?>
          <?php if (!$problems) { ?><tr><td colspan="5" style="color:var(--admin-muted)">아직 문제가 없습니다. 오른쪽에 문제 번호를 풀 순서대로 넣으세요.</td></tr><?php } ?>
          </tbody>
        </table>
      </div>
      <p class="admin-field-help" style="margin:8px 0 0">비공개 문제와 진행 중인 대회·비공개 대회에 쓰인 문제는 학생 화면에서 제목 없이 잠긴 줄로 보입니다.</p>
    </section>

    <form method="post" action="workbook_edit.php?id=<?php echo $id?>" class="admin-card wb-ids" onsubmit="return confirm('입력한 순서로 문제 목록을 통째로 바꿀까요?')">
      <?php require("../include/set_post_key.php"); ?>
      <input type="hidden" name="action" value="set_problems">
      <div class="wb-field">
        <label for="wb-pids">문제 번호 (풀 순서대로)</label>
        <span class="admin-field-help">한 줄에 하나씩, 또는 쉼표·띄어쓰기로 구분합니다. 위에서부터 1번입니다. 최대 500개.</span>
        <textarea id="wb-pids" name="pids" placeholder="1000&#10;1001&#10;1002"><?php echo wb_h($pid_text)?></textarea>
      </div>
      <div class="admin-form-actions"><button type="submit" class="btn btn-primary">이 순서로 저장</button></div>
    </form>
  </div>
  <?php } ?>
</div>
