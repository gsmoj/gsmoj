<?php $show_title = "코스 - $OJ_NAME"; ?>
<?php include("template/$OJ_TEMPLATE/header.php"); ?>
<style>
/* 코스 하나를 가로 한 줄로 보여 준다. 설명은 한 줄만 보이고 넘치면 …로 줄인다. */
.wb-rows { margin-bottom:2rem; overflow:hidden; border-radius:16px; background:#fff; box-shadow:0 1px 2px rgba(17,24,39,.04); }
.wb-row { display:flex; align-items:center; gap:20px; padding:18px 24px; border-top:1px solid #f1f3f5; color:#111827; text-decoration:none; transition:background-color .15s ease; }
.wb-row:first-child { border-top:0; }
.wb-row:hover { background:#f8fafc; color:#111827; text-decoration:none; }
.wb-row-text { display:flex; flex:1 1 auto; align-items:baseline; gap:16px; min-width:0; }
.wb-row-title { flex:0 1 auto; max-width:40%; overflow:hidden; font-size:16px; font-weight:700; text-overflow:ellipsis; white-space:nowrap; }
.wb-row-desc { flex:1 1 auto; min-width:0; overflow:hidden; color:#6b7280; font-size:14px; text-overflow:ellipsis; white-space:nowrap; }
.wb-row-count { flex:0 0 auto; min-width:5.5em; color:#6b7280; font-size:13px; text-align:right; white-space:nowrap; }
.wb-row-count b { color:#111827; font-weight:600; }
.wb-row-progress { display:flex; flex:0 0 200px; align-items:center; gap:10px; }
.wb-track { flex:1 1 auto; height:6px; overflow:hidden; border-radius:999px; background:#eceef1; }
.wb-fill { height:100%; border-radius:inherit; background:#2563eb; }
.wb-fill.is-done { background:#16a34a; }
.wb-row-progress span { min-width:4.5em; color:#374151; font-size:13px; text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; }
.wb-row-arrow { flex:0 0 auto; color:#c5cad1; font-size:18px; line-height:1; }
@media (max-width:767px) {
  /* 좁은 화면에서는 제목·설명을 윗줄에, 문제 수·진행도를 아랫줄에 둔다. */
  .wb-row { flex-wrap:wrap; gap:8px 12px; padding:16px; }
  .wb-row-text { flex:1 1 100%; flex-direction:column; gap:4px; }
  .wb-row-title { max-width:100%; }
  .wb-row-desc { width:100%; }
  .wb-row-count { text-align:left; }
  .wb-row-progress { flex:1 1 auto; }
  .wb-row-arrow { display:none; }
}
</style>

<div class="gs-page-head">
  <h1>코스</h1>
  <p>정해진 순서대로 풀면서 실력을 쌓아 보세요</p>
</div>

<?php // 목록이 흰 패널(.padding) 안에 겹치지 않도록 회색 배경 위에 바로 놓는다. ?>
<div class="wb-list">
  <?php if (empty($wb_books)) { ?>
  <div class="gs-empty">아직 등록된 코스가 없어요</div>
  <?php } else { ?>
  <div class="wb-rows">
    <?php foreach ($wb_books as $b) {
      $pct = $b['total'] > 0 ? round(100 * $b['solved'] / $b['total']) : 0;
      $title = htmlspecialchars($b['title'], ENT_QUOTES, 'UTF-8');
      // 설명은 한 줄로만 보여 주므로 줄바꿈을 띄어쓰기로 바꾸고, 전체 설명은 title로 띄운다.
      $desc = htmlspecialchars(trim((string)preg_replace('/\s+/u', ' ', $b['description'])), ENT_QUOTES, 'UTF-8');
    ?>
    <a class="wb-row" href="workbook.php?id=<?php echo $b['workbook_id']?>">
      <span class="wb-row-text">
        <span class="wb-row-title" title="<?php echo $title?>"><?php echo $title?></span>
        <?php if ($desc !== "") { ?><span class="wb-row-desc" title="<?php echo $desc?>"><?php echo $desc?></span><?php } ?>
      </span>
      <span class="wb-row-count">문제 <b><?php echo $b['total']?></b>개</span>
      <?php if ($wb_logged_in) { ?>
      <span class="wb-row-progress" title="<?php echo $pct?>% 해결">
        <span class="wb-track"><span class="wb-fill<?php if ($b['total'] > 0 && $b['solved'] >= $b['total']) echo ' is-done'?>" style="display:block; width:<?php echo $pct?>%"></span></span>
        <span><?php echo $b['solved']?> / <?php echo $b['total']?></span>
      </span>
      <?php } ?>
      <span class="wb-row-arrow" aria-hidden="true">&rsaquo;</span>
    </a>
    <?php } ?>
  </div>
  <?php } ?>
</div>

<?php include("template/$OJ_TEMPLATE/footer.php"); ?>
