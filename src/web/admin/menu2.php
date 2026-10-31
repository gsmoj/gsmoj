<?php
  $ADMIN_SKIP_TEMPLATE_STYLES=true;
  require_once("admin-header.php");

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

<?php include(__DIR__.'/menu_groups.inc.php'); ?>

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
<script>
// 권한에 따라 링크가 하나도 없는 메뉴 그룹(예: 교사에게 공지·대회 관리)은 버튼까지 숨긴다.
document.querySelectorAll('.admin-menu-group').forEach(function (g) { if (!g.querySelector('li')) g.style.display = 'none'; });
</script>
</body>
</html>
