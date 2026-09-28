<?php require("admin-header.php");

if (!(isset($_SESSION[$OJ_NAME.'_'.'administrator']))){
	exit(1);
}
function writable($path){
	$ret=false;
	$fp=fopen($path."/testifwritable.tst","w");
	$ret=!($fp===false);
	fclose($fp);
	unlink($path."/testifwritable.tst");
	return $ret;
}
?>
<?php if(isset($_POST['do'])){
	require_once("../include/check_post_key.php");
	if (isset($_POST['from'])){
		$from=intval($_POST['from']);
		$to=intval($_POST['to']);
		$row=0;
		if($result=pdo_query("select 1 from problem where problem_id=?",$to)){
			$row=count($result);
			
		}
		
		if($row==0&&rename("$OJ_DATA/$from","$OJ_DATA/$to")){
			$sql="UPDATE `problem` SET `problem_id`=? WHERE `problem_id`=?";
			if(pdo_query($sql,$to,$from)==0){
				 rename("$OJ_DATA/$to","$OJ_DATA/$from");
				 echo "<div class='alert alert-danger admin-flash' role='alert'>변경 실패...</div>";
				 exit(1);
			}
			$sql="UPDATE `solution` SET `problem_id`=? WHERE `problem_id`=?";
			pdo_query($sql,$to,$from);
			$sql="UPDATE `contest_problem` SET `problem_id`=? WHERE `problem_id`=?";
			pdo_query($sql,$to,$from);
			$sql="UPDATE `topic` SET `pid`=? WHERE `pid`=?";
			pdo_query($sql,$to,$from);
			
			$sql="select max(problem_id) from problem";
			if($result=pdo_query($sql)){
				$f=$result[0];
				$nextid=$f[0]+1;
				$sql="ALTER TABLE problem AUTO_INCREMENT = $nextid";
				pdo_query($sql);
			}
			
			echo "<div class='alert alert-success admin-flash' role='status'>변경 완료!</div>";
		}else{

				echo "<div class='alert alert-danger admin-flash' role='alert'>변경 실패...</div>";
		}

	}
}

 $show_form=true;
	   if(!writable($OJ_DATA)){
		   $show_form=false;
	   }
?>
<title>문제 번호 변경</title>
<style>
  .changeid-card { max-width: 620px; padding: 18px; border: 1px solid var(--admin-border); border-radius: 12px; background: #fff; }
  .changeid-card .alert { margin-bottom: 16px; }
  .admin-form-shell .changeid-form { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
  .changeid-form input[type=text] { width: 120px; text-align: center; }
  .changeid-warning { margin: 0 0 14px; color: #b91c1c; font-weight: 600; }
  .admin-flash { max-width: 620px; margin: 16px 24px; }
</style>
<script>document.body.classList.add('admin-form-page');</script>
<div class="admin-form-shell">
<header class="admin-form-title">
  <h3>문제 번호 변경</h3>
  <p>문제 번호를 바꾸면 채점 기록, 대회 문제, 토론 글의 문제 번호도 함께 바뀝니다.</p>
</header>
<div class="changeid-card">
<?php if(!$show_form){ ?>
  <div class="alert alert-danger">
    <?php echo "$OJ_DATA"?> 를 php.ini의 open_basedir 설정에 추가하거나,<br>
    다음 명령을 실행해야 합니다:<br>
    <b>chmod 775 -R <?php echo "$OJ_DATA"?> &amp;&amp; chgrp -R www-data <?php echo "$OJ_DATA"?></b><br>
    현재는 이 기능을 사용할 수 없습니다.
  </div>
<?php }else{ ?>
  <p class="changeid-warning">주의: 문제 번호를 바꾸면 되돌리기 어렵습니다. 신중히 진행하세요.</p>
  <form action='problem_changeid.php' method=post class="changeid-form">
    <label class="admin-field-label" for="changeid-from" style="margin:0">현재 번호</label>
    <input class="form-control" id="changeid-from" type=text name='from' placeholder="1001">
    <span aria-hidden="true">&rarr;</span>
    <label class="admin-field-label" for="changeid-to" style="margin:0">새 번호</label>
    <input class="form-control" id="changeid-to" type=text name='to' placeholder="2001">
    <input type='hidden' name='do' value='do'>
    <input class="btn btn-danger" type=submit value='번호 변경'>
    <?php require_once("../include/set_post_key.php");?>
  </form>
<?php }
?>
</div>
</div>
