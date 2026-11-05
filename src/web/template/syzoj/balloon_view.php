<?php 
  $show_title="$MSG_ERROR_INFO - $OJ_NAME";
  include("template/$OJ_TEMPLATE/header.php");
?>
<div class="ui positive icon message">
  <i class="check icon"></i>
  <div class="content">
<h1><?php echo $MSG_BALLOON_TICKET?></h1>
<?php
echo "<h2>".htmlentities(str_replace("\n\r","\n",$view_user),ENT_QUOTES,"utf-8")."\n";
echo "-".htmlentities(str_replace("\n\r","\n",$view_school),ENT_QUOTES,"utf-8")."-".htmlentities(str_replace("\n\r","\n",$view_nick),ENT_QUOTES,"utf-8")."\n"."</h2>";
echo $MSG_PROBLEM." ".$PID[$view_pid]."<br>";
if(isset($_GET['fb']) && intval($_GET['fb'])==1){
echo $MSG_BALLOON." ".$MSG_COLOR.": <font color='".$ball_color[$view_pid]."'>".$ball_name[$view_pid]." $MSG_FIRST_BLOOD! </font><br>";
}else{
echo $MSG_BALLOON." ".$MSG_COLOR.": <font color='".$ball_color[$view_pid]."'>".$ball_name[$view_pid]."</font><br>";
}
?>
<input onclick="window.print();" type="button" value="<?php echo $MSG_PRINTER?>">
<input onclick="location.href='balloon.php?id=<?php echo $id?>&cid=<?php echo $cid?>';" type="button" value="<?php echo $MSG_PRINT_DONE?>">
<img src="image/wx.jpg" height="100px" width="100px">
<?php echo $view_map?>

<script>
  $("td:contains(<?php echo $view_user?>)").css("background-color","<?php echo $ball_color[$view_pid]?>");

</script>
  </div>
</div>

<?php include("template/$OJ_TEMPLATE/footer.php");?>
