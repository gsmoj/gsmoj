<?php
// 계정 통합 화면. 남길 계정(대상)으로 없앨 계정(원본)의 제출 기록·권한·DataGSM 연결 등을 옮긴다.
// 미리 보기에서 두 계정을 비교한 뒤 확인해야 통합하며, 원본 계정은 비활성화하고 이메일을 비워 둔다(완전 삭제는 사용자 목록에서 따로 한다).
require("admin-header.php");
if (!isset($_SESSION[$OJ_NAME.'_'.'administrator'])) {
    echo "<div class='admin-content-page'><div class='alert alert-danger'>관리자만 사용할 수 있습니다.</div></div>";
    exit(1);
}
require_once("../include/my_func.inc.php");

// user_id를 가리키는 열. 통합할 때 원본에서 대상으로 모두 바꾼다. privilege·datagsm_user는 겹칠 수 있어 따로 처리한다.
$um_columns = array(
    array('solution', 'user_id'), array('loginlog', 'user_id'), array('assessment_event', 'user_id'),
    array('balloon', 'user_id'), array('printer', 'user_id'), array('share_code', 'user_id'),
    array('openai_task_queue', 'user_id'), array('news', 'user_id'), array('contest', 'user_id'),
    array('assessment', 'owner'), array('mail', 'from_user'), array('mail', 'to_user'),
    array('topic', 'author_id'), array('reply', 'author_id'),
);

function um_user($user_id) {
    $rows = pdo_query("SELECT u.`user_id`,u.`nick`,u.`email`,u.`group_name`,u.`defunct`,u.`reg_time`,u.`accesstime`,
        (SELECT COUNT(*) FROM `solution` s WHERE s.`user_id`=u.`user_id`) AS `submit_cnt`,
        (SELECT COUNT(DISTINCT `problem_id`) FROM `solution` s WHERE s.`user_id`=u.`user_id` AND s.`result`=4) AS `solved_cnt`,
        (SELECT GROUP_CONCAT(`rightstr` SEPARATOR ', ') FROM `privilege` p WHERE p.`user_id`=u.`user_id`) AS `rights`,
        (SELECT `datagsm_id` FROM `datagsm_user` d WHERE d.`user_id`=u.`user_id`) AS `datagsm_id`
        FROM `users` u WHERE u.`user_id`=?", $user_id);
    return (is_array($rows) && count($rows) == 1) ? $rows[0] : null;
}

function um_h($s) {
    return htmlentities(strval($s), ENT_QUOTES, 'UTF-8');
}

$source_id = trim(isset($_REQUEST['source']) ? $_REQUEST['source'] : "");
$target_id = trim(isset($_REQUEST['target']) ? $_REQUEST['target'] : "");
$errors = array();
$messages = array();
$source = $target = null;

if ($source_id !== "" || $target_id !== "") {
    $source = um_user($source_id);
    $target = um_user($target_id);
    if ($source === null) $errors[] = "없앨 계정 '".um_h($source_id)."'을(를) 찾을 수 없습니다.";
    if ($target === null) $errors[] = "남길 계정 '".um_h($target_id)."'을(를) 찾을 수 없습니다.";
    if ($source !== null && $target !== null && strcasecmp($source['user_id'], $target['user_id']) == 0) $errors[] = "같은 계정끼리는 통합할 수 없습니다.";
    if ($source !== null && $source['user_id'] == $_SESSION[$OJ_NAME.'_user_id']) $errors[] = "지금 로그인한 계정은 없앨 계정으로 고를 수 없습니다.";
    if ($source !== null && $target !== null && $source['datagsm_id'] !== null && $target['datagsm_id'] !== null && $source['datagsm_id'] != $target['datagsm_id']) {
        $errors[] = "두 계정이 서로 다른 DataGSM 계정에 연결되어 있어 통합할 수 없습니다.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['confirm']) && !$errors && $source !== null && $target !== null) {
    require_once("../include/check_post_key.php");
    $from = $source['user_id'];
    $to = $target['user_id'];
    pdo_query("START TRANSACTION");
    foreach ($um_columns as $col) {
        pdo_query("UPDATE `{$col[0]}` SET `{$col[1]}`=? WHERE `{$col[1]}`=?", $to, $from);
    }
    // 권한은 대상에 없는 것만 옮기고, 겹치는 원본 권한은 지운다.
    pdo_query("UPDATE `privilege` p SET p.`user_id`=? WHERE p.`user_id`=? AND p.`rightstr` NOT IN (SELECT `rightstr` FROM (SELECT `rightstr` FROM `privilege` WHERE `user_id`=?) t)", $to, $from, $to);
    pdo_query("DELETE FROM `privilege` WHERE `user_id`=?", $from);
    // DataGSM 연결은 대상에 없을 때만 옮긴다. 같은 DataGSM 계정이면 원본 쪽 연결만 지운다.
    if ($target['datagsm_id'] === null) pdo_query("UPDATE `datagsm_user` SET `user_id`=? WHERE `user_id`=?", $to, $from);
    else pdo_query("DELETE FROM `datagsm_user` WHERE `user_id`=?", $from);
    pdo_query("UPDATE `solution` SET `nick`=? WHERE `user_id`=?", $target['nick'], $to);
    pdo_query("UPDATE `users` t JOIN `users` s ON s.`user_id`=? SET t.`coin_earned`=t.`coin_earned`+s.`coin_earned`, t.`coin_bonus`=t.`coin_bonus`+s.`coin_bonus`, t.`coin_spent`=t.`coin_spent`+s.`coin_spent` WHERE t.`user_id`=?", $from, $to);
    pdo_query("UPDATE `users` SET `defunct`='Y', `email`='', `coin_earned`=0, `coin_bonus`=0, `coin_spent`=0, `solved`=0, `submit`=0 WHERE `user_id`=?", $from);
    pdo_query("UPDATE `users` SET `solved`=(SELECT COUNT(DISTINCT `problem_id`) FROM `solution` WHERE `user_id`=? AND `result`=4), `submit`=(SELECT COUNT(*) FROM `solution` WHERE `user_id`=?) WHERE `user_id`=?", $to, $to, $to);
    // 대상 계정에 이메일이 없으면 원본 이메일을 옮긴다. 원본 이메일은 위에서 비웠으므로 겹치지 않는다.
    if ($target['email'] === '' && $source['email'] !== '') pdo_query("UPDATE `users` SET `email`=? WHERE `user_id`=?", $source['email'], $to);
    pdo_query("COMMIT");
    pdo_query("INSERT INTO `loginlog`(user_id,password,ip,time) VALUES(?,?,?,NOW())", $to, substr('merged from ' . $from, 0, 40), $_SERVER['REMOTE_ADDR']);
    $messages[] = "'".um_h($from)."' 계정을 '".um_h($to)."' 계정으로 통합했습니다. '".um_h($from)."' 계정은 비활성화했습니다.";
    $source = um_user($from);
    $target = um_user($to);
    $source_id = $target_id = "";
}

// 고를 수 있는 계정 목록과, 같은 이메일을 쓰는 계정 묶음(빠른 선택용).
$all_users = pdo_query("SELECT `user_id`,`nick`,`defunct` FROM `users` ORDER BY `defunct`, `user_id`");
$dups = pdo_query("SELECT `email`, GROUP_CONCAT(`user_id` ORDER BY `reg_time` SEPARATOR ',') AS `ids` FROM `users` WHERE `email`<>'' GROUP BY `email` HAVING COUNT(*)>1 ORDER BY `email`");
$show_preview = $source !== null && $target !== null && $source_id !== "" && !$errors;

function um_select($name, $selected, $users) {
    echo "<select id='$name' name='$name' class='form-control um-pick'>";
    echo "<option value=''>계정 선택</option>";
    foreach ($users as $u) {
        $label = $u['user_id'] . ($u['nick'] !== '' && $u['nick'] !== $u['user_id'] ? " (" . html_entity_decode($u['nick'], ENT_QUOTES, 'UTF-8') . ")" : "") . ($u['defunct'] == 'Y' ? " - 비활성" : "");
        $sel = strcasecmp($u['user_id'], $selected) == 0 ? " selected" : "";
        echo "<option value='" . um_h($u['user_id']) . "'$sel>" . um_h($label) . "</option>";
    }
    echo "</select>";
}
?>
<title>계정 통합</title>
<script>document.body.classList.add('admin-form-page');</script>
<style>
.um-pickers { display: grid; grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr); gap: 12px; align-items: end; }
.um-swap { height: 34px; padding: 0 12px; font-size: 16px; line-height: 1; }
.um-quick { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin-top: 14px; }
.um-quick-label { color: var(--admin-muted); font-size: 13px; margin-right: 4px; }
.um-compare { margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--admin-border); }
.um-compare th:first-child { width: 120px; color: var(--admin-muted); font-weight: 500; }
.um-compare td.um-diff { background: #fffbeb; }
@media (max-width: 640px) {
  .um-pickers { grid-template-columns: 1fr; }
  .um-swap { justify-self: center; transform: rotate(90deg); }
}
</style>

<div class="admin-form-shell">
<header class="admin-form-title"><h3>사용자 - 계정 통합</h3></header>

<?php foreach ($messages as $m) echo "<div class='alert alert-success'>$m</div>"; ?>
<?php foreach ($errors as $e) echo "<div class='alert alert-danger'>$e</div>"; ?>

<div class="admin-card">
  <p class="admin-field-help">없앨 계정의 제출 기록, 권한, DataGSM 연결, 쪽지·게시글 등을 남길 계정으로 옮깁니다. 없앨 계정은 비활성화되고 이메일이 비워집니다.</p>

  <form id="um-pick-form" action="user_merge.php" method="get">
    <div class="um-pickers">
      <div>
        <label class="admin-field-label" for="source">없앨 계정</label>
        <?php um_select('source', $source_id, $all_users); ?>
      </div>
      <button type="button" id="um-swap" class="btn btn-default um-swap" title="없앨 계정과 남길 계정을 서로 바꿉니다" aria-label="서로 바꾸기">&#8644;</button>
      <div>
        <label class="admin-field-label" for="target">남길 계정</label>
        <?php um_select('target', $target_id, $all_users); ?>
      </div>
    </div>
  </form>

<?php if (is_array($dups) && count($dups) > 0) { ?>
  <div class="um-quick">
    <span class="um-quick-label">같은 이메일을 쓰는 계정</span>
<?php
    foreach ($dups as $row) {
        // 먼저 가입한 계정을 남길 계정으로 채운다. 반대로 하려면 바꾸기 버튼을 누른다.
        $ids = explode(',', $row['ids']);
        $keep = $ids[0];
        $drop = $ids[1];
        $title = um_h(html_entity_decode($row['email'], ENT_QUOTES, 'UTF-8'));
        echo "<button type='button' class='btn btn-default btn-xs um-fill' data-source='" . um_h($drop) . "' data-target='" . um_h($keep) . "' title='$title'>" . um_h(implode(" · ", $ids)) . "</button>";
    }
?>
  </div>
<?php } ?>

<?php if ($show_preview) { ?>
  <form class="um-compare" action="user_merge.php" method="post" onsubmit="return confirm('<?php echo um_h($source['user_id'])?> 계정을 <?php echo um_h($target['user_id'])?> 계정으로 통합할까요?\n되돌릴 수 없습니다.');">
    <div class="admin-table-wrap">
    <table class="table table-condensed">
      <thead><tr><th></th><th>없앨 계정</th><th>남길 계정</th></tr></thead>
      <tbody>
<?php
    $fields = array('user_id' => $MSG_USER_ID, 'nick' => $MSG_NICK, 'email' => $MSG_EMAIL, 'group_name' => '반', 'reg_time' => '가입일',
        'accesstime' => '마지막 접속', 'submit_cnt' => '제출 수', 'solved_cnt' => '맞힌 문제 수', 'rights' => '권한', 'datagsm_id' => 'DataGSM 연결');
    foreach ($fields as $key => $label) {
        $fmt = function ($u) use ($key) {
            if ($key === 'datagsm_id') return $u[$key] === null ? "없음" : "연결됨";
            if ($key === 'user_id' && $u['defunct'] == 'Y') return um_h($u[$key]) . " <span class='admin-badge admin-badge--off'>비활성</span>";
            if ($key === 'email') return um_h(html_entity_decode($u[$key], ENT_QUOTES, 'UTF-8'));
            return um_h($u[$key]);
        };
        // 값이 다른 칸은 색으로 표시해 무엇이 바뀌는지 바로 보이게 한다.
        $diff = strval($source[$key]) !== strval($target[$key]) && $key !== 'user_id' ? " class='um-diff'" : "";
        echo "<tr><th>" . um_h($label) . "</th><td$diff>" . $fmt($source) . "</td><td$diff>" . $fmt($target) . "</td></tr>";
    }
?>
      </tbody>
    </table>
    </div>
    <input type="hidden" name="source" value="<?php echo um_h($source['user_id'])?>">
    <input type="hidden" name="target" value="<?php echo um_h($target['user_id'])?>">
    <?php require_once("../include/set_post_key.php");?>
    <div class="admin-form-actions">
      <button name="confirm" type="submit" value="1" class="btn btn-danger">통합하기</button>
    </div>
  </form>
<?php } ?>
</div>

</div>

<script>
// 두 계정을 모두 고르면 바로 미리 보기로 넘어간다.
(function () {
  var form = document.getElementById('um-pick-form');
  var source = document.getElementById('source');
  var target = document.getElementById('target');
  function go() { if (source.value !== '' && target.value !== '') form.submit(); }
  source.addEventListener('change', go);
  target.addEventListener('change', go);
  document.getElementById('um-swap').addEventListener('click', function () {
    var v = source.value; source.value = target.value; target.value = v; go();
  });
  var fills = document.querySelectorAll('.um-fill');
  for (var i = 0; i < fills.length; i++) {
    fills[i].addEventListener('click', function () {
      source.value = this.getAttribute('data-source'); target.value = this.getAttribute('data-target'); go();
    });
  }
})();
</script>
