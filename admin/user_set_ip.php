<?php require("admin-header.php");

if (isset($OJ_LANG)) {
	require_once("../lang/$OJ_LANG.php");
}
?>

<title>대회 참여자 IP 변경</title>

<div class="admin-form-shell admin-compact-form">
<header class="admin-form-title"><h3>대회 참여자 IP 변경</h3><p>사용자의 최근 로그인 IP를 직접 기록합니다.</p></header>

<?php
require_once("../include/set_get_key.php");
$sql = "";
if(isset($_POST['do'])){
	require_once("../include/check_post_key.php");
	
	$user_id = $_POST['user_id'];
  $ip = $_POST['ip'];

  if(false){
		$user_id = stripslashes($user_id);
		$ip = stripslashes($ip);
	}

	$sql = "insert into loginlog (user_id,password,ip,time) value(?,?,?,now())";
	$result = pdo_query($sql,$user_id,"set ip by ".$_SESSION[$OJ_NAME."_user_id"],$ip);
	echo "<div class='alert alert-success'>사용자 ".htmlentities($_POST['user_id'], ENT_QUOTES, 'UTF-8')."의 로그인 IP를 변경했습니다.</div>";
}
?>

<form action=user_set_ip.php method=post class="form-horizontal">
	<div class="form-group">
		<label class="col-sm-offset-3 col-sm-3 control-label"><?php echo $MSG_USER_ID?></label>
		<?php if(isset($_GET['uid'])) { ?>
		<div class="col-sm-3"><input name="user_id" class="form-control" value="<?php echo htmlentities($_GET['uid'], ENT_QUOTES, 'UTF-8');?>" type="text" required ></div>
  	<?php } else if(isset($_POST['user_id'])) { ?>
		<div class="col-sm-3"><input name="user_id" class="form-control" value="<?php echo htmlentities($_POST['user_id'], ENT_QUOTES, 'UTF-8');?>" type="text" required ></div>
		<?php } else { ?>
		<div class="col-sm-3"><input name="user_id" class="form-control" placeholder="<?php echo $MSG_USER_ID."*"?>" type="text" required ></div>
		<?php } ?>
	</div>

	<div class="form-group">
		<label class="col-sm-offset-3 col-sm-3 control-label">새 IP 주소</label>
		<?php if(isset($_POST['ip'])) { ?>
		<div class="col-sm-3"><input name="ip" class="form-control" value="<?php echo htmlentities($_POST['ip'], ENT_QUOTES, 'UTF-8')?>" type="text"  autocomplete="off" required ></div>
		<?php } else { ?>
		<div class="col-sm-3"><input name="ip" class="form-control" placeholder="<?php echo "?.?.?.?*"?>" type="text"  autocomplete="off" required ></div>
		<?php } ?>
	</div>

	<div class="form-group">
		<?php require_once("../include/set_post_key.php");?>
		<div class="col-sm-offset-4 col-sm-2">
			<button name="do" type="hidden" value="do" class="btn btn-primary btn-block" ><?php echo $MSG_SAVE; ?></button>
		</div>
		<div class="col-sm-2">
			<button name="submit" type="reset" class="btn btn-default btn-block"><?php echo $MSG_RESET?></button>
		</div>
	</div>
</form>

</div>

<?php
require("../oj-footer.php");
?>
