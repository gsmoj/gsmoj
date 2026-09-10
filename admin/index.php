<?php
        require_once("../include/db_info.inc.php");
?><html>
<head>
<title><?php echo $OJ_NAME.$MSG_ADMIN?></title>
<link rel="stylesheet" href="admin.css" type="text/css">
</head>
<body class="admin-dashboard">
<div class="admin-dashboard-layout">
  <aside class="admin-dashboard-sidebar">
  <?php if($OJ_TEMPLATE=="sidebar"){ ?>
          <iframe name="menu" src="menu.php" title="관리자 메뉴"></iframe>
  <?php }else{?>
          <iframe name="menu" src="menu2.php" title="관리자 메뉴"></iframe>
  <?php } ?>
  </aside>
  <main class="admin-dashboard-main">
    <iframe name="main" src="help.php" title="관리자 본문"></iframe>
  </main>
</div>
</body>
</html>
