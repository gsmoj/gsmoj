<?php if (!isset($gs_list_page)) $gs_list_page = "contest.php";
$gs_list_title = $gs_list_page == "assessment.php" ? (isset($MSG_ASSESSMENT) ? $MSG_ASSESSMENT : "수행평가") : $MSG_CONTEST;
$show_title="$gs_list_title - $OJ_NAME"; ?>
<?php include("template/$OJ_TEMPLATE/header.php");?>
<?php
      if(!isset($page)) $page=1;
      $page=intval($page);
      $section=8;
      $start=$page>$section?$page-$section:1;
      $end=$page+$section>$view_total_page?$view_total_page:$page+$section;
      $MY=isset($_GET['my'])?"&my":"";
?>
<div class="gs-page-head">
  <h1><?php echo $gs_list_title?></h1>
  <p><?php echo isset($_GET['my']) ? "내가 참여할 수 있는 ".$gs_list_title."만 모아 보여 줘요" : "진행 중이거나 지난 ".$gs_list_title."를 확인할 수 있어요"?></p>
</div>

<form class="gs-toolbar" method=post action=<?php echo $gs_list_page?> >
  <label class="gs-search-box">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
    <input type="search" name="keyword" value="" placeholder="<?php echo str_replace($MSG_CONTEST, $gs_list_title, $MSG_CONTEST_NAME) ?>으로 검색" aria-label="<?php echo str_replace($MSG_CONTEST, $gs_list_title, $MSG_CONTEST_NAME) ?>으로 검색">
  </label>
  <div class="gs-toolbar-end">
    <a class="gs-chip<?php if(!isset($_GET['my'])) echo ' active'?>" href="<?php echo $gs_list_page?>">전체</a>
    <a class="gs-chip<?php if(isset($_GET['my'])) echo ' active'?>" href="<?php echo $gs_list_page?>?my"><?php echo str_replace($MSG_CONTEST, $gs_list_title, $MSG_VIEW_MY_CONTESTS)?></a>
  </div>
</form>

<div class="padding">
  <div class="gs-table-wrap">
    <table class="ui very basic table">
      <thead>
        <tr>
          <th><?php echo $MSG_CONTEST_ID?></th>
          <th><?php echo $MSG_CONTEST_NAME?></th>
          <th><?php echo $MSG_TIME?></th>
          <th><?php echo $MSG_CONTEST_OPEN?></th>
          <th><?php echo $MSG_CONTEST_CREATOR?></th>
        </tr>
      </thead>
      <tbody>
          <?php
            foreach($view_contest as $row){
              echo "<tr>";
              foreach($row as $table_cell){
                echo "<td>";
                echo "\t".$table_cell;
                echo "</td>";
              }
              echo "</tr>";
            }
          ?>
      </tbody>
    </table>
  </div>
  <?php if(empty($view_contest)){?><div class="gs-empty">조건에 맞는 <?php echo $gs_list_title?>가 없어요</div><?php }?>
</div>

<?php if($view_total_page>1){ ?>
<nav class="gs-page-nav" aria-label="<?php echo $gs_list_title?> 목록 페이지 이동">
  <?php if($page>1){ ?><a href="<?php echo $gs_list_page."?page=".strval($page-1).$MY ?>" aria-label="이전 페이지">&lsaquo;</a><?php } else { ?><span class="disabled" aria-hidden="true">&lsaquo;</span><?php } ?>
  <?php for ($i=$start;$i<=$end;$i++){ ?>
  <a class="<?php if($page==$i) echo 'active'?>" href="<?php echo $gs_list_page."?page=".$i.$MY?>"<?php if($page==$i) echo ' aria-current="page"'?>><?php echo $i?></a>
  <?php } ?>
  <?php if($page<$view_total_page){ ?><a href="<?php echo $gs_list_page."?page=".strval($page+1).$MY ?>" aria-label="다음 페이지">&rsaquo;</a><?php } else { ?><span class="disabled" aria-hidden="true">&rsaquo;</span><?php } ?>
</nav>
<?php } ?>

<?php include("template/$OJ_TEMPLATE/footer.php");?>
