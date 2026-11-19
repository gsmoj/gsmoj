<?php
// 문제집 목록. 관리자만 쓸 수 있다. 만들기·수정·삭제는 workbook_edit.php에서 한다.
require("admin-header.php");
require_once("../include/workbook.inc.php");
if (!isset($_SESSION[$OJ_NAME.'_'.'administrator'])) {
    echo "<div class='admin-content-page'><div class='alert alert-danger'>관리자만 사용할 수 있습니다.</div></div>";
    exit(1);
}
if (!wb_ready()) {
    echo "<div class='admin-content-page'><div class='alert alert-warning'>문제집 테이블이 아직 없습니다. <a href='update_db.php'>데이터베이스 업데이트</a>를 먼저 실행하세요.</div></div>";
    exit(1);
}
$flash = isset($_SESSION[$OJ_NAME.'_'.'wb_flash']) ? $_SESSION[$OJ_NAME.'_'.'wb_flash'] : null;
unset($_SESSION[$OJ_NAME.'_'.'wb_flash']);
$rows = pdo_query("SELECT w.`workbook_id`, w.`title`, w.`owner`, w.`updated_at`,
        (SELECT COUNT(*) FROM `workbook_problem` wp WHERE wp.`workbook_id`=w.`workbook_id`) AS `problems`
    FROM `workbook` w WHERE w.`defunct`='N' ORDER BY w.`workbook_id` DESC");
if (!is_array($rows)) $rows = array();
?>
<title>문제집 관리</title>
<style>
  .wb-table td, .wb-table th { vertical-align: middle !important; white-space: nowrap; }
  .wb-table td.wb-title { width: 100%; min-width: 220px; white-space: normal; }
  .wb-table td.wb-act { text-align: right; }
  .wb-table .btn { margin: 0 2px; }
</style>
<div class="admin-content-page">
  <div class="admin-page-heading">
    <div>
      <h3>문제집 관리</h3>
      <p>문제를 원하는 순서로 묶어 학생에게 보여 줍니다(예: 기초 100제). 학생은 상단 메뉴의 "문제집"에서 순서대로 풀고 진행도를 확인합니다.</p>
    </div>
    <div class="admin-heading-actions">
      <a class="btn btn-primary" href="workbook_edit.php">새 문제집</a>
    </div>
  </div>

  <?php if ($flash) echo "<div class='alert ".($flash[0] ? "alert-success" : "alert-warning")."'>".wb_h($flash[1])."</div>"; ?>

  <section class="admin-card">
    <div class="admin-table-wrap">
      <table class="table wb-table">
        <thead><tr><th>번호</th><th>제목</th><th>문제</th><th>만든 사람</th><th>수정한 때</th><th style="text-align:right">작업</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r) { $wid = intval($r['workbook_id']); ?>
          <tr>
            <td><?php echo $wid?></td>
            <td class="wb-title"><a href="workbook_edit.php?id=<?php echo $wid?>"><?php echo wb_h($r['title'])?></a></td>
            <td><?php echo intval($r['problems'])?>개</td>
            <td><?php echo wb_h($r['owner'])?></td>
            <td><?php echo wb_h(substr((string)$r['updated_at'], 0, 16))?></td>
            <td class="wb-act">
              <a class="btn btn-default btn-sm" href="workbook_edit.php?id=<?php echo $wid?>">수정</a>
              <a class="btn btn-default btn-sm" href="../workbook.php?id=<?php echo $wid?>" target="_blank">학생 화면</a>
            </td>
          </tr>
        <?php } ?>
        <?php if (!$rows) { ?><tr><td colspan="6" style="color:var(--admin-muted)">아직 문제집이 없습니다. "새 문제집"으로 만들어 보세요.</td></tr><?php } ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
