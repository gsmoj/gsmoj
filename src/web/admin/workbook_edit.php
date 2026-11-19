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
    if ($id && !$wb) wb_back(0, false, "코스를 찾을 수 없습니다. 삭제되었을 수 있습니다.");

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
        wb_back($new, $new > 0, $new > 0 ? "코스를 만들었습니다. 이제 문제 번호를 순서대로 넣으세요." : "코스를 만들지 못했습니다. 데이터베이스 업데이트를 확인하세요.");
    }
    // 아래 작업은 이미 만든 문제집에서만 한다.
    if (!$wb) wb_back(0, false, "코스를 먼저 만드세요.");

    // 화면에서 정한 순서대로 문제 목록을 통째로 바꾼다. 없는 문제와 수행평가 전용 문제는 빼고 알려 준다.
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
        $msg = "문제 ".count($ok)."개를 정한 순서대로 저장했습니다.";
        if ($missing) $msg .= " 없는 문제 번호라 뺐습니다: ".implode(", ", $missing).".";
        if ($dedicated) $msg .= " 수행평가 전용 문제라 뺐습니다: ".implode(", ", $dedicated).".";
        wb_back($id, !$missing && !$dedicated, $msg);
    }

    // 문제집은 학생 화면에서만 숨기고(defunct='Y') 문제 목록 기록은 남긴다.
    if ($action === "delete") {
        pdo_query("UPDATE `workbook` SET `defunct`='Y', `updated_at`=NOW() WHERE `workbook_id`=?", $id);
        $_SESSION[$OJ_NAME.'_'.'wb_flash'] = array(true, "코스를 삭제했습니다.");
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
    echo "<div class='admin-content-page'><div class='alert alert-warning'>코스 테이블이 아직 없습니다. <a href='update_db.php'>데이터베이스 업데이트</a>를 먼저 실행하세요.</div></div>";
    exit(1);
}
$wb = $id ? wb_get($id) : null;
if ($id && !$wb) {
    echo "<div class='admin-content-page'><div class='alert alert-danger'>코스를 찾을 수 없습니다. <a href='workbook_list.php'>목록으로</a></div></div>";
    exit(1);
}
$flash = isset($_SESSION[$OJ_NAME.'_'.'wb_flash']) ? $_SESSION[$OJ_NAME.'_'.'wb_flash'] : null;
unset($_SESSION[$OJ_NAME.'_'.'wb_flash']);
$problems = $wb ? wb_problems($id) : array();
// 선택 목록(왼쪽)에 쓸 전체 문제. 수행평가 전용 문제는 문제집에 넣을 수 없으므로 뺀다.
// 화면 스크립트가 textContent로만 넣으므로 제목은 원문 그대로 JSON으로 넘긴다.
$wb_all = array();
$wb_selected = array();
if ($wb) {
    $rows = pdo_query("SELECT `problem_id`, `title`, `defunct` FROM `problem` WHERE `defunct`<>'A' ORDER BY `problem_id`");
    if (is_array($rows)) foreach ($rows as $r)
        $wb_all[] = array(intval($r['problem_id']), str_replace("&#44;", ",", (string)$r['title']), $r['defunct'] === 'N' ? 1 : 0);
    foreach ($problems as $p)
        $wb_selected[] = array(intval($p['problem_id']), $p['title'] === null ? "(없는 문제)" : str_replace("&#44;", ",", (string)$p['title']), $p['defunct'] === 'N' ? 1 : 0);
}
// 제목에 깨진 글자가 있어도 목록 전체가 비지 않도록 대체 문자로 바꾼다.
$wb_json_flags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE;
?>
<title><?php echo $wb ? wb_h($wb['title']) : "새 코스"?></title>
<style>
  .wb-grid { display: grid; gap: 14px; }
  .wb-field { display: grid; gap: 6px; }
  .wb-field label { margin: 0; font-weight: 700; }
  .wb-card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
  .wb-card-head .admin-card-title { margin-bottom: 0; }
  /* 왼쪽: 문제를 검색해 체크하면 오른쪽 순서 목록 끝에 들어간다. 오른쪽: 끌어서 놓거나 ▲▼로 순서를 바꾼다. */
  .wb-layout { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 16px; align-items: start; }
  .wb-panel { display: flex; flex-direction: column; min-height: 0; }
  .wb-panel-body { height: 520px; overflow-y: auto; border: 1px solid var(--admin-border); border-radius: 10px; background: #fff; }
  .wb-search { width: 100% !important; margin-bottom: 10px; }
  .wb-note { margin: 8px 0 0; color: var(--admin-muted); font-size: 12px; }
  .wb-pick, .wb-order { margin: 0; padding: 0; list-style: none; }
  .wb-pick li { border-bottom: 1px solid #f1f3f5; }
  .wb-pick label { display: flex; align-items: center; gap: 10px; margin: 0; padding: 8px 12px; font-weight: 400; cursor: pointer; }
  .wb-pick label:hover { background: #f8fafc; }
  .wb-pick input { margin: 0; }
  .wb-pick li.is-on label { background: #eff6ff; }
  .wb-pid { min-width: 52px; color: var(--admin-muted); font-variant-numeric: tabular-nums; }
  .wb-ptitle { flex: 1 1 auto; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .wb-order li { display: flex; align-items: center; gap: 8px; padding: 7px 8px 7px 6px; border-bottom: 1px solid #f1f3f5; background: #fff; }
  .wb-order li.is-dragging { opacity: .45; background: #eff6ff; }
  .wb-handle { padding: 2px 4px; color: #9ca3af; cursor: grab; user-select: none; font-size: 14px; line-height: 1; }
  .wb-order li:active .wb-handle { cursor: grabbing; }
  .wb-no { min-width: 28px; color: var(--admin-text); font-weight: 700; text-align: right; font-variant-numeric: tabular-nums; }
  /* 관리자 공통 버튼 크기(admin.css)를 덮어써 순서 줄의 ▲▼✕ 버튼을 작게 고정한다. */
  .wb-order .btn.wb-mini { flex: 0 0 auto; width: 28px !important; min-width: 0 !important; height: 26px !important; min-height: 0 !important; padding: 0 !important; font-size: 12px !important; line-height: 24px !important; }
  .wb-empty { padding: 28px 16px; color: var(--admin-muted); text-align: center; }
  .wb-savebar { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 12px; }
  .wb-dirty { color: #b45309; font-size: 13px; font-weight: 600; }
  @media (max-width: 900px) { .wb-layout { grid-template-columns: minmax(0, 1fr); } .wb-panel-body { height: 360px; } }
</style>
<div class="admin-content-page">
  <div class="admin-page-heading">
    <div>
      <h3><?php echo $wb ? wb_h($wb['title']) : "새 코스"?></h3>
      <p><?php echo $wb ? "만든 사람 ".wb_h($wb['owner'])." · 문제 ".count($problems)."개" : "제목을 정해 만든 뒤, 문제를 체크해 넣고 풀 순서를 정합니다."?></p>
    </div>
    <div class="admin-heading-actions">
      <a class="btn btn-default" href="workbook_list.php">목록</a>
      <?php if ($wb) { ?>
      <a class="btn btn-default" href="../workbook.php?id=<?php echo $id?>" target="_blank">학생 화면 보기</a>
      <form method="post" action="workbook_edit.php?id=<?php echo $id?>" style="margin:0" onsubmit="return confirm('이 코스를 삭제할까요? 문제 자체는 지워지지 않습니다.')">
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
        <label for="wb-desc">설명 <span class="admin-field-help">(코스 목록과 코스 화면 위에 보입니다)</span></label>
        <textarea id="wb-desc" name="description" rows="3" placeholder="예: 입출력부터 반복문까지 순서대로 풀어 보세요."><?php echo $wb ? wb_h($wb['description']) : ""?></textarea>
      </div>
    </div>
    <div class="admin-form-actions"><button type="submit" class="btn btn-primary"><?php echo $wb ? "저장" : "만들기"?></button></div>
  </form>

  <?php if ($wb) { ?>
  <div class="wb-layout">
    <section class="admin-card wb-panel">
      <div class="wb-card-head"><div class="admin-card-title">문제 선택</div><span class="admin-field-help" id="wb-pick-count"></span></div>
      <input type="search" class="form-control wb-search" id="wb-search" placeholder="문제 번호나 제목으로 검색" aria-label="문제 번호나 제목으로 검색" autocomplete="off">
      <div class="wb-panel-body"><ul class="wb-pick" id="wb-pick"></ul></div>
      <p class="wb-note">체크하면 오른쪽 순서의 맨 끝에 들어가고, 체크를 풀면 빠집니다. 수행평가 전용 문제는 목록에 없습니다.</p>
    </section>

    <form method="post" action="workbook_edit.php?id=<?php echo $id?>" class="admin-card wb-panel" id="wb-order-form">
      <?php require("../include/set_post_key.php"); ?>
      <input type="hidden" name="action" value="set_problems">
      <input type="hidden" name="pids" id="wb-pids" value="">
      <div class="wb-card-head">
        <div class="admin-card-title">코스 순서 <span id="wb-order-count"></span></div>
        <button type="button" class="btn btn-default btn-sm" id="wb-clear">모두 빼기</button>
      </div>
      <div class="wb-panel-body"><ol class="wb-order" id="wb-order"></ol></div>
      <p class="wb-note">⋮⋮ 손잡이를 끌어 순서를 바꾸거나 ▲▼로 한 칸씩 옮깁니다. 위에서부터 1번입니다. 비공개 문제와 진행 중인 대회·비공개 대회에 쓰인 문제는 학생 화면에서 제목 없이 잠긴 줄로 보입니다.</p>
      <div class="wb-savebar">
        <span class="wb-dirty" id="wb-dirty" hidden>저장하지 않은 변경이 있습니다</span>
        <button type="submit" class="btn btn-primary" style="margin-left:auto">이 순서로 저장</button>
      </div>
    </form>
  </div>
  <script>
  (function () {
    // [문제 번호, 제목, 공개 여부(1/0)]
    var ALL = <?php echo json_encode($wb_all, $wb_json_flags)?>;
    var SELECTED = <?php echo json_encode($wb_selected, $wb_json_flags)?>;
    var MAX = 500, SHOW = 300;
    var byId = {};
    ALL.forEach(function (p) { byId[p[0]] = p; });
    SELECTED.forEach(function (p) { if (!byId[p[0]]) byId[p[0]] = p; });   // 지워진 문제도 순서 목록에는 남긴다
    var order = SELECTED.map(function (p) { return p[0]; });
    var dirty = false;

    var $ = function (id) { return document.getElementById(id); };
    var pick = $('wb-pick'), list = $('wb-order'), search = $('wb-search');

    function el(tag, cls, text) {
      var n = document.createElement(tag);
      if (cls) n.className = cls;
      if (text !== undefined) n.textContent = text;
      return n;
    }
    function badge(p) {
      return p[2] ? null : el('span', 'admin-badge admin-badge--off', '비공개');
    }
    function touch() {
      dirty = true;
      $('wb-dirty').hidden = false;
    }

    function renderPick() {
      var q = search.value.trim().toLowerCase();
      var chosen = {};
      order.forEach(function (id) { chosen[id] = true; });
      var hits = q === '' ? ALL : ALL.filter(function (p) {
        return String(p[0]).indexOf(q) === 0 || p[1].toLowerCase().indexOf(q) >= 0;
      });
      pick.textContent = '';
      hits.slice(0, SHOW).forEach(function (p) {
        var li = el('li', chosen[p[0]] ? 'is-on' : '');
        var label = el('label');
        var box = el('input');
        box.type = 'checkbox';
        box.checked = !!chosen[p[0]];
        box.addEventListener('change', function () {
          if (box.checked) {
            if (order.length >= MAX) { box.checked = false; alert('코스에는 최대 ' + MAX + '개까지 넣을 수 있습니다.'); return; }
            order.push(p[0]);
          } else {
            order = order.filter(function (id) { return id !== p[0]; });
          }
          touch();
          li.className = box.checked ? 'is-on' : '';
          renderOrder();
        });
        label.appendChild(box);
        label.appendChild(el('span', 'wb-pid', String(p[0])));
        label.appendChild(el('span', 'wb-ptitle', p[1]));
        var b = badge(p);
        if (b) label.appendChild(b);
        li.appendChild(label);
        pick.appendChild(li);
      });
      if (hits.length === 0) pick.appendChild(el('li', 'wb-empty', '검색 결과가 없습니다.'));
      $('wb-pick-count').textContent = hits.length > SHOW
        ? '검색 결과 ' + hits.length + '개 중 ' + SHOW + '개 표시 · 검색으로 좁혀 보세요'
        : '검색 결과 ' + hits.length + '개';
    }

    function move(from, to) {
      if (to < 0 || to >= order.length) return;
      var id = order.splice(from, 1)[0];
      order.splice(to, 0, id);
      touch();
      renderOrder();
    }

    var dragEl = null;
    function renderOrder() {
      list.textContent = '';
      order.forEach(function (id, i) {
        var p = byId[id] || [id, '(없는 문제)', 0];
        var li = el('li');
        li.draggable = true;
        li.dataset.id = id;
        li.appendChild(el('span', 'wb-handle', '⋮⋮'));
        li.appendChild(el('span', 'wb-no', String(i + 1)));
        li.appendChild(el('span', 'wb-pid', String(id)));
        li.appendChild(el('span', 'wb-ptitle', p[1]));
        var b = badge(p);
        if (b) li.appendChild(b);
        [['▲', '위로', i - 1], ['▼', '아래로', i + 1]].forEach(function (m) {
          var btn = el('button', 'btn btn-default wb-mini', m[0]);
          btn.type = 'button';
          btn.title = m[1];
          btn.setAttribute('aria-label', m[1]);
          btn.disabled = m[2] < 0 || m[2] >= order.length;
          btn.addEventListener('click', function () { move(i, m[2]); });
          li.appendChild(btn);
        });
        var del = el('button', 'btn btn-default wb-mini', '✕');
        del.type = 'button';
        del.title = '빼기';
        del.setAttribute('aria-label', '빼기');
        del.addEventListener('click', function () {
          order.splice(i, 1);
          touch();
          renderOrder();
          renderPick();
        });
        li.appendChild(del);
        li.addEventListener('dragstart', function (e) {
          dragEl = li;
          li.classList.add('is-dragging');
          e.dataTransfer.effectAllowed = 'move';
          e.dataTransfer.setData('text/plain', String(id));
        });
        li.addEventListener('dragend', function () {
          li.classList.remove('is-dragging');
          dragEl = null;
          // 끌어서 바뀐 화면 순서를 그대로 순서 목록으로 삼는다.
          var next = Array.prototype.map.call(list.children, function (n) { return parseInt(n.dataset.id, 10); });
          if (next.join(',') !== order.join(',')) { order = next; touch(); }
          renderOrder();
        });
        list.appendChild(li);
      });
      if (order.length === 0) list.appendChild(el('li', 'wb-empty', '왼쪽에서 문제를 체크해 넣으세요.'));
      $('wb-order-count').textContent = '(' + order.length + '개)';
      $('wb-pids').value = order.join(',');
    }
    list.addEventListener('dragover', function (e) {
      if (!dragEl) return;
      e.preventDefault();
      var over = e.target.closest ? e.target.closest('li') : null;
      if (!over || over === dragEl || !over.dataset.id) return;
      var r = over.getBoundingClientRect();
      list.insertBefore(dragEl, e.clientY > r.top + r.height / 2 ? over.nextSibling : over);
    });
    // 놓을 때 브라우저가 끌던 글자를 주소로 열지 않게 한다(Firefox). 순서 반영은 dragend에서 한다.
    list.addEventListener('drop', function (e) { if (dragEl) e.preventDefault(); });

    search.addEventListener('input', renderPick);
    $('wb-clear').addEventListener('click', function () {
      if (order.length === 0 || !confirm('코스 순서에서 모든 문제를 뺄까요? 저장해야 반영됩니다.')) return;
      order = [];
      touch();
      renderOrder();
      renderPick();
    });
    $('wb-order-form').addEventListener('submit', function () {
      $('wb-pids').value = order.join(',');
      dirty = false;
    });
    window.addEventListener('beforeunload', function (e) {
      if (!dirty) return;
      e.preventDefault();
      e.returnValue = '';
    });

    renderOrder();
    renderPick();
  })();
  </script>
  <?php } ?>
</div>
