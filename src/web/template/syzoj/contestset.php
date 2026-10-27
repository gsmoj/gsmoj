<?php if (!isset($gs_list_page)) $gs_list_page = "contest.php";
$gs_list_title = $gs_list_page == "assessment.php" ? (isset($MSG_ASSESSMENT) ? $MSG_ASSESSMENT : "수행평가") : $MSG_CONTEST;
$show_title="$gs_list_title - $OJ_NAME"; ?>
<?php include("template/$OJ_TEMPLATE/header.php");?>
<div class="padding">
<div class="ui grid" style="margin-bottom: 10px; ">
    <div class="row" style="white-space: nowrap; ">
      <div class="seven wide column">
          <form method=post action=<?php echo $gs_list_page?> >
            <div class="ui search" style="width: 280px; height: 28px; margin-top: -5.3px; ">
              <div class="ui left icon input" style="width: 100%; ">
                <input class="prompt" style="width: 100%; " type="text" value="" placeholder=" <?php echo $MSG_CONTEST_NAME ?> …" name="keyword">
                <i class="search icon"></i>&nbsp;&nbsp;&nbsp;
                <?php
                if(isset($_GET['my'])){
                
                echo '<a  class="ui button primary"  href="'.$gs_list_page.'" >'.str_replace($MSG_CONTEST, $gs_list_title, $MSG_VIEW_ALL_CONTESTS) .'</a>';
                }else{
                
                echo '<a  class="ui button red"  href="'.$gs_list_page.'?my" >'.str_replace($MSG_CONTEST, $gs_list_title, $MSG_VIEW_MY_CONTESTS) .'</a>';
                };
                ?>
              </div>
              <div class="results" style="width: 100%; "></div>
            </div>
          </form>

      </div>

      <div class="nine wide right aligned column">

      </div>
    </div>
  </div>

      <div style="margin-bottom: 30px; ">
    
    <?php
      if(!isset($page)) $page=1;
      $page=intval($page);
      $section=8;
      $start=$page>$section?$page-$section:1;
      $end=$page+$section>$view_total_page?$view_total_page:$page+$section;
      $MY=isset($_GET['my'])?"&my":"";
    ?>
<div style="text-align: center; ">
  <div class="ui pagination menu" style="box-shadow: none; ">
    <a class="<?php if($page==1) echo "disabled "; ?>icon item" href="<?php if($page<>1) echo $gs_list_page."?page=".strval($page-1).$MY ?>" id="page_prev">
      <i class="left chevron icon"></i>
    </a>
    <?php
      for ($i=$start;$i<=$end;$i++){
        echo "<a class=\"".($page==$i?"active ":"")."item\" href=\"".$gs_list_page."?page=".$i.$MY."\">".$i."</a>";
      }
    ?>
    <a class="<?php if($page==$view_total_page) echo "disabled "; ?> icon item" href="<?php if($page<>$view_total_page) echo $gs_list_page."?page=".strval($page+1).$MY; ?>" id="page_next">
    <i class="right chevron icon"></i>
    </a>
  </div>
</div>

</div>
    <table class="ui very basic center aligned table">
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

<?php include("template/$OJ_TEMPLATE/footer.php");?>
