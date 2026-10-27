<?php
// 학생 반(users.group_name) 일괄 지정 화면.
// 목록에서 골라 한 번에 지정하거나, "아이디 반" 목록을 붙여 넣어 지정한다. 반은 학생당 하나이며 최대 16자다.
require("admin-header.php");
if (!(isset($_SESSION[$OJ_NAME.'_'.'administrator']) || isset($_SESSION[$OJ_NAME.'_'.'teacher']))) {
    echo "<div class='admin-content-page'><div class='alert alert-danger'>관리자와 교사만 사용할 수 있습니다.</div></div>";
    exit(1);
}
$MSG_CLASS = isset($MSG_CLASS) ? $MSG_CLASS : "반";

function ug_valid_group($g) {
    return $g === "" || (mb_strlen($g, "UTF-8") <= 16 && preg_match('/^[\p{L}\p{N} _.\-]+$/u', $g));
}

$messages = array();
$errors = array();
if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['mode'])) {
    require_once("../include/check_post_key.php");
    if ($_POST['mode'] === "selected") {
        // 선택한 사용자에게 같은 반을 지정하거나 비운다.
        $group = isset($_POST['clear']) ? "" : trim(isset($_POST['group']) ? $_POST['group'] : "");
        $users = isset($_POST['users']) && is_array($_POST['users']) ? array_values(array_unique($_POST['users'])) : array();
        if (count($users) == 0) $errors[] = "사용자를 먼저 선택하세요.";
        else if (!isset($_POST['clear']) && $group === "") $errors[] = "지정할 ".$MSG_CLASS." 이름을 입력하세요.";
        else if (!ug_valid_group($group)) $errors[] = $MSG_CLASS." 이름은 16자 이내의 한글·영문·숫자·공백·`-` `_` `.`만 쓸 수 있습니다.";
        else {
            $done = 0;
            foreach ($users as $u) $done += intval(pdo_query("update users set group_name=? where user_id=?", $group, $u));
            $messages[] = $group === "" ? "{$done}명의 ".$MSG_CLASS."을 비웠습니다." : "{$done}명을 '".htmlentities($group, ENT_QUOTES, 'UTF-8')."' ".$MSG_CLASS."으로 지정했습니다.";
        }
    } else if ($_POST['mode'] === "paste") {
        // 한 줄에 "아이디 반" (공백·탭·쉼표 구분). 반 칸을 비우면 반을 비운다.
        $lines = preg_split('/\r\n|\r|\n/', isset($_POST['list']) ? $_POST['list'] : "");
        $done = 0; $unknown = array(); $invalid = array();
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === "") continue;
            $parts = preg_split('/[\t,]+|\s+/', $line, 2);
            $u = trim($parts[0]);
            $g = isset($parts[1]) ? trim($parts[1]) : "";
            if (!ug_valid_group($g)) { $invalid[] = $line; continue; }
            $n = intval(pdo_query("update users set group_name=? where user_id=?", $g, $u));
            if ($n) $done++;
            else {
                $exists = pdo_query("select count(*) from users where user_id=?", $u);
                if (empty($exists) || intval($exists[0][0]) == 0) $unknown[] = $u; else $done++;  // 이미 같은 반이면 바뀐 행이 0이다
            }
        }
        $messages[] = "{$done}명의 ".$MSG_CLASS."을 지정했습니다.";
        if ($unknown) $errors[] = "없는 아이디 ".count($unknown)."개: ".htmlentities(implode(", ", array_slice($unknown, 0, 30)), ENT_QUOTES, 'UTF-8').(count($unknown) > 30 ? " …" : "");
        if ($invalid) $errors[] = $MSG_CLASS." 이름이 올바르지 않은 줄 ".count($invalid)."개: ".htmlentities(implode(" / ", array_slice($invalid, 0, 10)), ENT_QUOTES, 'UTF-8');
    }
}

// 목록 필터
$q = isset($_GET['q']) ? trim($_GET['q']) : "";
$filter = isset($_GET['group']) ? $_GET['group'] : "";
$page = max(1, isset($_GET['page']) ? intval($_GET['page']) : 1);
$per_page = 100;
$where = array("defunct='N'");
$args = array();
if ($q !== "") {
    $where[] = "(user_id like ? or nick like ? or school like ?)";
    $like = "%".str_replace(array("%", "_"), array("\\%", "\\_"), $q)."%";
    array_push($args, $like, $like, $like);
}
if ($filter === "__none__") $where[] = "group_name=''";
else if ($filter !== "") { $where[] = "group_name=?"; $args[] = $filter; }
$where_sql = implode(" and ", $where);
$total = call_user_func_array("pdo_query", array_merge(array("select count(*) from users where $where_sql"), $args));
$total = empty($total) ? 0 : intval($total[0][0]);
$pages = max(1, ceil($total / $per_page));
$page = min($page, $pages);
$offset = ($page - 1) * $per_page;
$rows = call_user_func_array("pdo_query", array_merge(array("select user_id, nick, school, group_name, solved, submit, accesstime from users where $where_sql order by group_name='' desc, group_name, user_id limit $offset, $per_page"), $args));
$groups = pdo_query("select group_name, count(*) from users where defunct='N' group by group_name order by group_name=''desc, group_name");

function ug_url($over) {
    global $q, $filter, $page;
    $params = array_merge(array("q" => $q, "group" => $filter, "page" => $page), $over);
    foreach ($params as $k => $v) if ($v === "" || $v === 1 && $k === "page") unset($params[$k]);
    return "user_group.php".($params ? "?".http_build_query($params) : "");
}
?>
<title><?php echo $MSG_CLASS?> 지정</title>
<style>
  .ug-groups { display: flex; flex-wrap: wrap; gap: 6px; margin: 0 0 16px; }
  .ug-groups a { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border: 1px solid var(--admin-border); border-radius: 999px; background: #fff; color: var(--admin-text); font-size: 13px; text-decoration: none; }
  .ug-groups a.is-active { border-color: var(--admin-primary); background: #eff6ff; color: var(--admin-primary-dark); font-weight: 600; }
  .ug-groups a b { color: var(--admin-muted); font-weight: 600; }
  .ug-layout { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 16px; align-items: start; }
  .ug-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin: 0 0 12px; }
  .ug-toolbar input[type=text], .ug-toolbar select { width: auto !important; min-width: 0; }
  .ug-apply { position: sticky; top: 0; z-index: 5; display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin: 0 0 12px; padding: 10px 12px; border-radius: 10px; background: #f8fafc; }
  .ug-apply input[type=text] { width: 160px !important; }
  .ug-table td, .ug-table th { white-space: nowrap; vertical-align: middle !important; }
  .ug-none { color: #b45309; font-size: 12px; }
  .ug-paste textarea { min-height: 260px; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 13px; }
  .ug-pager { display: flex; gap: 6px; margin-top: 12px; }
  @media (max-width: 1100px) { .ug-layout { grid-template-columns: minmax(0, 1fr); } }
</style>
<div class="admin-content-page">
  <div class="admin-page-heading">
    <div>
      <h3><?php echo $MSG_USER?> - <?php echo $MSG_CLASS?> 지정</h3>
      <p>학생을 <?php echo $MSG_CLASS?>별로 묶습니다. <?php echo $MSG_CLASS?>은 수행평가 회차의 참가자와 채점 현황·<?php echo $MSG_CLASS?> 통계 필터에 쓰입니다. 바뀐 <?php echo $MSG_CLASS?>은 학생이 다시 로그인하면 반영됩니다.</p>
    </div>
  </div>

  <?php foreach ($messages as $m) echo "<div class='alert alert-success'>$m</div>"; ?>
  <?php foreach ($errors as $m) echo "<div class='alert alert-danger'>$m</div>"; ?>

  <nav class="ug-groups" aria-label="<?php echo $MSG_CLASS?>별 보기">
    <a href="<?php echo htmlentities(ug_url(array("group" => "", "page" => 1)), ENT_QUOTES, 'UTF-8')?>" class="<?php if ($filter === "") echo 'is-active'?>">전체</a>
    <?php if (!empty($groups)) foreach ($groups as $g) {
        $name = $g[0]; $key = $name === "" ? "__none__" : $name; ?>
    <a href="<?php echo htmlentities(ug_url(array("group" => $key, "page" => 1)), ENT_QUOTES, 'UTF-8')?>" class="<?php if ($filter === $key) echo 'is-active'?>"><?php echo $name === "" ? "지정 안 됨" : htmlentities($name, ENT_QUOTES, 'UTF-8')?> <b><?php echo intval($g[1])?></b></a>
    <?php } ?>
  </nav>

  <div class="ug-layout">
    <section class="admin-card">
      <form class="ug-toolbar" method="get" action="user_group.php">
        <input type="text" name="q" value="<?php echo htmlentities($q, ENT_QUOTES, 'UTF-8')?>" placeholder="아이디, 닉네임, 학교 검색" style="width:240px !important">
        <?php if ($filter !== "") { ?><input type="hidden" name="group" value="<?php echo htmlentities($filter, ENT_QUOTES, 'UTF-8')?>"><?php } ?>
        <button type="submit" class="btn btn-primary">검색</button>
        <?php if ($q !== "") { ?><a class="btn btn-default" href="<?php echo htmlentities(ug_url(array("q" => "", "page" => 1)), ENT_QUOTES, 'UTF-8')?>">검색 지우기</a><?php } ?>
        <span class="admin-field-help"><?php echo $total?>명</span>
      </form>
      <form method="post" action="<?php echo htmlentities(ug_url(array()), ENT_QUOTES, 'UTF-8')?>" id="ug-selected">
        <input type="hidden" name="mode" value="selected">
        <?php require("../include/set_post_key.php"); ?>
        <div class="ug-apply">
          <b id="ug-count">0명 선택</b>
          <input type="text" name="group" list="ug-group-list" maxlength="16" placeholder="<?php echo $MSG_CLASS?> 이름 (예: 2-1)">
          <datalist id="ug-group-list"><?php if (!empty($groups)) foreach ($groups as $g) if ($g[0] !== "") echo "<option value='".htmlentities($g[0], ENT_QUOTES, 'UTF-8')."'>"; ?></datalist>
          <button type="submit" class="btn btn-primary">선택한 학생에게 지정</button>
          <button type="submit" name="clear" value="1" class="btn btn-default" onclick="return confirm('선택한 학생의 <?php echo $MSG_CLASS?>을 비울까요?')"><?php echo $MSG_CLASS?> 비우기</button>
        </div>
        <div class="admin-table-wrap">
          <table class="table ug-table">
            <thead><tr><th><input type="checkbox" id="ug-all" aria-label="전체 선택"></th><th>아이디</th><th>닉네임</th><th>학교</th><th><?php echo $MSG_CLASS?></th><th>맞은 문제</th><th>제출</th><th>최근 접속</th></tr></thead>
            <tbody>
            <?php if (!empty($rows)) foreach ($rows as $r) { ?>
              <tr>
                <td><input type="checkbox" name="users[]" value="<?php echo htmlentities($r['user_id'], ENT_QUOTES, 'UTF-8')?>"></td>
                <td><a href="../userinfo.php?user=<?php echo urlencode($r['user_id'])?>" target="_blank"><?php echo htmlentities($r['user_id'], ENT_QUOTES, 'UTF-8')?></a></td>
                <td><?php echo htmlentities($r['nick'], ENT_QUOTES, 'UTF-8')?></td>
                <td><?php echo htmlentities($r['school'], ENT_QUOTES, 'UTF-8')?></td>
                <td><?php echo $r['group_name'] === "" ? "<span class='ug-none'>지정 안 됨</span>" : "<b>".htmlentities($r['group_name'], ENT_QUOTES, 'UTF-8')."</b>"?></td>
                <td><?php echo intval($r['solved'])?></td>
                <td><?php echo intval($r['submit'])?></td>
                <td><?php echo htmlentities($r['accesstime'], ENT_QUOTES, 'UTF-8')?></td>
              </tr>
            <?php } else { ?>
              <tr><td colspan="8" class="admin-field-help" style="padding:18px;text-align:center">조건에 맞는 사용자가 없습니다.</td></tr>
            <?php } ?>
            </tbody>
          </table>
        </div>
      </form>
      <?php if ($pages > 1) { ?>
      <div class="ug-pager">
        <?php for ($i = 1; $i <= $pages; $i++) { ?>
        <a class="btn <?php echo $i == $page ? 'btn-primary' : 'btn-default'?>" href="<?php echo htmlentities(ug_url(array("page" => $i)), ENT_QUOTES, 'UTF-8')?>"><?php echo $i?></a>
        <?php } ?>
      </div>
      <?php } ?>
    </section>

    <section class="admin-card ug-paste">
      <h4 class="admin-card-title" style="color:var(--admin-text)">목록으로 한 번에 지정</h4>
      <p class="admin-field-help">한 줄에 <code>아이디 <?php echo $MSG_CLASS?></code>를 적습니다. 공백·탭·쉼표로 구분하며, 스프레드시트에서 두 열을 복사해 붙여 넣어도 됩니다. <?php echo $MSG_CLASS?> 칸을 비우면 <?php echo $MSG_CLASS?>을 비웁니다.</p>
      <form method="post" action="<?php echo htmlentities(ug_url(array()), ENT_QUOTES, 'UTF-8')?>">
        <input type="hidden" name="mode" value="paste">
        <?php require("../include/set_post_key.php"); ?>
        <textarea name="list" placeholder="s25001 2-1&#10;s25002 2-1&#10;s25031 2-2"></textarea>
        <div class="admin-form-actions"><button type="submit" class="btn btn-primary">목록대로 지정</button></div>
      </form>
    </section>
  </div>
</div>
<script>
(function () {
  var all = document.getElementById('ug-all'), count = document.getElementById('ug-count');
  var boxes = function () { return document.querySelectorAll('#ug-selected input[name="users[]"]'); };
  function update() { count.textContent = Array.prototype.filter.call(boxes(), function (b) { return b.checked; }).length + '명 선택'; }
  all.addEventListener('change', function () { boxes().forEach(function (b) { b.checked = all.checked; }); update(); });
  document.getElementById('ug-selected').addEventListener('change', function (e) { if (e.target.name === 'users[]') update(); });
})();
</script>
