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
  <?php
    $main_src = isset($_SESSION[$OJ_NAME.'_'.'administrator']) ? 'monitor.php' : 'help.php';
    // 헤더 아래 메뉴 줄의 바로가기(?main=problem_list.php&tab=queue)로 오른쪽 화면을 바로 연다. 관리자 폴더의 PHP 파일 이름만 허용한다.
    if (isset($_GET['main']) && preg_match('/^[a-z0-9_]+\.php$/', $_GET['main']) && $_GET['main'] !== 'index.php' && is_file(__DIR__.'/'.$_GET['main'])) {
        $main_src = $_GET['main'];
        if (isset($_GET['type']) && preg_match('/^[a-z]+$/', $_GET['type'])) $main_src .= '?type='.$_GET['type'];
        if (isset($_GET['tab']) && preg_match('/^[a-z]+$/', $_GET['tab'])) $main_src .= '#'.$_GET['tab'];
    }
  ?>
  <frame name="main" src="<?php echo htmlentities($main_src, ENT_QUOTES, 'UTF-8')?>">
  <noframes>
  </noframes>
</frameset>
</html>
