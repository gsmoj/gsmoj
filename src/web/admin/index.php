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
  <frame name="main" src="help.php">
  <noframes>
  </noframes>
</frameset>
</html>
