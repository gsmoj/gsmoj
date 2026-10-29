<?php require_once("admin-header.php");
if (!(isset($_SESSION[$OJ_NAME.'_'.'administrator']))){
	exit(1);
}?>
<?php if(isset($_POST['do'])){
	require_once("../include/check_post_key.php");
	$from=$_POST['from'];
	$to=$_POST['to'];
	$start=intval($_POST['start']);
	$end=intval($_POST['end']);
	$sql="update `solution` set `user_id`=? where `user_id`=? and problem_id>=? and problem_id<=? and result=4";
	//echo $sql;
	echo "<div class='alert alert-success' role='status'>".pdo_query($sql,$to,$from,$start,$end)."개 정답 코드의 소유자를 변경했습니다.</div>";
	
}
?>
<title><?php echo $MSG_GIVESOURCE?></title>
<script>document.body.classList.add('admin-form-page');</script>
<div class="admin-form-shell">
<header class="admin-form-title">
	<h3><?php echo htmlentities($MSG_SYSTEM." - ".$MSG_GIVESOURCE, ENT_QUOTES, 'UTF-8')?></h3>
	<p><?php echo $MSG_HELP_GIVESOURCE?>. 지정한 문제 범위의 정답 코드만 옮겨집니다.</p>
</header>
<form action='source_give.php' method=post class="admin-grid-form admin-card">
	<label class="admin-field-label" for="give-from">보내는 사용자ID</label>
	<input class="form-control" id="give-from" type=text size=10 name="from" value="zhblue">
	<label class="admin-field-label" for="give-to">받는 사용자ID</label>
	<input class="form-control" id="give-to" type=text size=10 name="to" value="standard">
	<label class="admin-field-label" for="give-start">시작 <?php echo $MSG_PROBLEM_ID?></label>
	<input class="form-control" id="give-start" type=text size=10 name="start">
	<label class="admin-field-label" for="give-end">끝 <?php echo $MSG_PROBLEM_ID?></label>
	<input class="form-control" id="give-end" type=text size=10 name="end">
	<input type='hidden' name='do' value='do'>
	
	<?php require_once("../include/set_post_key.php");?>
	<div class="admin-form-actions">
		<input type=submit class="btn btn-primary" value='<?php echo $MSG_GIVESOURCE?>'>
	</div>
</form>
</div>
