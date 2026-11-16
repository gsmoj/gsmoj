<?php require_once("admin-header.php");?>
<?php require_once("../include/check_get_key.php");
if (!(isset($_SESSION[$OJ_NAME.'_'.'administrator']))){
	exit(1);
}
if(isset($_GET['uid'])){
	$user_id=$_GET['uid'];
	$rightstr =$_GET['rightstr'];
	if($_SESSION[$OJ_NAME."_user_id"]==$user_id && $rightstr=="administrator" ){
	        echo "자기 자신의 관리자 권한은 삭제할 수 없습니다!";
	        exit(0);
	}
	$sql="delete from `privilege` where user_id=? and rightstr=?";
	$rows=pdo_query($sql,$user_id,$rightstr);
	echo htmlspecialchars($user_id, ENT_QUOTES, 'UTF-8')." ".htmlspecialchars($rightstr, ENT_QUOTES, 'UTF-8')." 권한을 삭제했습니다.";
}
?>

<script language=javascript>
	window.setTimeout(function(){ history.go(-1); }, 1000);
</script>
