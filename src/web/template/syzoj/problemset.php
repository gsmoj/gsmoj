<?php $show_title="$MSG_PROBLEMS - $OJ_NAME"; ?>
<?php include("template/$OJ_TEMPLATE/header.php");?>

<style>
body#MainBg-C { background:#f4f5f7 !important; }
.problemset-page { box-sizing:border-box; width:100%; margin:0 auto; padding:2.25rem 0 3rem; color:#1f2937; font-size:15px; }
.problemset-page *, .problemset-page *::before, .problemset-page *::after { box-sizing:border-box; }
.problemset-head h1 { margin:0; color:#111827; font-size:32px; font-weight:700; letter-spacing:-.02em; line-height:1.3; }
.problemset-head p { margin:.5rem 0 0; color:#4b5563; font-size:15px; }
.problemset-toolbar { display:flex; flex-wrap:wrap; align-items:center; gap:.75rem 1rem; margin:2rem 0 1.5rem; }
.problemset-search { position:relative; flex:0 1 400px; min-width:220px; }
.problemset-search svg { position:absolute; top:50%; left:18px; width:20px; height:20px; color:#9ca3af; transform:translateY(-50%); pointer-events:none; }
.problemset-search input[type="search"] { width:100%; height:52px; min-height:52px; padding:0 16px 0 48px !important; border:1px solid transparent; border-radius:14px; outline:0; background:#fff; color:#111827; font-size:15px; font-weight:400; }
.problemset-search input[type="search"]::placeholder { color:#9ca3af; }
.problemset-search input[type="search"]:focus { border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.12); }
.problemset-chips { display:flex; flex-wrap:wrap; gap:.5rem; }
.problemset-chip { display:inline-flex; align-items:center; height:38px; padding:0 16px; border-radius:999px; background:#eceef1; color:#374151; font-size:14px; font-weight:500; white-space:nowrap; transition:background-color .15s ease; }
.problemset-chip:hover { background:#e2e5e9; color:#111827; }
.problemset-chip.active, .problemset-chip.active:hover { background:#111827; color:#fff; }
.problemset-selects { display:flex; flex-wrap:wrap; gap:.75rem; margin-left:auto; }
.problemset-selects select { height:40px; min-height:40px; max-width:12rem; padding:0 38px 0 16px; border:1px solid transparent; border-radius:12px; outline:0; background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E") no-repeat right 12px center; color:#111827; font-size:14px; font-weight:500; text-overflow:ellipsis; cursor:pointer; -webkit-appearance:none; -moz-appearance:none; appearance:none; }
.problemset-selects select:focus { border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.12); }
.problemset-card { padding:.5rem 1.75rem 1.25rem; overflow-x:auto; border-radius:20px; background:#fff; }
.problemset-table { width:100%; margin:0; border-collapse:collapse; color:#374151; font-size:16px; line-height:1.4; }
.problemset-table th { padding:1.1rem .75rem 1rem; border-bottom:1px solid #eef0f2; color:#6b7280; font-size:13px; font-weight:500; text-align:left; white-space:nowrap; }
.problemset-table td { padding:1.15rem .75rem; border-bottom:1px solid #f1f3f5; vertical-align:middle; }
.problemset-table tbody tr:last-child td { border-bottom:0; }
.problemset-table tbody tr { transition:background-color .15s ease; }
.problemset-table tbody tr:hover { background:#fafbfc; }
.problemset-table th:first-child, .problemset-table td:first-child { padding-left:0; }
.problemset-table th:last-child, .problemset-table td:last-child { padding-right:0; }
.problemset-table .col-state { width:72px; }
.problemset-table .col-id { width:96px; color:#4b5563; }
.problemset-table .col-num { width:104px; text-align:right; white-space:nowrap; }
.problemset-table .col-rate { width:160px; text-align:right; white-space:nowrap; }
.problemset-table td.col-num a { color:#4b5563; font-size:14px; }
.problemset-table td.col-num a:hover { color:#2563eb; }
.problem-state { display:inline-flex; vertical-align:middle; }
.problem-state svg { width:22px; height:22px; }
.problem-title-cell { display:flex; flex-wrap:wrap; align-items:center; gap:.4rem .6rem; min-width:0; }
.problem-title-link { color:#111827; font-size:16px; font-weight:500; line-height:1.4; overflow-wrap:anywhere; }
.problem-title-link:hover { color:#2563eb; }
.problem-flag { padding:2px 8px; border-radius:6px; font-size:12px; font-weight:500; white-space:nowrap; }
.problem-flag.is-hidden-problem { background:#fee2e2; color:#b91c1c; }
.problem-flag.is-reward { background:#ffedd5; color:#c2410c; }
.problemset-table .col-tags { width:280px; }
/* 분류 칸: 제목 길이와 상관없이 같은 위치에 같은 크기의 칩으로 보여 준다. */
.problem-tags { display:flex; flex-wrap:wrap; align-items:center; gap:6px; }
.problem-tags a, .problem-tags .label, .problem-tag-more { display:inline-block; height:24px; max-width:8.5em; padding:0 10px; overflow:hidden; border-radius:6px; background:#f1f3f5; color:#4b5563; font-size:12px; font-weight:500; line-height:24px; text-overflow:ellipsis; white-space:nowrap; }
.problem-tags a:hover { background:#e5e7eb; color:#111827; }
.problem-tag-more { background:#fff; box-shadow:inset 0 0 0 1px #e5e7eb; color:#6b7280; cursor:default; }
.problem-tags .label { cursor:pointer; }
.problem-tags-empty { color:#c5cad1; font-size:13px; }
.problem-rate { display:inline-flex; align-items:center; justify-content:flex-end; gap:1rem; }
.problem-rate-track { width:64px; height:5px; overflow:hidden; border-radius:999px; background:#eceef1; }
.problem-rate-fill { height:100%; border-radius:inherit; background:#2563eb; }
.problem-rate-text { min-width:3.2rem; color:#111827; font-size:15px; text-align:right; }
.problemset-empty { padding:3rem 0 2rem !important; color:#6b7280; font-size:15px; text-align:center; }
.problemset-pagination { display:flex; flex-wrap:wrap; align-items:center; justify-content:center; gap:.35rem; margin:1.5rem 0 0; }
.problemset-pagination a, .problemset-pagination span { display:inline-flex; align-items:center; justify-content:center; min-width:36px; height:36px; padding:0 .5rem; border-radius:10px; color:#4b5563; font-size:14px; font-weight:500; }
.problemset-pagination a:hover { background:#e9ebee; color:#111827; }
.problemset-pagination .active, .problemset-pagination .active:hover { background:#fff; color:#111827; }
.problemset-pagination .disabled { color:#c5cad1; }
.problemset-pagination svg { width:16px; height:16px; }
@media (max-width:991px) {
  .problemset-selects { margin-left:0; }
  /* 태블릿 폭에서는 제목이 좁아지지 않도록 맞은 사람/제출 칸을 숨기고 분류 칸을 줄인다. */
  .problemset-table .col-num { display:none; }
  .problemset-table .col-tags { width:180px; }
}
@media (max-width:767px) {
  .problemset-page { padding:1.5rem 1rem 2rem; }
  .problemset-head h1 { font-size:26px; }
  .problemset-toolbar { margin:1.5rem 0 1rem; }
  .problemset-search { flex:1 1 100%; }
  .problemset-selects { width:100%; }
  .problemset-selects select { flex:1 1 0; max-width:none; min-width:0; }
  .problemset-card { padding:.25rem 1rem .75rem; border-radius:16px; }
  .problemset-table { font-size:15px; }
  .problemset-table .col-state { width:40px; }
  .problemset-table .col-id { width:60px; }
  .problemset-table .col-tags { display:none; }
  .problemset-table .col-rate { width:auto; }
  .problem-rate-track { display:none; }
  .problem-title-link { font-size:15px; }
}
</style>

<?php
if (!isset($page)) $page=1;
$page=intval($page);
$section=4;
$start=$page>$section?$page-$section:1;
$end=$page+$section>$view_total_page?$view_total_page:$page+$section;
$ps_logged_in=isset($_SESSION[$OJ_NAME.'_user_id']);
$ps_search=(isset($_GET['search'])&&is_string($_GET['search']))?$_GET['search']:"";
$ps_sorts=array("id"=>"번호순","rate"=>"정답률순","accepted"=>"맞은 사람순","submit"=>"제출순");
$ps_filters=array(""=>"전체","unsolved"=>"안 푼 문제","solved"=>"맞은 문제","tried"=>"시도한 문제");
$ps_categories=$view_categories;
if($view_category!=""&&!in_array($view_category,$ps_categories,true)) array_unshift($ps_categories,$view_category);

// 현재 검색·필터 상태를 유지한 목록 주소를 만든다. 빈 값으로 덮어쓰면 해당 조건을 뺀다.
function problemset_url($overrides=array()){
  global $view_filter,$view_category,$view_sort,$ps_search;
  $query=array("page"=>1,"search"=>trim($ps_search)==""?"":$ps_search,"filter"=>$view_filter,"category"=>$view_category,"sort"=>$view_sort=="id"?"":$view_sort);
  foreach($overrides as $key=>$value) $query[$key]=$value;
  foreach($query as $key=>$value) if($value==="") unset($query[$key]);
  return htmlentities("problemset.php?".http_build_query($query),ENT_QUOTES,'UTF-8');
}

function render_problemset_pagination($page,$start,$end,$view_total_page){
?>
  <nav class="problemset-pagination" aria-label="문제 목록 페이지 이동">
    <?php if($page>1){?><a href="<?php echo problemset_url(array("page"=>$page-1))?>" aria-label="이전 페이지"><?php }else{?><span class="disabled" aria-hidden="true"><?php }?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg><?php echo $page>1?'</a>':'</span>'?>
    <?php for($i=$start;$i<=$end;$i++){ ?>
      <a class="<?php if($page==$i) echo 'active'?>" href="<?php echo problemset_url(array("page"=>$i))?>"<?php if($page==$i) echo ' aria-current="page"'?>><?php echo $i?></a>
    <?php } ?>
    <?php if($page<$view_total_page){?><a href="<?php echo problemset_url(array("page"=>$page+1))?>" aria-label="다음 페이지"><?php }else{?><span class="disabled" aria-hidden="true"><?php }?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg><?php echo $page<$view_total_page?'</a>':'</span>'?>
  </nav>
<?php } ?>

<div class="problemset-page">
  <div class="problemset-head">
    <h1><?php echo $MSG_PROBLEMS?></h1>
    <p><?php if($has_filter){?>조건에 맞는 문제가 <?php echo $view_total_count?>개 있어요<?php }else{?>총 <?php echo $view_total_count?>개의 문제를 풀어볼 수 있어요<?php }?></p>
  </div>

  <form class="problemset-toolbar" id="problemset-filter-form" action="problemset.php" method="get">
    <input type="hidden" name="page" value="1">
    <?php if($view_filter!=""){?><input type="hidden" name="filter" value="<?php echo $view_filter?>"><?php }?>
    <label class="problemset-search">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" name="search" placeholder="문제 번호나 제목으로 검색" aria-label="문제 번호나 제목으로 검색" value="<?php echo htmlentities($ps_search,ENT_QUOTES,'UTF-8')?>">
    </label>
    <?php if($ps_logged_in){?>
    <div class="problemset-chips">
      <?php foreach($ps_filters as $key=>$label){?>
        <a class="problemset-chip<?php if($view_filter===$key) echo ' active'?>" href="<?php echo problemset_url(array("filter"=>$key))?>"><?php echo $label?></a>
      <?php }?>
    </div>
    <?php }?>
    <div class="problemset-selects">
      <select name="category" aria-label="분류">
        <option value="">모든 분류</option>
        <?php foreach($ps_categories as $cat){?>
          <option value="<?php echo htmlentities($cat,ENT_QUOTES,'UTF-8')?>"<?php if($view_category===$cat) echo ' selected'?>><?php echo htmlentities($cat,ENT_QUOTES,'UTF-8')?></option>
        <?php }?>
      </select>
      <select name="sort" aria-label="정렬">
        <?php foreach($ps_sorts as $key=>$label){?>
          <option value="<?php echo $key?>"<?php if($view_sort===$key) echo ' selected'?>><?php echo $label?></option>
        <?php }?>
      </select>
    </div>
  </form>

  <div class="problemset-card">
    <table class="problemset-table">
      <thead><tr>
        <?php if($ps_logged_in){?><th class="col-state">상태</th><?php }?>
        <th class="col-id">번호</th>
        <th><?php echo $MSG_TITLE?></th>
        <th class="col-tags">분류</th>
        <th class="col-rate">정답률</th>
        <th class="col-num">맞은 사람</th>
        <th class="col-num"><?php echo $MSG_SUBMIT?></th>
      </tr></thead>
      <tbody>
      <?php
      foreach($result as $row){
        $problem_id=intval($row['problem_id']);
        $submit_count=intval($row['submit']);
        $accepted_count=intval($row['accepted']);
        $percentage=$submit_count>0?round(100*$accepted_count/$submit_count,1):0;
      ?>
        <tr>
          <?php if($ps_logged_in){?>
          <td class="col-state"><?php if(isset($acc_arr[$problem_id])){?><span class="problem-state" title="맞은 문제"><svg viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9.5"/><path d="m8 12.3 2.7 2.7L16 9.7"/></svg></span><?php }elseif(isset($sub_arr[$problem_id])){?><span class="problem-state" title="시도한 문제"><svg viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9.5"/><path d="m9 9 6 6m0-6-6 6"/></svg></span><?php }else{?><span class="problem-state" title="안 푼 문제"><svg viewBox="0 0 24 24" fill="none" stroke="#c5cad1" stroke-width="2"><circle cx="12" cy="12" r="9.5"/></svg></span><?php }?></td>
          <?php }?>
          <td class="col-id"><?php echo $problem_id?></td>
          <td>
            <div class="problem-title-cell">
              <a class="problem-title-link" href="problem.php?id=<?php echo $problem_id?>"><?php echo htmlentities($row['title'],ENT_QUOTES,'UTF-8')?></a>
              <?php if($row['defunct']=='Y'){?><span class="problem-flag is-hidden-problem">비공개</span><?php }?>
              <?php if(intval($row['coin'])>1){?><span class="problem-flag is-reward">보상 <?php echo min(intval($row['coin']),5)?></span><?php }?>
            </div>
          </td>
          <td class="col-tags">
            <?php
            $ps_tags=array();
            foreach(explode(" ",$row['source']) as $cat){
              $cat=trim($cat);
              if(mb_ereg("^http",$cat)) $cat=trim(get_domain($cat));
              if($cat==""||$cat=="&nbsp") continue;
              $ps_tags[]=$cat;
            }
            // 분류가 많아도 줄 높이가 들쭉날쭉하지 않도록 3개까지만 보이고 나머지는 +N에 모은다.
            $ps_more=array_slice($ps_tags,3);
            ?>
            <div fd="source" pid="<?php echo $problem_id?>" class="problem-tags">
              <?php foreach(array_slice($ps_tags,0,3) as $cat){ ?><a title="<?php echo htmlentities($cat,ENT_QUOTES,'UTF-8')?>" href="<?php echo problemset_url(array("category"=>$cat))?>"><?php echo htmlentities($cat,ENT_QUOTES,'UTF-8')?></a><?php } ?>
              <?php if(count($ps_more)>0){ ?><span class="problem-tag-more" title="<?php echo htmlentities(implode(", ",$ps_more),ENT_QUOTES,'UTF-8')?>">+<?php echo count($ps_more)?></span><?php } ?>
              <?php if(count($ps_tags)==0){ ?><span class="problem-tags-empty">-</span><?php } ?>
            </div>
          </td>
          <td class="col-rate">
            <span class="problem-rate" title="정답률 <?php echo $percentage?>%">
              <span class="problem-rate-track"><span class="problem-rate-fill" style="display:block;width:<?php echo $percentage?>%"></span></span>
              <span class="problem-rate-text"><?php echo number_format($percentage,1)?>%</span>
            </span>
          </td>
          <td class="col-num"><a href="status.php?problem_id=<?php echo $problem_id?>&amp;jresult=4"><?php echo $accepted_count?></a></td>
          <td class="col-num"><a href="status.php?problem_id=<?php echo $problem_id?>"><?php echo $submit_count?></a></td>
        </tr>
      <?php } ?>
      <?php if(empty($result)){?>
        <tr><td class="problemset-empty" colspan="<?php echo $ps_logged_in?7:6?>">조건에 맞는 문제가 없어요</td></tr>
      <?php }?>
      </tbody>
    </table>
  </div>

  <?php if(!isset($_GET['list'])&&$view_total_page>0) render_problemset_pagination($page,$start,$end,$view_total_page); ?>
</div>

<script>
$(function(){
  var $form=$('#problemset-filter-form');
  $form.find('select').on('change',function(){$form.trigger('submit');});
  // 비어 있는 조건은 주소에 남기지 않는다.
  $form.on('submit',function(){
    $form.find('input[name=search],select').each(function(){
      if($.trim(this.value)===''||(this.name==='sort'&&this.value==='id')) this.disabled=true;
    });
  });
  // 뒤로 가기로 돌아왔을 때 꺼 둔 입력을 다시 켠다.
  $(window).on('pageshow',function(){$form.find(':disabled').prop('disabled',false);});
});
</script>
<?php include("template/$OJ_TEMPLATE/footer.php");?>
