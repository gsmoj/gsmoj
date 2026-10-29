<?php $show_title="$MSG_RANKLIST - $OJ_NAME"; ?>
<?php include("template/$OJ_TEMPLATE/header.php");?>

<?php
  $gs_rank_scopes=array(""=>"전체","d"=>"일간","w"=>"주간","m"=>"월간","y"=>"연간");
  $gs_rank_prefix=htmlentities(isset($_GET['prefix'])?$_GET['prefix']:"",ENT_QUOTES,"utf-8");
  $gs_rank_group=htmlentities(isset($_GET['group_name'])?$_GET['group_name']:"",ENT_QUOTES,"utf-8");
?>
<div class="gs-page-head">
  <h1><?php echo $MSG_RANKLIST?></h1>
  <p>맞힌 문제 수를 기준으로 순위를 보여 줘요</p>
</div>

<form action="ranklist.php" class="gs-toolbar" method="get" role="search">
  <label class="gs-search-box gs-search-box-sm">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
    <input type="search" name="prefix" placeholder="<?php echo $MSG_USER?>로 검색" aria-label="<?php echo $MSG_USER?>로 검색" value="<?php echo $gs_rank_prefix ?>">
  </label>
  <label class="gs-search-box gs-search-box-sm">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
    <input type="search" name="group_name" placeholder="<?php echo $MSG_GROUP_NAME ?>으로 검색" aria-label="<?php echo $MSG_GROUP_NAME ?>으로 검색" value="<?php echo $gs_rank_group ?>">
  </label>
  <button class="gs-sr-only" type="submit"><?php echo $MSG_SEARCH?></button>
  <div class="gs-toolbar-end">
    <?php foreach($gs_rank_scopes as $gs_key=>$gs_label){ ?>
    <a class="gs-chip<?php if($scope===$gs_key) echo ' active'?>" href="ranklist.php<?php if($gs_key!=="") echo "?scope=".$gs_key?>"><?php echo $gs_label?></a>
    <?php } ?>
  </div>
</form>

<div class="padding">
  <div class="gs-table-wrap">
	    <table class="ui very basic center aligned table" style="table-layout: fixed; ">
	        <thead>
	        <tr>
	            <th style="width: 60px; "><?php echo $MSG_Number?></th>
	            <th style="width: 180px; "><?php echo $MSG_USER?></th>
	            <th><?php echo $MSG_NICK?></th>
			<th><?php echo $MSG_GROUP_NAME?></th>
              <th style="width: 100px; "><?php echo $MSG_SOVLED?></th>
              <th style="width: 100px; "><?php echo $MSG_SUBMIT?></th>
              <th style="width: 100px; "><?php echo $MSG_RATIO?></th>
	        </tr>
	        </thead>
	        <tbody>
          <?php
          foreach($view_rank as $row){
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
</div>

<?php if($view_total>$page_size){ ?>
<nav class="gs-page-nav" aria-label="순위 페이지 이동">
    <?php
    $gs_rank_start=isset($_GET['start'])?intval($_GET['start']):0;
    for($i = 0; $i <$view_total ; $i += $page_size) {
    $str= "<a class=\"".($gs_rank_start==$i?"active":"")."\" href='./ranklist.php?start=" . strval ( $i ).($scope?"&scope=$scope":"") . "'>";
    $str.= strval ( $i + 1 );
    $str.= "-";
    $str.= strval ( $i + $page_size );
    $str.= "</a>";
    echo $str;
    }
    ?>
</nav>
<?php } ?>

<?php include("template/$OJ_TEMPLATE/footer.php");?>
