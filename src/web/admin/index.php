<?php
        require_once("../include/db_info.inc.php");
        if (!isset($_SESSION[$OJ_NAME.'_'.'user_id'])) {
                header("Location: ../loginpage.php");
                exit();
        }
?><html>
<head>
<title><?php echo $OJ_NAME.$MSG_ADMIN?></title>
</head>
<frameset cols="260,*" frameborder="0" border="0">
  <?php if($OJ_TEMPLATE=="sidebar"){ ?>
          <frame name="menu" src="menu.php">                            
  <?php }else{?>
          <frame name="menu" src="menu2.php">
  <?php } ?>
  <?php // 왼쪽에 메뉴가 고정되어 있으므로 오른쪽 첫 화면은 메뉴 대신 서버 모니터링을 보여 준다. 관리자가 아니면 기존 도움말을 연다. ?>
  <frame name="main" src="<?php echo isset($_SESSION[$OJ_NAME.'_'.'administrator']) ? 'monitor.php' : 'help.php'?>">
  <noframes>
  </noframes>
</frameset>
</html>
