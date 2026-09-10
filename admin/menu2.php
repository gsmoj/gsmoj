<?php require_once("admin-header.php");

  if(isset($OJ_LANG)){
    require_once("../lang/$OJ_LANG.php");
  }
  $path_fix="../";
  $OJ_TP=$OJ_TEMPLATE;
  $OJ_TEMPLATE="bs3";

  // 새로 추가한 메뉴 라벨은 ko.php에만 정의되어 있으므로, 다른 언어 파일을 쓰는 환경에서도
  // 빈 라벨이 나오지 않도록 admin-header.php와 같은 방식으로 폴백을 둔다.
  $MENU_ADVANCED_TOOLS      = isset($MSG_ADVANCED_TOOLS)      ? $MSG_ADVANCED_TOOLS      : "고급 도구";
  $MENU_EXTERNAL_LINKS      = isset($MSG_EXTERNAL_LINKS)      ? $MSG_EXTERNAL_LINKS      : "외부 링크";
  $MENU_SOLUTION_STATISTICS = isset($MSG_SOLUTION_STATISTICS) ? $MSG_SOLUTION_STATISTICS : "채점 통계 리포트";
  $MENU_PROBLEM_COPY        = isset($MSG_PROBLEM_COPY)        ? $MSG_PROBLEM_COPY        : "문제 복사";
  $MENU_PROBLEM_REORDER     = isset($MSG_PROBLEM_REORDER)     ? $MSG_PROBLEM_REORDER     : "문제 번호 재정렬";
?>
<html>
<head>
<title><?php echo $MSG_ADMIN?></title>
<link rel="stylesheet" href="<?php echo $OJ_CDN_URL.$path_fix."template/$OJ_TEMPLATE/"?>bootstrap-theme.min.css">
<!-- Bootstrap JavaScript가 jQuery에 의존하므로 jQuery를 먼저 불러온다. -->
<script src="<?php echo $OJ_CDN_URL.$path_fix."template/$OJ_TEMPLATE/"?>jquery.min.js"></script>

<!-- 메뉴 그룹 접기/펼치기(collapse)에 필요한 Bootstrap JavaScript. -->
<script src="<?php echo $OJ_CDN_URL.$path_fix."template/$OJ_TEMPLATE/"?>bootstrap.min.js"></script>

</head>

<!-- admin.css의 사이드바 스타일은 이 클래스를 기준으로 걸린다. -->
<body class="admin-menu-frame" style="text-align:center;min-width:100px" >
<hr>
<a class='btn btn-sm admin-brand' href="../index.php" target="_top" title="GSMOJ 메인으로 이동"><b>GSMOJ</b></a>
<hr>

<a class='btn btn-sm' href="../status.php" target="_top" title="<?php echo $MSG_HELP_SEEOJ?>"><b><?php echo $MSG_SEEOJ?></b></a><br>

<div>
<div class="admin-menu-groups" role="menu">

  <!-- NEWS -->
  <div class="admin-menu-group">
    <button type="button" class="btn btn-default btn-sm admin-menu-toggle collapsed" data-toggle="collapse" data-target="#admin-grp-news" aria-expanded="false" aria-controls="admin-grp-news">
      <?php echo $MSG_NEWS."-".$MSG_ADMIN ?> <span class="caret"></span>
    </button>
    <ul id="admin-grp-news" class="admin-menu-list collapse" role="menu">
<?php if (isset($_SESSION[$OJ_NAME.'_'.'administrator'])){?>
      <?php if ($OJ_TP=="bs3"){?>
        <li><a href="setmsg.php" target="main" title="<?php echo $MSG_HELP_SETMESSAGE?>"><b><?php echo $MSG_NEWS."-".$MSG_SETMESSAGE?></b></a></li>
      <?php }?>
        <li><a href="news_list.php" target="main" title="<?php echo $MSG_HELP_NEWS_LIST?>"><b><?php echo $MSG_NEWS."-".$MSG_LIST?></b></a></li>
        <li><a href="news_add_page.php" target="main" title="<?php echo $MSG_HELP_ADD_NEWS?>"><b><?php echo $MSG_NEWS."-".$MSG_ADD?></b></a></li>
<?php }?>
    </ul>
  </div>

  <!-- USER -->
  <div class="admin-menu-group">
    <button type="button" class="btn btn-default btn-sm admin-menu-toggle collapsed" data-toggle="collapse" data-target="#admin-grp-user" aria-expanded="false" aria-controls="admin-grp-user">
      <?php echo $MSG_USER."-".$MSG_ADMIN ?> <span class="caret"></span>
    </button>
    <ul id="admin-grp-user" class="admin-menu-list collapse" role="menu">
<?php if (isset($_SESSION[$OJ_NAME.'_'.'administrator'])||isset( $_SESSION[$OJ_NAME.'_'.'password_setter'])){?>
        <li><a href="user_list.php" target="main" title="<?php echo $MSG_HELP_USER_LIST?>"><b><?php echo $MSG_USER."-".$MSG_LIST?></b></a></li>
<?php }?>
<?php if (isset($_SESSION[$OJ_NAME.'_'.'administrator'])||isset($_SESSION[$OJ_NAME.'_'.'user_adder'])){?>
        <li><a href="user_add.php" target="main" title="<?php echo $MSG_HELP_USER_ADD?>"><b><?php echo $MSG_USER."-".$MSG_ADD?></b></a></li>
        <li><a href="user_import.php" target="main" title="<?php echo $MSG_HELP_USER_IMPORT ?>"><b><?php echo $MSG_USER."-".$MSG_IMPORT?></b></a></li>
<?php }?>
<?php if (isset($_SESSION[$OJ_NAME.'_'.'administrator'])||isset( $_SESSION[$OJ_NAME.'_'.'password_setter'])){?>
        <li><a href="changepass.php" target="main" title="<?php echo $MSG_HELP_SETPASSWORD?>"><b><?php echo $MSG_USER."-".$MSG_SETPASSWORD?></b></a></li>
<?php }?>
<?php if (isset($_SESSION[$OJ_NAME.'_'.'administrator'])){?>
        <li><a href="privilege_list.php" target="main" title="<?php echo $MSG_HELP_PRIVILEGE_LIST?>"><b><?php echo $MSG_USER."-".$MSG_PRIVILEGE."-".$MSG_LIST?></b></a></li>
        <li><a href="privilege_add.php" target="main" title="<?php echo $MSG_HELP_ADD_PRIVILEGE?>"><b><?php echo $MSG_USER."-".$MSG_PRIVILEGE."-".$MSG_ADD?></b></a></li>
<?php }?>
    </ul>
  </div>

  <!-- PROBLEM -->
  <div class="admin-menu-group">
    <button type="button" class="btn btn-default btn-sm admin-menu-toggle collapsed" data-toggle="collapse" data-target="#admin-grp-problem" aria-expanded="false" aria-controls="admin-grp-problem">
      <?php echo $MSG_PROBLEM."-".$MSG_ADMIN ?> <span class="caret"></span>
    </button>
    <ul id="admin-grp-problem" class="admin-menu-list collapse" role="menu">
<?php if (isset($_SESSION[$OJ_NAME.'_'.'administrator'])||isset($_SESSION[$OJ_NAME.'_'.'problem_editor'])||isset($_SESSION[$OJ_NAME.'_'.'contest_creator'])) {?>
        <li><a href="problem_list.php" target="main" title="<?php echo $MSG_HELP_PROBLEM_LIST?>"><b><?php echo $MSG_PROBLEM."-".$MSG_LIST?></b></a></li>
<?php }
      if (isset($_SESSION[$OJ_NAME.'_'.'administrator'])||isset($_SESSION[$OJ_NAME.'_'.'problem_editor'])) {?>
        <li><a href="problem_add_page.php" target="main" title="<?php echo html_entity_decode($MSG_HELP_ADD_PROBLEM)?>"><b><?php echo $MSG_PROBLEM."-".$MSG_ADD?></b></a></li>
<?php }
      if (isset($_SESSION[$OJ_NAME.'_'.'administrator'])||isset($_SESSION[$OJ_NAME.'_'.'problem_importer'])) {?>
        <li><a href="problem_import.php" target="main" title="<?php echo $MSG_HELP_IMPORT_PROBLEM?>"><b><?php echo $MSG_PROBLEM."-".$MSG_IMPORT?></b></a></li>
        <li><a href="problem_export.php" target="main" title="<?php echo $MSG_HELP_EXPORT_PROBLEM?>"><b><?php echo $MSG_PROBLEM."-".$MSG_EXPORT?></b></a></li>
<?php }?>
    </ul>
  </div>

  <!-- CONTEST -->
  <div class="admin-menu-group">
    <button type="button" class="btn btn-default btn-sm admin-menu-toggle collapsed" data-toggle="collapse" data-target="#admin-grp-contest" aria-expanded="false" aria-controls="admin-grp-contest">
      <?php echo $MSG_CONTEST."-".$MSG_ADMIN ?> <span class="caret"></span>
    </button>
    <ul id="admin-grp-contest" class="admin-menu-list collapse" role="menu">
<?php if (isset($_SESSION[$OJ_NAME.'_'.'administrator'])||isset($_SESSION[$OJ_NAME.'_'.'contest_creator'])){?>
        <li><a href="contest_list.php" target="main"  title="<?php echo $MSG_HELP_CONTEST_LIST?>"><b><?php echo $MSG_CONTEST."-".$MSG_LIST?></b></a></li>
        <li><a href="contest_add.php" target="main"  title="<?php echo $MSG_HELP_ADD_CONTEST?>"><b><?php echo $MSG_CONTEST."-".$MSG_ADD?></b></a></li>
        <li><a href="user_set_ip.php" target="main" title="<?php echo $MSG_HELP_SET_LOGIN_IP?>"><b><?php echo $MSG_CONTEST."-".$MSG_SET_LOGIN_IP?></b></a></li>
        <li><a href="team_generate.php" target="main" title="<?php echo $MSG_HELP_TEAMGENERATOR?>"><b><?php echo $MSG_CONTEST."-".$MSG_TEAMGENERATOR?></b></a></li>
        <li><a href="team_generate2.php" target="main" title="<?php echo $MSG_HELP_TEAMGENERATOR?>"><b><?php echo $MSG_CONTEST."-".$MSG_TEAMGENERATOR?></b></a></li>
        <li><a href="offline_import.php" target="main" title="<?php echo $MSG_IMPORT.$MSG_CONTEST ?>"><b><?php echo $MSG_CONTEST."-".$MSG_IMPORT ?></b></a></li>
<?php }?>
    </ul>
  </div>

<?php if (isset($_SESSION[$OJ_NAME.'_'.'administrator'])){?>
  <!-- SYSTEM -->
  <div class="admin-menu-group">
    <button type="button" class="btn btn-default btn-sm admin-menu-toggle collapsed" data-toggle="collapse" data-target="#admin-grp-system" aria-expanded="false" aria-controls="admin-grp-system">
      <?php echo $MSG_SYSTEM."-".$MSG_ADMIN ?> <span class="caret"></span>
    </button>
    <ul id="admin-grp-system" class="admin-menu-list collapse" role="menu">
      <?php if( $domain == $DOMAIN ){ ?>
        <li><a href="settings.php" target="main" title="<?php echo $MSG_HELP_SETTINGS?>"><b><?php echo $MSG_SYSTEM."-".$MSG_SETTINGS?></b></a></li>
      <?php } ?>
        <li><a href="rejudge.php" target="main" title="<?php echo $MSG_HELP_REJUDGE?>"><b><?php echo $MSG_SYSTEM."-".$MSG_REJUDGE?></b></a></li>
        <li><a href="source_give.php" target="main" title="<?php echo $MSG_HELP_GIVESOURCE?>"><b><?php echo $MSG_SYSTEM."-".$MSG_GIVESOURCE?></b></a></li>
        <li><a href="../online.php" target="main"><b><?php echo $MSG_SYSTEM."-".$MSG_HELP_ONLINE?></b></a></li>
        <li><a href="update_db.php" target="main" title="<?php echo $MSG_HELP_UPDATE_DATABASE?>"><b><?php echo $MSG_SYSTEM."-".$MSG_UPDATE_DATABASE?></b></a></li>
        <li><a href="backup.php" target="main" title="<?php echo $MSG_HELP_BACKUP_DATABASE?>"><b><?php echo $MSG_SYSTEM."-".$MSG_BACKUP_DATABASE?></b></a></li>
        <li><a href="ranklist_export.php" target="main" title="<?php echo $MSG_EXPORT.$MSG_RANKLIST ?>"><b><?php echo  $MSG_EXPORT.$MSG_RANKLIST ?></b></a></li>
    </ul>
  </div>

  <!-- ADVANCED TOOLS : 자주 쓰지 않고 영향 범위가 큰 도구를 따로 모은 그룹 -->
  <div class="admin-menu-group">
    <button type="button" class="btn btn-default btn-sm admin-menu-toggle collapsed" data-toggle="collapse" data-target="#admin-grp-tools" aria-expanded="false" aria-controls="admin-grp-tools">
      <?php echo $MENU_ADVANCED_TOOLS ?> <span class="caret"></span>
    </button>
    <ul id="admin-grp-tools" class="admin-menu-list collapse" role="menu">
        <li><a href="solution_statistics.php" target="main" title="Create your own data"><b><?php echo $MENU_SOLUTION_STATISTICS ?> (SS Report)</b></a></li>
        <li><a href="problem_copy.php" target="main" title="Create your own data"><b><?php echo $MENU_PROBLEM_COPY ?> (CopyProblem)</b></a></li>
        <!-- 문제 번호를 실제로 바꾸는 기능이라 되돌리기 어렵다. 색으로 구분해 둔다. -->
        <li><a class="admin-menu-danger" href="problem_changeid.php" target="main" title="Danger,Use it on your own risk"><b><?php echo $MENU_PROBLEM_REORDER ?> (ReOrderProblem)</b></a></li>
    </ul>
  </div>

  <!-- EXTERNAL LINKS : 외부 사이트와 참고 문서 -->
  <div class="admin-menu-group">
    <button type="button" class="btn btn-default btn-sm admin-menu-toggle collapsed" data-toggle="collapse" data-target="#admin-grp-links" aria-expanded="false" aria-controls="admin-grp-links">
      <?php echo $MENU_EXTERNAL_LINKS ?> <span class="caret"></span>
    </button>
    <ul id="admin-grp-links" class="admin-menu-list collapse" role="menu">
        <li><a href="https://github.com/zhblue/hustoj/" target="_blank"><b>HUSTOJ</b></a></li>
        <li><a href="https://yuanqi.tencent.com/agent/jADpOEWqLvTv" target="_blank"><b>샤오장 선생님(AI-help)</b></a></li>
        <li><a href="http://hustoj.com" target="main"><b><?php echo $MSG_ADMIN." ".$MSG_FAQ?></b></a></li>
        <li><a href="https://github.com/zhblue/freeproblemset/" target="_blank"><b>FreeProblemSet</b></a></li>
        <li><a href="http://tk.hustoj.com" target="_blank"><b>셀프 문제은행</b></a></li>
<?php if(isset($OJ_REMOTE_JUDGE)&&$OJ_REMOTE_JUDGE){ ?>
        <li><a href="https://www.ssoier.cn/api/" target="_blank"><b>원격 계정 관리</b></a></li>
<?php } ?>
        <li><a href="https://mp.weixin.qq.com/s?__biz=MzI1MTAwMTI2NA==&mid=2656403287&idx=1&sn=2b1b9a5cd0b271aa4a050c349981e715" target="_blank"><b>2차 개발 문서</b></a></li>
    </ul>
  </div>
<?php }?>

</div>
</div>

<script>
// 프레임셋 구조라 메뉴 프레임은 main 프레임이 어떤 페이지를 보고 있는지 알 수 없다.
// 클릭 시점에 표시를 옮겨 두면 메뉴 프레임이 다시 로드되지 않는 동안 그대로 유지된다.
$(function () {
  $(".admin-menu-list").on("click", "a[target='main']", function () {
    $(".admin-menu-list a").removeClass("active");
    $(this).addClass("active");
  });
});
</script>
</body>
</html>
