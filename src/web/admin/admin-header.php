<?php 
// 모든 관리자 페이지가 공통으로 포함하는 헤더다.
// 권한 확인, 공통 CSS/JS, CSRF 토큰 삽입, 세션 유지 iframe을 담당한다.
ini_set("memory_limit", "1024M");  // 대용량 문제/사용자 가져오기를 위해 필요하면 더 키운다.
ini_set("max_execution_time", "600");
require_once("../include/db_info.inc.php");
require_once ("../include/my_func.inc.php");
if(file_exists("../lang/$OJ_LANG.php")) require_once("../lang/$OJ_LANG.php");
if(isset($OJ_LOG_ENABLED) && $OJ_LOG_ENABLED){
	// 관리자 요청 파라미터를 로그에 남겨 추후 작업 이력을 추적한다. (비밀번호 등 민감정보는 마스킹)
	$params = json_encode(maskSensitiveParams($_REQUEST), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	$logger->info($params);
}
?>
<!DOCTYPE html>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel=stylesheet href='../bootstrap/css/bootstrap.min.css' type="text/css">
<?php // 파일이 바뀌면 주소가 달라지도록 수정 시각을 붙여, 브라우저가 예전 스타일을 계속 쓰지 않게 한다. ?>
<link rel="stylesheet" href="admin.css?v=<?php echo @filemtime(__DIR__."/admin.css")?>" type="text/css">
<script src="../template/bs3/jquery.min.js"></script>
<script>
$("document").ready(function (){
  // 관리자 폼에는 csrf.php가 생성한 토큰 필드를 자동으로 붙인다.
  $("form").append("<div id='csrf' />");
  $("#csrf").load("../csrf.php");
});

</script>
<?php
// 관리자, 대회 생성자, 문제 편집자, 비밀번호 관리자, 교사만 관리자 페이지에 접근할 수 있다.
if (!(isset($_SESSION[$OJ_NAME.'_'.'administrator'])||isset($_SESSION[$OJ_NAME.'_'.'contest_creator'])||isset($_SESSION[$OJ_NAME.'_'.'problem_editor'])||isset($_SESSION[$OJ_NAME.'_'.'password_setter'])||isset($_SESSION[$OJ_NAME.'_'.'teacher']))){
	echo "<a href='../loginpage.php'>".(isset($MSG_Login)?$MSG_Login:"Please Login First!")."</a>";
	exit(1);
}
if ((!isset($ADMIN_SKIP_TEMPLATE_STYLES) || !$ADMIN_SKIP_TEMPLATE_STYLES) && file_exists("../template/$OJ_TEMPLATE/css.php")) require_once("../template/$OJ_TEMPLATE/css.php");
?>
<!-- 세션 만료를 늦추기 위해 숨겨진 iframe에서 session.php를 주기적으로 유지한다. -->
<iframe src="../session.php" title="" aria-hidden="true" hidden></iframe>

