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

<title><?php echo $MSG_NEWS." ".$MSG_LIST?></title>

<div class='padding admin-content-page'>
<div class="admin-page-heading">
  <div><h3><?php echo $MSG_NEWS." ".$MSG_LIST?></h3><p><?php echo $MSG_HELP_NEWS_LIST?></p></div>
  <a href="news_add_page.php" class="btn btn-primary"><?php echo $MSG_NEWS." ".$MSG_ADD?></a>
</div>

<?php
$sql = "SELECT COUNT('news_id') AS ids FROM `news`";
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
  $sql = "SELECT `news_id`,`user_id`,`title`,`time`,`defunct`,`importance`,`menu` FROM `news` WHERE (title LIKE ?) OR (content LIKE ?) ORDER BY `news_id` DESC";
  $result = pdo_query($sql,$keyword,$keyword);
}else{
  $sql = "SELECT `news_id`,`user_id`,`title`,`time`,`defunct`,`importance`,`menu` FROM `news` ORDER BY `news_id` DESC LIMIT $sid, $idsperpage";
  $result = pdo_query($sql);
}
?>

<form action=news_list.php class="form-search form-inline admin-list-search">
  <input type="text" name='keyword' class="form-control search-query"
    value="<?php echo htmlentities($_GET['keyword']??"", ENT_QUOTES, 'UTF-8')?>"
    placeholder="<?php echo $MSG_TITLE.', '.$MSG_CONTENTS?>">
  <button type="submit" class="btn btn-primary"><?php echo $MSG_SEARCH?></button>
</form>

  <div class="admin-table-wrap">
  <table class="table admin-news-table">
    <thead>
    <tr>
      <th>번호</th>
      <th class="admin-news-title"><?php echo $MSG_TITLE?></th>
      <th>수정일</th>
      <th>게시 상태</th>
      <th>중요도</th>
      <th>메뉴 표시</th>
      <th><?php echo $MSG_COPY?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    foreach($result as $row){
      echo "<tr news_id='".$row['news_id']."'>";
        echo "<td>".$row['news_id']."</td>";
        echo "<td class='admin-news-title'><a href='news_edit.php?id=".$row['news_id']."'>".htmlentities($row['title']==""?"(제목 없음)":$row['title'])."</a>"."</td>";
        echo "<td class='admin-news-time'>".$row['time']."</td>";
        echo "<td><a href=news_df_change.php?id=".$row['news_id']."&getkey=".htmlentities($_SESSION[$OJ_NAME.'_'.'getkey'], ENT_QUOTES, 'UTF-8')." title='눌러서 게시 상태 전환'>".($row['defunct']=="N"?"<span class='admin-badge admin-badge--on'>게시 중</span>":"<span class='admin-badge admin-badge--off'>숨김</span>")."</a>"."</td>";
        echo "<td fd='importance' class='admin-inline-edit' title='더블클릭하여 수정'>" . ($row['importance']). "</td>";
        echo "<td fd='menu'>" . ($row['menu'] == 1 ? "<span class='admin-badge admin-badge--info'>표시</span>" : "<span class='admin-badge admin-badge--off'>미표시</span>") . "</td>";
        echo "<td><a class='btn btn-default btn-sm' href=news_add_page.php?cid=".$row['news_id'].">".$MSG_COPY."</a></td>";
      echo "</tr>";
    }
    ?>
    </tbody>
  </table>
  </div>
<p class="admin-field-help admin-news-help"><?php echo $MSG_HELP_ADD_FAQS?></p>

<?php
if(!(isset($_GET['keyword']) && $_GET['keyword']!=""))
{
  echo "<div style='display:inline;'>";
  echo "<nav class='center'>";
  echo "<ul class='pagination pagination-sm'>";
  echo "<li class='page-item'><a href='news_list.php?page=".(strval(1))."'>&lt;&lt;</a></li>";
  echo "<li class='page-item'><a href='news_list.php?page=".($page==1?strval(1):strval($page-1))."'>&lt;</a></li>";
  for($i=$spage; $i<=$epage; $i++){
    echo "<li class='".($page==$i?"active ":"")."page-item'><a title='go to page' href='news_list.php?page=".$i."'>".$i."</a></li>";
  }
  echo "<li class='page-item'><a href='news_list.php?page=".($page==$pages?strval($page):strval($page+1))."'>&gt;</a></li>";
  echo "<li class='page-item'><a href='news_list.php?page=".(strval($pages))."'>&gt;&gt;</a></li>";
  echo "</ul>";
  echo "</nav>";
  echo "</div>";
}
?>

</div>
<script>
function admin_mod(){
	var tb_name="news";
	var fd_name="importance";
	// ajax.php는 news의 importance 필드만 인라인 수정을 허용한다. 메뉴 표시는 수정 화면에서 바꾼다.
	let fd_array = ["importance"];

	for (let fd_name of fd_array) {
		$("td[fd="+fd_name+"]").each(function(){
			let sp=$(this);
			let fd=$(this).attr("fd");
			let data_id=$(this).parent().attr(tb_name+'_id');
			$(this).dblclick(function(){
				let data=sp.text();
				sp.html("<form onsubmit='return false;'><input type=hidden name='m' value='"+tb_name+"_update_"+fd+"'><input type='hidden' name='"+tb_name+"_id' value='"+data_id+"'><input type='text' name='"+fd+"' value='"+data+"' selected='true' class='input-mini' size=20 ></form>");
				let ipt=sp.find("input[name="+fd+"]");
				ipt.focus();
				ipt[0].select();
				sp.find("input").change(function(){
					let newdata=sp.find("input[name="+fd+"]").val();
					$.post("ajax.php",sp.find("form").serialize()).done(function(){
						console.log("new "+fd );
						sp.html(newdata);
					});

				});
			});
		});
	}
}
$(document).ready(function(){
        admin_mod();
});

</script>

