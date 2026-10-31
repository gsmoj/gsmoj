<?php
        // 관리자 화면 진입점. 왼쪽 메뉴 프레임 대신 사이트 공통 헤더를 그대로 쓰고,
        // 관리 기능은 헤더 아래 관리자 메뉴 줄의 그룹 상자(마우스를 올리면 하위 기능 표시)에서 연다.
        // 본문은 iframe(name="main")이라 기존 관리 화면과 메뉴 링크(target="main")를 그대로 쓴다.
        require_once("../include/db_info.inc.php");
        if (!isset($_SESSION[$OJ_NAME.'_'.'user_id'])) {
                header("Location: ../loginpage.php");
                exit();
        }
        // admin-header.php와 같은 권한만 들어올 수 있다. 관리 권한이 없으면 첫 화면으로 보낸다.
        if (!(isset($_SESSION[$OJ_NAME.'_'.'administrator'])||isset($_SESSION[$OJ_NAME.'_'.'contest_creator'])||isset($_SESSION[$OJ_NAME.'_'.'problem_editor'])||isset($_SESSION[$OJ_NAME.'_'.'password_setter'])||isset($_SESSION[$OJ_NAME.'_'.'teacher']))) {
                header("Location: ../index.php");
                exit();
        }
        require_once("../include/my_func.inc.php");
        if(file_exists("../lang/$OJ_LANG.php")) require_once("../lang/$OJ_LANG.php");

        // 첫 화면은 서버 모니터링을 보여 준다. 관리자가 아니면 기존 도움말을 연다.
        $main_src = isset($_SESSION[$OJ_NAME.'_'.'administrator']) ? 'monitor.php' : 'help.php';
        // 헤더 메뉴의 바로가기(?main=problem_list.php&tab=queue)로 본문을 바로 연다. 관리자 폴더의 PHP 파일 이름만 허용한다.
        if (isset($_GET['main']) && preg_match('/^[a-z0-9_]+\.php$/', $_GET['main']) && $_GET['main'] !== 'index.php' && is_file(__DIR__.'/'.$_GET['main'])) {
                $main_src = $_GET['main'];
                if (isset($_GET['type']) && preg_match('/^[a-z]+$/', $_GET['type'])) $main_src .= '?type='.$_GET['type'];
                if (isset($_GET['tab']) && preg_match('/^[a-z]+$/', $_GET['tab'])) $main_src .= '#'.$_GET['tab'];
        }

        $show_title = $OJ_NAME.$MSG_ADMIN;
        require_once("../template/$OJ_TEMPLATE/header.php");
?>
<style>
    /* 본문(iframe)이 헤더 아래 화면 전체를 쓰도록 가운데 정렬·최대폭·바닥글을 없앤다. */
    #main.ui.container { width: 100% !important; max-width: none !important; margin: 0 !important; padding: 0 !important; }
    .footer.gs-footer { display: none !important; }
    body#MainBg-C { overflow: hidden !important; }
    .admin-main { display: block; width: 100%; height: 600px; border: 0; }
</style>
<iframe name="main" id="admin-main" class="admin-main" title="관리자 화면" src="<?php echo htmlentities($main_src, ENT_QUOTES, 'UTF-8'); ?>"></iframe>
<script>
(function () {
    var frame = document.getElementById('admin-main');
    // 헤더·관리자 메뉴 줄 높이가 권한마다 달라서, 실제 위치를 재서 남은 화면 높이를 본문에 준다.
    function fitHeight() { frame.style.height = Math.max(window.innerHeight - frame.getBoundingClientRect().top, 400) + 'px'; }
    fitHeight();
    window.addEventListener('resize', fitHeight);
})();
</script>
<?php require_once("../template/$OJ_TEMPLATE/footer.php"); ?>
