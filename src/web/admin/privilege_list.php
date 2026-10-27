<?php
require("admin-header.php");
require_once("../include/set_get_key.php");

if(!isset($_SESSION[$OJ_NAME.'_'.'administrator'])){
  exit(1);
}

if(isset($OJ_LANG)){
  require_once("../lang/$OJ_LANG.php");
}
?>

<title><?php echo $MSG_PRIVILEGE." ".$MSG_LIST?></title>

<div class='padding admin-content-page'>
<div class="admin-page-heading">
  <div><h3><?php echo $MSG_USER." - ".$MSG_PRIVILEGE." - ".$MSG_LIST?></h3><p><?php echo $MSG_HELP_PRIVILEGE_LIST?></p></div>
  <a href="privilege_add.php" class="btn btn-primary"><?php echo $MSG_PRIVILEGE." ".$MSG_ADD?></a>
</div>

<?php
$sql = "SELECT COUNT(*) AS ids FROM privilege WHERE rightstr IN ('administrator','source_browser','contest_creator','user_adder','http_judge','problem_editor','tag_adder','problem_importer','problem_verifiter','password_setter','printer','balloon','vip','problem_start','problem_end','service_port') ORDER BY user_id, rightstr";
$result = pdo_query($sql);
$row = $result[0];

$ids = intval($row['ids']);

$idsperpage = 25;
$pages = intval(ceil($ids/$idsperpage));

if(isset($_GET['page'])){ $page = intval($_GET['page']);}
else{ $page = 1;}

$pagesperframe = 5;
$frame = intval(ceil($page/$pagesperframe));

$spage = ($frame-1)*$pagesperframe+1;
$epage = min($spage+$pagesperframe-1, $pages);

$sid = ($page-1)*$idsperpage;

$sql = "";
if(isset($_GET['keyword']) && $_GET['keyword']!=""){
  $keyword = $_GET['keyword'];
  $keyword = "%$keyword%";
  $sql = "SELECT * FROM privilege WHERE (user_id LIKE ?) OR (rightstr LIKE ?) ORDER BY user_id, rightstr";
  $result = pdo_query($sql,$keyword,$keyword);
}else{
  $sql = "SELECT * FROM privilege WHERE rightstr IN ('administrator','source_browser','contest_creator','user_adder','http_judge','problem_editor','tag_adder','problem_importer','password_setter','printer','balloon','vip','problem_start','problem_end','service_port') ORDER BY user_id, rightstr LIMIT $sid, $idsperpage";
  $result = pdo_query($sql);
}
?>

<form action=privilege_list.php class="form-search form-inline admin-list-search">
  <input type="text" name=keyword class="form-control search-query"
    value="<?php echo htmlentities($_GET['keyword']??"", ENT_QUOTES, 'UTF-8')?>"
    placeholder="<?php echo $MSG_USER_ID.', '.$MSG_PRIVILEGE?>">
  <button type="submit" class="btn btn-primary"><?php echo $MSG_SEARCH?></button>
</form>

  <div class="admin-table-wrap">
  <table class="table admin-privilege-table">
    <thead>
    <tr>
      <th><?php echo $MSG_USER_ID?></th>
      <th><?php echo $MSG_PRIVILEGE?></th>
      <th><?php echo $MSG_DELETE?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    foreach($result as $row){
      echo "<tr>";
        echo "<td>".$row['user_id']."</td>";
        echo "<td><span class='admin-badge admin-badge--info'>".$row['rightstr'];
	if($row['valuestr']!="true") echo ":".$row['valuestr'];
	echo "</span></td>";
	if($row['rightstr']!="administrator"  ||$row['user_id']!=$_SESSION[$OJ_NAME."_user_id"])
        	echo "<td><a href='privilege_delete.php?uid=".htmlentities($row['user_id'],ENT_QUOTES,"UTF-8")."&rightstr={$row['rightstr']}&getkey=".$_SESSION[$OJ_NAME.'_'.'getkey']."' class='btn btn-default btn-xs'>".$MSG_DELETE."</a></td>";
	else
		echo "<td class='admin-field-help'>본인 관리자 권한은 삭제할 수 없음</td>";
	echo "</tr>";
    }
    ?>
    </tbody>
  </table>
  </div>

<?php
if(!(isset($_GET['keyword']) && $_GET['keyword']!=""))
{
  echo "<div style='display:inline;'>";
  echo "<nav class='center'>";
  echo "<ul class='pagination pagination-sm'>";
  echo "<li class='page-item'><a href='privilege_list.php?page=".(strval(1))."'>&lt;&lt;</a></li>";
  echo "<li class='page-item'><a href='privilege_list.php?page=".($page==1?strval(1):strval($page-1))."'>&lt;</a></li>";
  for($i=$spage; $i<=$epage; $i++){
    echo "<li class='".($page==$i?"active ":"")."page-item'><a title='go to page' href='privilege_list.php?page=".$i."'>".$i."</a></li>";
  }
  echo "<li class='page-item'><a href='privilege_list.php?page=".($page==$pages?strval($page):strval($page+1))."'>&gt;</a></li>";
  echo "<li class='page-item'><a href='privilege_list.php?page=".(strval($pages))."'>&gt;&gt;</a></li>";
  echo "</ul>";
  echo "</nav>";
  echo "</div>";
}
?>

</div>
