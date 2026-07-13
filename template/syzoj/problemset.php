<?php $show_title="$MSG_PROBLEMS - $OJ_NAME"; ?>
<?php include("template/$OJ_TEMPLATE/header.php");?>

<style>
.problemset-page { box-sizing:border-box; width:min(100% - 2rem,1280px); margin:1.25rem auto !important; padding:1.25rem !important; border:1px solid #e2e8f0; border-radius:.75rem !important; background:#fff; box-shadow:none !important; font-size:14px; }
.problemset-page::before { display:none; }
.problemset-page::after { border-radius:inherit; background:#fff !important; box-shadow:none !important; }
.problemset-toolbar { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1.25rem; }
.problemset-searches { display:flex; flex-wrap:wrap; gap:.75rem; }
.problemset-searches .ui.input { width:18rem; }
.problemset-searches .problem-id-input { width:9rem; }
.problemset-searches input { height:40px; font-size:14px !important; }
.problemset-actions { display:flex; align-items:center; gap:.75rem; color:#475569; font-size:14px; white-space:nowrap; }
.problemset-actions .button { font-size:13px !important; }
.problemset-table-wrap { overflow-x:auto; border:1px solid #e2e8f0; border-radius:.65rem; background:#fff; }
.problemset-table { width:100%; margin:0 !important; color:#334155 !important; font-size:14px !important; line-height:1.4; }
.problemset-table thead th { padding:.8rem .75rem !important; background:#f8fafc !important; color:#475569 !important; font-size:12px !important; font-weight:700 !important; letter-spacing:.04em; text-transform:none !important; }
.problemset-table tbody tr { transition:background-color .15s ease; }
.problemset-table tbody tr:hover { background:#f8fbff !important; }
.problemset-table td { padding:.85rem .75rem !important; color:#334155; font-size:14px !important; vertical-align:middle !important; }
.problemset-table td > strong { color:#64748b; font-family:Consolas,monospace; font-size:13px; }
.problem-title-link { color:#1e293b; font-size:15px; font-weight:600; line-height:1.4; }
.problem-title-link:hover { color:#2185d0; }
.problem-tags { float:right; display:inline-flex; flex-wrap:wrap; justify-content:flex-end; gap:.3rem; }
.problem-tags .ui.label, .problemset-table .ui.tiny.label { margin:0 0 0 .35rem; font-size:11px !important; }
.problem-rate { min-width:8rem; }
.problem-rate-track { height:.5rem; overflow:hidden; border-radius:999px; background:#e5e7eb; }
.problem-rate-fill { height:100%; min-width:0; border-radius:inherit; background:linear-gradient(90deg,#21ba45,#16ab39); }
.problem-rate-text { display:block; margin-top:.3rem; color:#6b7280; font-size:.8rem; }
.problemset-pagination { display:flex; justify-content:center; margin:1.5rem 0; }
.problemset-pagination .ui.menu { min-height:36px; box-shadow:none; }
.problemset-pagination .ui.menu .item { min-width:36px; padding:.65rem .8rem; font-size:13px; }
.show_tag_controled.is-hidden { display:none !important; }
@media (max-width:767px) {
  .problemset-page { width:100%; margin:0 !important; padding:1rem !important; border-right:0; border-left:0; border-radius:0 !important; }
  .problemset-toolbar { align-items:stretch; flex-direction:column; }
  .problemset-searches .ui.input, .problemset-searches .problem-id-input { width:100%; }
  .problemset-actions { justify-content:space-between; }
  .problem-tags { display:none; }
  .problemset-table th:nth-last-child(2), .problemset-table td:nth-last-child(2) { display:none; }
}
</style>

<?php
if (!isset($page)) $page=1;
$page=intval($page);
$section=8;
$start=$page>$section?$page-$section:1;
$end=$page+$section>$view_total_page?$view_total_page:$page+$section;
$safe_postfix=htmlentities($postfix,ENT_QUOTES,'UTF-8');

function render_problemset_pagination($page,$start,$end,$view_total_page,$safe_postfix){
?>
  <nav class="problemset-pagination" aria-label="문제 목록 페이지 이동">
    <div class="ui pagination menu">
      <a href="problemset.php?page=1<?php echo $safe_postfix?>" class="<?php if($page==1) echo 'disabled '?>icon item" aria-label="첫 페이지"><i class="fast backward icon"></i></a>
      <a href="<?php if($page>1) echo 'problemset.php?page='.($page-1).$safe_postfix; else echo '#';?>" class="<?php if($page==1) echo 'disabled '?>icon item" aria-label="이전 페이지"><i class="left chevron icon"></i></a>
      <?php for($i=$start;$i<=$end;$i++){ ?>
        <a class="<?php if($page==$i) echo 'active '?>item" href="problemset.php?page=<?php echo $i.$safe_postfix?>"><?php echo $i?></a>
      <?php } ?>
      <a href="<?php if($page<$view_total_page) echo 'problemset.php?page='.($page+1).$safe_postfix; else echo '#';?>" class="<?php if($page==$view_total_page) echo 'disabled '?>icon item" aria-label="다음 페이지"><i class="right chevron icon"></i></a>
      <a href="problemset.php?page=<?php echo $view_total_page.$safe_postfix?>" class="<?php if($page==$view_total_page) echo 'disabled '?>icon item" aria-label="마지막 페이지"><i class="fast forward icon"></i></a>
    </div>
  </nav>
<?php } ?>

<div class="padding problemset-page">
  <div class="problemset-toolbar">
    <div class="problemset-searches">
      <form action="" method="get">
        <div class="ui left icon input">
          <input type="search" name="search" placeholder="문제 제목 또는 분류 검색" value="<?php if(isset($_GET['search'])) echo htmlentities($_GET['search'],ENT_QUOTES,'UTF-8')?>">
          <i class="search icon"></i>
        </div>
      </form>
      <form action="problem.php" method="get">
        <div class="ui left icon input problem-id-input">
          <input type="number" name="id" min="1" placeholder="문제 번호">
          <i class="hashtag icon"></i>
        </div>
      </form>
    </div>
    <div class="problemset-actions">
      <div class="ui toggle checkbox" id="problem-tag-toggle">
        <input type="checkbox" id="problem-tag-toggle-input">
        <label for="problem-tag-toggle-input"><?php echo $MSG_SHOW_TAGS?></label>
      </div>
      <a href="category.php" class="ui labeled icon mini green button"><i class="tags icon"></i><?php echo $MSG_SHOW_ALL_TAGS?></a>
    </div>
  </div>

  <?php if(!isset($_GET['list'])) render_problemset_pagination($page,$start,$end,$view_total_page,$safe_postfix); ?>

  <div class="problemset-table-wrap">
    <table class="ui very basic center aligned table problemset-table">
      <thead><tr>
        <?php if(isset($_SESSION[$OJ_NAME.'_user_id'])){?><th class="one wide"><?php echo $MSG_STATUS?></th><?php }?>
        <th class="one wide"><?php echo $MSG_PROBLEM_ID?></th>
        <th class="left aligned"><?php echo $MSG_TITLE?></th>
        <th class="two wide"><?php echo $MSG_SOVLED.'/'.$MSG_SUBMIT?></th>
        <th class="three wide"><?php echo $MSG_PASS_RATE?></th>
      </tr></thead>
      <tbody>
      <?php
      $i=0;
      foreach($result as $row){
        $problem_id=intval($row['problem_id']);
        $submit_count=intval($row['submit']);
        $accepted_count=intval($row['accepted']);
        $percentage=$submit_count>0?round(100*$accepted_count/$submit_count,1):0;
      ?>
        <tr>
          <?php if(isset($_SESSION[$OJ_NAME.'_user_id'])){?>
          <td><?php if(isset($acc_arr[$problem_id])){?><span class="status accepted" title="해결"><i class="checkmark icon"></i></span><?php }elseif(isset($sub_arr[$problem_id])){?><span class="status wrong_answer" title="시도함"><i class="remove icon"></i></span><?php }else{?><span class="status" title="미제출"><i class="minus icon"></i></span><?php }?></td>
          <?php }?>
          <td><strong><?php echo $problem_id?></strong></td>
          <td class="left aligned">
            <a class="problem-title-link" href="problem.php?id=<?php echo $problem_id?>"><?php echo htmlentities($row['title'],ENT_QUOTES,'UTF-8')?></a>
            <?php if($row['defunct']=='Y'){?><span class="ui tiny red label">비공개</span><?php }?>
            <?php if(intval($row['coin'])>0){?><span class="ui tiny orange label">보상 <?php echo min(intval($row['coin']),5)?></span><?php }?>
            <span class="problem-tags show_tag_controled"><?php echo $view_problemset[$i][3]?></span>
          </td>
          <td><a href="status.php?problem_id=<?php echo $problem_id?>&amp;jresult=4"><?php echo $accepted_count.'/'.$submit_count?></a></td>
          <td class="problem-rate">
            <div class="problem-rate-track" title="정답률 <?php echo $percentage?>%"><div class="problem-rate-fill" style="width:<?php echo $percentage?>%"></div></div>
            <span class="problem-rate-text"><?php echo number_format($percentage,1)?>%</span>
          </td>
        </tr>
      <?php $i++; } ?>
      </tbody>
    </table>
  </div>

  <?php if(!isset($_GET['list'])) render_problemset_pagination($page,$start,$end,$view_total_page,$safe_postfix); ?>
</div>

<script>
$(function(){
  var $toggle=$('#problem-tag-toggle-input');
  function setTagsVisible(visible){
    $toggle.prop('checked',visible);
    $('.show_tag_controled').toggleClass('is-hidden',!visible);
    localStorage.setItem('show_tag',visible?'1':'0');
  }
  setTagsVisible(localStorage.getItem('show_tag')!=='0');
  $toggle.on('change',function(){setTagsVisible(this.checked);});
});
</script>
<?php include("template/$OJ_TEMPLATE/footer.php");?>
