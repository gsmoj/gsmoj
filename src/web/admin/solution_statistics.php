<?php
require("admin-header.php");
require_once("../include/set_get_key.php");

if(!(isset($_SESSION[$OJ_NAME.'_'.'administrator'])||isset($_SESSION[$OJ_NAME.'_'.'contest_creator']))){
  exit(1);
}

if(isset($OJ_LANG)){
  require_once("../lang/$OJ_LANG.php");
}
?>

<title>채점 통계 리포트</title>

<div class='padding admin-content-page'>
<div class="admin-page-heading">
  <div><h3>채점 통계 리포트</h3><p>문제 번호와 사용자 목록, 또는 대회를 골라 문제별 정답 여부를 표로 확인합니다.</p></div>
</div>

<?php
$cid=intval($_GET['cid']);
if(isset($_POST['pids'])||$cid>0){

  $user_ida = explode("\n", trim($_POST['ulist']));
  $user_ida = array_unique($user_ida);
  $user_ids="";
  if(count($user_ida)>0 && strlen($user_ida[0])>0){
  $len=count($user_ida);	  
    for($i=0; $i<$len; $i++){
      if($user_ids) $user_ids.=",";
      $user_ids.="?";
      $user_ida[$i]=trim($user_ida[$i]);
    }
  }
  //echo implode(",",$user_ida),"<br>";
  
	  $sql="select user_id,nick ";

  if($cid>0){
	  $pida=array();
	  $result=pdo_query("select problem_id from contest_problem where contest_id=? order by num",$cid);
	  foreach($result as $row){
	  	array_push($pida,$row['problem_id']);
	  }

  }else{
	  $pida=array_unique(explode(',',$_POST['pids']));
	  $len=count($pida);
	  for($i=0;$i<$len;$i++){
		$pida[$i]=intval($pida[$i]);
	  }
  }

  $pida=array_unique($pida);

  foreach($pida as $pid){
  	$sql.=" ,min(case problem_id when $pid then result else 15 end) P$pid";
  }
  //$user_ids=implode("','",$user_ida);
  if(isset($_GET['cid'])){
	  $sql.=" from solution where contest_id=? group by user_id,nick order by user_id";
  	  $result = pdo_query($sql,$cid);

  }else{
	  $sql.=" from solution where user_id in ($user_ids) group by user_id,nick ";
  	  $result = pdo_query($sql,$user_ida);
  }
//  echo $sql;
?>


<div class="admin-table-wrap">
  <table class="table admin-stats-table">
    <thead>
    <tr>
    <?php
      echo "<th>".$MSG_USER_ID."</th>";
      echo "<th>".$MSG_NICK."</th>";
  	foreach($pida as $pid){
      		echo "<th class='pid' value='$pid'>P".$pid;
      		echo "</th>";
	}
      echo "</tr>";
      echo "</thead><tbody>";
 // var_dump($result);
    foreach($result as $row){
      echo "<tr>";
      echo "<td>".$row['user_id']."</td>";
      echo "<td>".$row['nick']."</td>";
  	foreach($pida as $pid){
      		echo "<td>";
      		if($row["P$pid"]==4) echo "<span class='admin-badge admin-badge--on' >정답</span>";
		else if($row["P$pid"]==15) echo "&nbsp;";
		else echo "<span class='admin-badge admin-badge--warn' >오답</span>";
      		echo "</td>";
	}
      echo "</tr>";
    }
  ?>
    </tbody>
</table>
</div>
<div class="admin-form-actions admin-stats-actions">
  <a class="btn btn-default" href="javascript:history.go(-1);" >돌아가기</a>
</div>
<script>
	function showTitles(){
		$('.pid').each(function(){
			let v=$(this).attr('value');
			let title=$.ajax({url:"ajax.php",method:"post",data:{"pid":v,"m":"problem_get_title"},async:false}).responseText;
			let html=(v)+"<br><a href='../problem.php?id="+v+"' target='_blank'>"+title+"</a>\n";
			$(this).html(html);
			$(this).mouseover=null;
		});
		
	}
	$(document).ready(function(){
		showTitles();
	
	});

</script>
<?php
}else{

?>

	<section class="admin-tool-card">
	<h4>1) 문제·사용자로 조회</h4>
	<form action=solution_statistics.php method='post' class="admin-stats-form">
	  <label class="admin-field-label" for="stats-pids"><?php echo $MSG_PROBLEM_ID?> (쉼표로 구분)</label>
	  <input type="text" id="stats-pids" name=pids class="form-control" placeholder="1000,1001" size=128 >
	  <label class="admin-field-label" for="stats-ulist"><?php echo $MSG_USER_ID?> (한 줄에 한 명)</label>
              <textarea id="stats-ulist" name='ulist' rows='10' class="form-control" placeholder='user1<?php echo "\n"?>user2<?php echo "\n"?>user3<?php echo "\n"?>
              '> </textarea>
	  <div class="admin-form-actions">
	    <button type="submit" class="btn btn-primary"><?php echo $MSG_SEARCH?></button>
	  </div>
	</form>
	</section>
<?php
}
?>
	<section class="admin-tool-card">
	<h4><?php echo $cid>0 ? "다른 대회로 조회" : "2) 대회로 조회"?></h4>
	<form action=solution_statistics.php method='get' class="admin-tool-form">
	<select name="cid" class="form-control" aria-label="<?php echo $MSG_CONTEST?>">
<?php
	$sql="select * from contest order by contest_id desc limit 50 ";
	$result=pdo_query($sql);
	foreach($result as $row){
		echo "<option value='".$row['contest_id']."'";
		if($row['contest_id']==$cid) echo " selected ";
		echo ">".$row['title']."</option>";
	}
?>
	</select>
	  <button type="submit" class="btn btn-primary"><?php echo $MSG_SEARCH?></button>
	</form>
	</section>
</div>
