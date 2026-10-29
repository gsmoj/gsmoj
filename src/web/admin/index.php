<?php
        // 관리자 화면 진입점.
        // 사이트 공통 헤더(template/syzoj/header.php)를 그대로 띄운 뒤,
        // 그 아래에 접고 펼 수 있는 관리 메뉴 줄과 본문(iframe)을 둔다.
        require_once("../include/db_info.inc.php");
        if (!isset($_SESSION[$OJ_NAME.'_'.'user_id'])) {
                header("Location: ../loginpage.php");
                exit();
        }
        require_once("../include/my_func.inc.php");
        if(file_exists("../lang/$OJ_LANG.php")) require_once("../lang/$OJ_LANG.php");

        // 관리자/대회 생성자/문제 편집자/비밀번호 관리자만 접근 가능 (기존 admin-header.php와 동일한 체크).
        if (!(isset($_SESSION[$OJ_NAME.'_'.'administrator'])||isset($_SESSION[$OJ_NAME.'_'.'contest_creator'])||isset($_SESSION[$OJ_NAME.'_'.'problem_editor'])||isset($_SESSION[$OJ_NAME.'_'.'password_setter']))){
                header("Location: ../loginpage.php");
                exit();
        }

        // 헤더 아래 토글 메뉴가 쓰는 라벨. menu_groups.inc.php가 menu2.php와 같은 변수를 기대한다.
        $OJ_TP=$OJ_TEMPLATE;
        $MENU_ADVANCED_TOOLS      = isset($MSG_ADVANCED_TOOLS)      ? $MSG_ADVANCED_TOOLS      : "고급 도구";
        $MENU_EXTERNAL_LINKS      = isset($MSG_EXTERNAL_LINKS)      ? $MSG_EXTERNAL_LINKS      : "외부 링크";
        $MENU_SOLUTION_STATISTICS = isset($MSG_SOLUTION_STATISTICS) ? $MSG_SOLUTION_STATISTICS : "채점 통계 리포트";
        $MENU_PROBLEM_COPY        = isset($MSG_PROBLEM_COPY)        ? $MSG_PROBLEM_COPY        : "문제 복사";
        $MENU_PROBLEM_REORDER     = isset($MSG_PROBLEM_REORDER)     ? $MSG_PROBLEM_REORDER     : "문제 번호 재정렬";

        // 첫 화면은 메뉴 대신 서버 모니터링을 보여 준다. 관리자가 아니면 기존 도움말을 연다.
        $main_src = isset($_SESSION[$OJ_NAME.'_'.'administrator']) ? 'monitor.php' : 'help.php';
        // 헤더 아래 메뉴 줄의 바로가기(?main=problem_list.php&tab=queue)로 오른쪽 화면을 바로 연다. 관리자 폴더의 PHP 파일 이름만 허용한다.
        if (isset($_GET['main']) && preg_match('/^[a-z0-9_]+\.php$/', $_GET['main']) && $_GET['main'] !== 'index.php' && is_file(__DIR__.'/'.$_GET['main'])) {
                $main_src = $_GET['main'];
                if (isset($_GET['type']) && preg_match('/^[a-z]+$/', $_GET['type'])) $main_src .= '?type='.$_GET['type'];
                if (isset($_GET['tab']) && preg_match('/^[a-z]+$/', $_GET['tab'])) $main_src .= '#'.$_GET['tab'];
        }

        $show_title = $OJ_NAME.$MSG_ADMIN;
        require_once("../template/syzoj/header.php");
?>
<style>
    /* 관리자 화면 전용: #main.ui.container의 가운데 정렬/최대폭을 풀어서 본문이 화면 전체 폭을 쓰게 한다. */
    #main.ui.container {
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .admin-menubar {
        border-bottom: 1px solid #e5e7eb;
        background: #fff;
    }
    .admin-menubar-inner {
        display: flex;
        align-items: center;
        height: 44px;
    }
    #admin-menu-toggle {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        height: 32px;
        padding: 0 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: #fff;
        color: #374151;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }
    #admin-menu-toggle:hover { background: #f3f4f6; }
    #admin-menu-toggle .arrow { font-size: 10px; transition: transform .15s; }
    #admin-menu-toggle[aria-expanded="true"] .arrow { transform: rotate(180deg); }
    #admin-menu-panel {
        display: none;
        padding: 14px 0 18px;
        border-top: 1px solid #eef0f3;
        background: #f8fafc;
    }
    #admin-menu-panel.open { display: block; }
    #admin-menu-panel .admin-menu-groups {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
        gap: 18px 24px;
    }
    #admin-menu-panel .admin-menu-toggle {
        display: block;
        width: 100%;
        padding: 0 0 6px;
        border: 0;
        border-bottom: 1px solid #e2e5ea;
        background: none;
        color: #111827;
        font-size: 13px;
        font-weight: 700;
        text-align: left;
        cursor: default;
    }
    #admin-menu-panel .admin-menu-toggle .caret { display: none; }
    #admin-menu-panel .admin-menu-list {
        display: block;
        margin: 6px 0 0;
        padding: 0;
        list-style: none;
    }
    #admin-menu-panel .admin-menu-list a {
        display: block;
        padding: 4px 0;
        color: #4b5563;
        font-size: 13px;
        text-decoration: none;
    }
    #admin-menu-panel .admin-menu-list a b { font-weight: 500; }
    #admin-menu-panel .admin-menu-list a:hover { color: #2563eb; }
    #admin-menu-panel .admin-menu-list a.admin-menu-danger { color: #dc2626; }
    .admin-shell { height: calc(100vh - 150px); }
    .admin-shell .admin-main {
        display: block;
        width: 100%;
        height: 100%;
        border: 0;
    }
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

<div class="admin-shell" id="admin-shell">
    <iframe name="main" class="admin-main" src="<?php echo htmlentities($main_src, ENT_QUOTES, 'UTF-8'); ?>"></iframe>
</div>

<script>
(function () {
    var shell = document.getElementById('admin-shell');
    var toggle = document.getElementById('admin-menu-toggle');
    var panel = document.getElementById('admin-menu-panel');

    // 헤더, 관리 메뉴 줄, 펼친 메뉴 높이가 상황마다 달라서 실제 위치를 재서 남은 화면 높이를 채운다.
    function fitHeight() {
        var h = window.innerHeight - shell.getBoundingClientRect().top;
        shell.style.height = Math.max(h, 400) + 'px';
    }
    fitHeight();
    window.addEventListener('resize', fitHeight);

    function setOpen(open) {
        panel.classList.toggle('open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        fitHeight();
    }
    toggle.addEventListener('click', function () {
        setOpen(!panel.classList.contains('open'));
    });
    // 메뉴를 눌러 화면을 열면 메뉴는 다시 접어서 본문을 넓게 쓴다.
    panel.addEventListener('click', function (e) {
        var a = e.target.closest ? e.target.closest('a') : null;
        if (a && a.getAttribute('target') === 'main') setOpen(false);
    });
})();
</script>

<?php require_once("../template/syzoj/footer.php"); ?>
