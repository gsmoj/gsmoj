<?php
        // 관리자 화면 진입점. 사이트 공통 헤더(역할 메뉴 줄 포함)를 그대로 쓰고, 왼쪽 메뉴 프레임 대신
        // 헤더 아래 "관리 메뉴" 토글을 눌러 관리 메뉴 전체를 펼친다.
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

        // 헤더 토글 메뉴가 포함하는 menu_groups.inc.php가 menu2.php와 같은 라벨 변수를 기대한다.
        $OJ_TP=$OJ_TEMPLATE;
        $MENU_ADVANCED_TOOLS      = isset($MSG_ADVANCED_TOOLS)      ? $MSG_ADVANCED_TOOLS      : "고급 도구";
        $MENU_EXTERNAL_LINKS      = isset($MSG_EXTERNAL_LINKS)      ? $MSG_EXTERNAL_LINKS      : "외부 링크";
        $MENU_SOLUTION_STATISTICS = isset($MSG_SOLUTION_STATISTICS) ? $MSG_SOLUTION_STATISTICS : "채점 통계 리포트";
        $MENU_PROBLEM_COPY        = isset($MSG_PROBLEM_COPY)        ? $MSG_PROBLEM_COPY        : "문제 복사";
        $MENU_PROBLEM_REORDER     = isset($MSG_PROBLEM_REORDER)     ? $MSG_PROBLEM_REORDER     : "문제 번호 재정렬";

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
    .admin-menubar { position: relative; z-index: 2; border-bottom: 1px solid #e5e7eb; background: #fff; }
    .admin-menubar .ui.container.admin-menubar-inner { display: flex; align-items: center; height: 44px; }
    #admin-menu-toggle { display: inline-flex; align-items: center; gap: 8px; height: 32px; padding: 0 12px; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; color: #374151; font-size: 13px; font-weight: 600; cursor: pointer; }
    #admin-menu-toggle:hover { background: #f3f4f6; }
    #admin-menu-toggle .arrow { font-size: 10px; transition: transform .15s; }
    #admin-menu-toggle[aria-expanded="true"] .arrow { transform: rotate(180deg); }
    /* 펼친 메뉴는 본문 위에 겹쳐 보여 주므로 본문 높이가 바뀌지 않는다. */
    #admin-menu-panel { display: none; position: absolute; top: 100%; left: 0; right: 0; max-height: calc(100vh - 200px); overflow-y: auto; padding: 14px 0 18px; border-bottom: 1px solid #e5e7eb; background: #f8fafc; box-shadow: 0 10px 24px rgba(15, 23, 42, .12); }
    #admin-menu-panel.open { display: block; }
    #admin-menu-panel > .ui.container > .btn { margin-bottom: 10px; }
    #admin-menu-panel .admin-menu-groups { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 18px 24px; }
    #admin-menu-panel .admin-menu-toggle { display: block; width: 100%; padding: 0 0 6px; border: 0; border-bottom: 1px solid #e2e5ea; background: none; color: #111827; font-size: 13px; font-weight: 700; text-align: left; cursor: default; }
    #admin-menu-panel .admin-menu-toggle .caret { display: none; }
    #admin-menu-panel .admin-menu-list { display: block; margin: 6px 0 0; padding: 0; list-style: none; }
    #admin-menu-panel a { color: #4b5563; font-size: 13px; text-decoration: none; }
    #admin-menu-panel .admin-menu-list a { display: block; padding: 4px 0; }
    #admin-menu-panel .admin-menu-list a b { font-weight: 500; }
    #admin-menu-panel a:hover { color: #2563eb; }
    #admin-menu-panel .admin-menu-list a.admin-menu-danger { color: #dc2626; }
    .admin-main { display: block; width: 100%; height: 600px; border: 0; }
</style>

<div class="admin-menubar">
    <div class="ui container admin-menubar-inner">
        <button type="button" id="admin-menu-toggle" aria-expanded="false" aria-controls="admin-menu-panel">
            <i class="bars icon" style="margin:0;"></i> 관리 메뉴 <span class="arrow">▼</span>
        </button>
    </div>
    <div id="admin-menu-panel">
        <div class="ui container">
            <?php include(__DIR__.'/menu_groups.inc.php'); ?>
        </div>
    </div>
</div>
<iframe name="main" id="admin-main" class="admin-main" title="관리자 화면" src="<?php echo htmlentities($main_src, ENT_QUOTES, 'UTF-8'); ?>"></iframe>
<script>
(function () {
    var frame = document.getElementById('admin-main');
    var toggle = document.getElementById('admin-menu-toggle');
    var panel = document.getElementById('admin-menu-panel');
    // 헤더·역할 메뉴 줄 높이가 권한마다 달라서, 실제 위치를 재서 남은 화면 높이를 본문에 준다.
    function fitHeight() { frame.style.height = Math.max(window.innerHeight - frame.getBoundingClientRect().top, 400) + 'px'; }
    fitHeight();
    window.addEventListener('resize', fitHeight);
    function setOpen(open) {
        panel.classList.toggle('open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    toggle.addEventListener('click', function () { setOpen(!panel.classList.contains('open')); });
    // 메뉴를 눌러 본문을 열거나 Esc를 누르면 메뉴를 접는다. 본문(iframe)을 누르면 창이 포커스를 잃으므로 그때도 접는다.
    panel.addEventListener('click', function (e) {
        var a = e.target.closest ? e.target.closest('a') : null;
        if (a && a.getAttribute('target') === 'main') setOpen(false);
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
    window.addEventListener('blur', function () { setOpen(false); });
})();
</script>
<?php require_once("../template/$OJ_TEMPLATE/footer.php"); ?>
