<?php $show_title = htmlspecialchars($wb['title'], ENT_QUOTES, 'UTF-8')." - 문제집 - $OJ_NAME"; ?>
<?php include("template/$OJ_TEMPLATE/header.php"); ?>
<?php $wb_pct = $wb_total > 0 ? round(100 * $wb_solved / $wb_total) : 0; ?>
<style>
.wb-back { display:inline-flex; align-items:center; gap:4px; margin-bottom:.75rem; color:#6b7280; font-size:14px; }
.wb-back:hover { color:#111827; }
.wb-desc { margin:.5rem 0 0; color:#6b7280; font-size:15px; line-height:1.7; white-space:pre-line; word-break:keep-all; }
.wb-progress { display:flex; align-items:center; gap:14px; max-width:460px; margin-top:1.25rem; }
.wb-progress .wb-track { flex:1 1 auto; height:8px; overflow:hidden; border-radius:999px; background:#e5e7eb; }
.wb-progress .wb-fill { height:100%; border-radius:inherit; background:#2563eb; }
.wb-progress .wb-fill.is-done { background:#16a34a; }
.wb-progress span { color:#374151; font-size:14px; white-space:nowrap; }
.wb-card { padding:.5rem 1.5rem 1rem; border-radius:20px; background:#fff; }
.wb-table { width:100%; border-collapse:collapse; font-size:15px; }
.wb-table th { padding:14px 10px; border-bottom:1px solid #eceef1; color:#6b7280; font-size:13px; font-weight:500; text-align:left; }
.wb-table td { padding:14px 10px; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
.wb-table tr:last-child td { border-bottom:0; }
.wb-table .col-order { width:56px; color:#9ca3af; font-variant-numeric:tabular-nums; }
.wb-table .col-state { width:44px; }
.wb-table .col-id { width:80px; color:#6b7280; }
.wb-table .col-num { width:96px; color:#6b7280; text-align:right; }
.wb-table a.wb-title { color:#111827; font-weight:500; }
.wb-table a.wb-title:hover { color:#2563eb; }
.wb-state { display:inline-flex; width:22px; height:22px; }
.wb-state svg { width:22px; height:22px; }
.wb-locked { display:inline-flex; align-items:center; gap:6px; color:#9ca3af; font-size:14px; }
.wb-locked svg { width:15px; height:15px; }
@media (max-width:767px) {
  .wb-card { padding:.25rem 1rem .75rem; border-radius:16px; }
  .wb-table .col-num, .wb-table .col-id { display:none; }
}
</style>

<div class="gs-page-head">
  <a class="wb-back" href="workbook.php">&lsaquo; 문제집 목록</a>
  <h1><?php echo htmlspecialchars($wb['title'], ENT_QUOTES, 'UTF-8')?></h1>
  <?php if (trim((string)$wb['description']) !== "") { ?><p class="wb-desc"><?php echo htmlspecialchars($wb['description'], ENT_QUOTES, 'UTF-8')?></p><?php } ?>
  <?php if ($wb_logged_in && $wb_total > 0) { ?>
  <div class="wb-progress">
    <div class="wb-track"><div class="wb-fill<?php if ($wb_solved >= $wb_total) echo ' is-done'?>" style="width:<?php echo $wb_pct?>%"></div></div>
    <span><?php echo $wb_solved?> / <?php echo $wb_total?> 해결</span>
  </div>
  <?php } ?>
</div>

<div class="padding">
  <div class="wb-card">
    <table class="wb-table">
      <thead><tr>
        <th class="col-order">순서</th>
        <?php if ($wb_logged_in) { ?><th class="col-state">상태</th><?php } ?>
        <th class="col-id">문제 번호</th>
        <th><?php echo $MSG_TITLE?></th>
        <th class="col-num">맞은 사람</th>
        <th class="col-num"><?php echo $MSG_SUBMIT?></th>
      </tr></thead>
      <tbody>
      <?php foreach ($wb_rows as $r) { ?>
        <tr>
          <td class="col-order"><?php echo $r['order']?></td>
          <?php if ($wb_logged_in) { ?>
          <td class="col-state"><span class="wb-state"><?php
            if ($r['state'] === "ac") { ?><svg viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-label="맞은 문제"><circle cx="12" cy="12" r="9.5"/><path d="m8 12.3 2.7 2.7L16 9.7"/></svg><?php }
            else if ($r['state'] === "tried") { ?><svg viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-label="시도한 문제"><circle cx="12" cy="12" r="9.5"/><path d="m9 9 6 6m0-6-6 6"/></svg><?php }
            else { ?><svg viewBox="0 0 24 24" fill="none" stroke="#c5cad1" stroke-width="2" aria-label="안 푼 문제"><circle cx="12" cy="12" r="9.5"/></svg><?php }
          ?></span></td>
          <?php } ?>
          <?php if ($r['open']) { ?>
          <td class="col-id"><?php echo $r['problem_id']?></td>
          <td><a class="wb-title" href="problem.php?id=<?php echo $r['problem_id']?>"><?php echo htmlentities($r['title'], ENT_QUOTES, 'UTF-8')?></a></td>
          <td class="col-num"><a href="status.php?problem_id=<?php echo $r['problem_id']?>&amp;jresult=4"><?php echo $r['accepted']?></a></td>
          <td class="col-num"><a href="status.php?problem_id=<?php echo $r['problem_id']?>"><?php echo $r['submit']?></a></td>
          <?php } else { ?>
          <td class="col-id">-</td>
          <td><span class="wb-locked"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>지금은 볼 수 없는 문제</span></td>
          <td class="col-num">-</td>
          <td class="col-num">-</td>
          <?php } ?>
        </tr>
      <?php } ?>
      <?php if (empty($wb_rows)) { ?><tr><td colspan="<?php echo $wb_logged_in ? 6 : 5?>" class="gs-empty" style="text-align:center">아직 문제가 없어요</td></tr><?php } ?>
      </tbody>
    </table>
  </div>
</div>

<?php include("template/$OJ_TEMPLATE/footer.php"); ?>
