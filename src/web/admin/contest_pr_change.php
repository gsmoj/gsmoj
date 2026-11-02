<?php require_once("admin-header.php");
require_once("../include/check_get_key.php");
$cid=intval($_GET['cid']);
	if(!(isset($_SESSION[$OJ_NAME.'_'."m$cid"]) || isset($_SESSION[$OJ_NAME.'_'.'administrator']) || isset($_SESSION[$OJ_NAME.'_'.'contest_creator']))) exit();
$sql="select `private`,`contest_type` FROM `contest` WHERE `contest_id`=?";
$result=pdo_query($sql,$cid);
$num=count($result);
if ($num<1){
	echo "No Such Problem!";
	
	exit(0);
}
$row=$result[0];
// 수행평가 회차는 반 학생만 참가하도록 비공개로 운영하므로 공개 범위는 관리자만 바꿀 수 있다.
if ((intval($row['contest_type']) & 512) && !isset($_SESSION[$OJ_NAME.'_'.'administrator'])) {
	echo "<div class='admin-content-page'><div class='alert alert-danger'>수행평가 회차의 공개 범위는 관리자만 바꿀 수 있습니다.</div></div>";
	exit(1);
}
if (intval($row[0])==0) $sql="UPDATE `contest` SET `private`='1' WHERE `contest_id`=?";
else $sql="UPDATE `contest` SET `private`='0' WHERE `contest_id`=?";

pdo_query($sql,$cid);
?>
<script language=javascript>
	history.go(-1);
</script>

