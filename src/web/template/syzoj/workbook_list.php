<?php $show_title = "문제집 - $OJ_NAME"; ?>
<?php include("template/$OJ_TEMPLATE/header.php"); ?>
<style>
.wb-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:16px; margin-bottom:2rem; }
.wb-card { display:flex; flex-direction:column; gap:10px; padding:20px 22px; border-radius:16px; background:#fff; color:#111827; text-decoration:none; box-shadow:0 1px 2px rgba(17,24,39,.04); transition:box-shadow .15s ease, transform .15s ease; }
.wb-card:hover { color:#111827; text-decoration:none; box-shadow:0 6px 20px rgba(17,24,39,.08); transform:translateY(-1px); }
.wb-card h2 { margin:0; font-size:18px; font-weight:700; line-height:1.4; word-break:keep-all; }
.wb-card p { display:-webkit-box; margin:0; overflow:hidden; color:#6b7280; font-size:14px; line-height:1.6; -webkit-line-clamp:2; -webkit-box-orient:vertical; word-break:keep-all; }
.wb-meta { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-top:auto; color:#6b7280; font-size:13px; }
.wb-meta b { color:#111827; font-weight:600; }
.wb-track { height:6px; overflow:hidden; border-radius:999px; background:#eceef1; }
.wb-fill { height:100%; border-radius:inherit; background:#2563eb; }
.wb-fill.is-done { background:#16a34a; }
</style>

<div class="gs-page-head">
  <h1>문제집</h1>
  <p>정해진 순서대로 풀면서 실력을 쌓아 보세요</p>
</div>

<?php // 카드가 흰 패널(.padding) 안에 겹치지 않도록 회색 배경 위에 바로 놓는다. ?>
<div class="wb-list">
  <?php if (empty($wb_books)) { ?>
  <div class="gs-empty">아직 등록된 문제집이 없어요</div>
  <?php } else { ?>
  <div class="wb-grid">
    <?php foreach ($wb_books as $b) {
      $pct = $b['total'] > 0 ? round(100 * $b['solved'] / $b['total']) : 0;
    ?>
    <a class="wb-card" href="workbook.php?id=<?php echo $b['workbook_id']?>">
      <h2><?php echo htmlspecialchars($b['title'], ENT_QUOTES, 'UTF-8')?></h2>
      <?php if (trim($b['description']) !== "") { ?><p><?php echo htmlspecialchars($b['description'], ENT_QUOTES, 'UTF-8')?></p><?php } ?>
      <div class="wb-meta">
        <span>문제 <b><?php echo $b['total']?></b>개</span>
        <?php if ($wb_logged_in) { ?><span><b><?php echo $b['solved']?></b> / <?php echo $b['total']?> 해결</span><?php } ?>
      </div>
      <?php if ($wb_logged_in) { ?>
      <div class="wb-track" title="<?php echo $pct?>% 해결"><div class="wb-fill<?php if ($b['total'] > 0 && $b['solved'] >= $b['total']) echo ' is-done'?>" style="width:<?php echo $pct?>%"></div></div>
      <?php } ?>
    </a>
    <?php } ?>
  </div>
  <?php } ?>
</div>

<?php include("template/$OJ_TEMPLATE/footer.php"); ?>
