<?php
        require_once(dirname(__FILE__)."/../../include/memcache.php");
        
        /**
         * 로그인한 사용자의 읽지 않은 쪽지 개수를 메뉴에 표시한다.
         * 반환값은 헤더에서 바로 출력할 수 있는 작은 HTML 조각이다.
         */
        function checkmail(){
          global $OJ_NAME;
          $sql="select count(1) cnt FROM `mail` WHERE new_mail=1 AND `to_user`=?";
          $result=pdo_query($sql,$_SESSION[$OJ_NAME.'_'.'user_id']);
          if(empty($result)) return false;
          $row=$result[0];
          $retmsg="<span id=red>(".$row['cnt'].")</span>";
          return $retmsg;
        }

        /**
         * 상단 메뉴에 노출할 공지(news.menu=1)를 HTML 링크 목록으로 만든다.
         * Redis 또는 세션 캐시에 저장되는 값이므로 여기서는 완성된 HTML 문자열을 반환한다.
         */
        function get_menu_news() {
            $result = "";
            /** @noinspection SqlNoDataSourceInspection */
            $sql_news_menu = "select `news_id`,`title` FROM `news` WHERE `menu`=1 AND `title`!='faqs.cn' ORDER BY `importance` ASC,`time` DESC LIMIT 10";
            $sql_news_menu_result = mysql_query_cache( $sql_news_menu );
            if ( $sql_news_menu_result ) {
                foreach ( $sql_news_menu_result as $row ) {
                    $result .= '<a class="item" href="/viewnews.php?id=' . $row['news_id'] . '">' ."<i class='star icon'></i>" . $row['title'] . '</a>';
                }
            }
            return $result;
        }
        
        // 현재 요청 파일명을 기준으로 상단 메뉴의 active 상태를 계산한다.
        // 꼬리표(?page=1 등)는 무시하고 순수 파일명만 사용해야 하이라이트가 안정적으로 동작한다.
        $url = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        if ($url == "") $url = "index.php"; // 기본 루트 접속 시 index.php로 간주
        
        $dir=basename(getcwd());
        // admin/index.php도 이 헤더를 그대로 쓰므로 admin 폴더에서는 상위 경로로 링크를 건다.
        if($dir=="discuss3"||$dir=="admin") $path_fix="../";
        else $path_fix="";
        
        // 로그인 필수 모드일 때는 로그인/비밀번호 찾기/회원가입 화면만 예외로 열어 둔다.
        if(isset($OJ_NEED_LOGIN)&&$OJ_NEED_LOGIN&&(
                  $url!='loginpage.php'&&
                  $url!='lostpassword.php'&&
                  $url!='lostpassword2.php'&&
                  $url!='registerpage.php'
                  ) && !isset($_SESSION[$OJ_NAME.'_'.'user_id'])){

           header("location:".$path_fix."loginpage.php");
           exit();
        }

        // 온라인 사용자 집계가 켜져 있으면 현재 접속자를 기록한다.
        if($OJ_ONLINE){
                require_once($path_fix.'include/online.php');
                $on = new online();
        }

        $sql_news_menu_result_html = "";

        // 공지사항 메뉴는 매 요청마다 DB를 치지 않도록 Redis 또는 세션에 캐시한다.
        if ($OJ_MENU_NEWS) {
            if ($OJ_REDIS) {
                $redis = new Redis();
                $redis->connect($OJ_REDISSERVER, $OJ_REDISPORT);

                if (isset($OJ_REDISAUTH)) {
                  $redis->auth($OJ_REDISAUTH);
                }
                $redisDataKey = $OJ_REDISQNAME . '_MENU_NEWS_CACHE';
                if ($redis->exists($redisDataKey)) {
                    $sql_news_menu_result_html = $redis->get($redisDataKey);
                } else {
                    $sql_news_menu_result_html = get_menu_news();
                    $redis->set($redisDataKey, $sql_news_menu_result_html);
                    $redis->expire($redisDataKey, 300);
                }
                $redis->close();
            } else {
                $sessionDataKey = $OJ_NAME.'_'."_MENU_NEWS_CACHE";
                if (isset($_SESSION[$sessionDataKey])) {
                    $sql_news_menu_result_html = $_SESSION[$sessionDataKey];
                } else {
                    $sql_news_menu_result_html = get_menu_news();
                    $_SESSION[$sessionDataKey] = $sql_news_menu_result_html;
                }
            }
            
            // 동적 공지 메뉴 하이라이트 처리.
            // 캐시된 HTML에서 현재 보고 있는 공지 id를 찾아 active 클래스를 강제로 붙인다.
            if ($url == 'viewnews.php' && isset($_GET['id'])) {
                $nid = intval($_GET['id']);
                $target = 'class="item" href="/viewnews.php?id=' . $nid . '"';
                $replace = 'class="item active" href="/viewnews.php?id=' . $nid . '"';
                $sql_news_menu_result_html = str_replace($target, $replace, $sql_news_menu_result_html);
            }
        }
?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="utf-8">
    <meta content="IE=edge" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $show_title ?></title>
    <?php include(dirname(__FILE__)."/css.php");?>
    <style>
        body#MainBg-C {
            min-height: calc(100vh - 72px);
            overflow-x: hidden;
        }

        /* GSMOJ 상단 헤더 */
        .gs-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 99999;
            height: 72px;
            border-bottom: 1px solid #eef0f2;
            background: #fff;
        }
        .gs-header *, .gs-header *::before, .gs-header *::after { box-sizing: border-box; }
        /* 헤더 아래 역할별 메뉴 줄 (관리 권한이 있는 사용자만) */
        .gs-rolebar {
            position: fixed;
            top: 72px;
            left: 0;
            right: 0;
            z-index: 99998;
            height: 40px;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
        }
        /* .ui.container의 display:block보다 우선하도록 선택자를 구체화한다. */
        .gs-rolebar .ui.container.gs-rolebar-inner { display: flex; align-items: center; gap: 4px; height: 40px; white-space: nowrap; }
        .gs-rolebar-label { margin-right: 8px; padding: 2px 8px; border-radius: 6px; background: #111827; color: #fff; font-size: 12px; font-weight: 600; }
        .gs-rolebar a { padding: 6px 10px; border-radius: 7px; color: #4b5563; font-size: 13px; font-weight: 500; text-decoration: none; }
        .gs-rolebar a:hover { background: #eef2f7; color: #111827; }
        .gs-rolebar .gs-rolebar-status { margin-left: auto; padding: 4px 10px; border-radius: 999px; background: #e0f2fe; color: #075985; font-size: 12px; font-weight: 600; }
        .gs-rolebar .gs-rolebar-status.is-warn { background: #fef3c7; color: #92400e; }
        /* 관리자 화면의 첫째 줄: 관리 항목 그룹 탭. 탭을 고르면 둘째 줄에 그 그룹의 하위 기능이 나온다. */
        .gs-rolebar .gs-rb-tab { padding: 6px 12px; border: 0; border-radius: 7px; background: none; color: #4b5563; font: inherit; font-size: 13px; font-weight: 600; cursor: pointer; }
        .gs-rolebar .gs-rb-tab:hover { background: #eef2f7; color: #111827; }
        .gs-rolebar .gs-rb-tab.active { background: #111827; color: #fff; }
        /* 관리자 페이지의 헤더: 사이트 헤더(.gs-header)와 같은 크기·배치를 쓰고, 메뉴 자리에 그룹 탭을 둔다. */
        .gs-admin-tabs { display: flex; align-items: center; gap: 2px; min-width: 0; margin-left: 34px; white-space: nowrap; }
        .gs-admin-tabs .gs-rb-tab { padding: 8px 12px; border: 0; border-radius: 8px; background: none; color: #4b5563; font: inherit; font-size: 15px; font-weight: 500; cursor: pointer; }
        .gs-admin-tabs .gs-rb-tab:hover { background: #f3f4f6; color: #111827; }
        .gs-admin-tabs .gs-rb-tab.active { background: #111827; color: #fff; }
        .gs-admin-tabs > a { padding: 8px 12px; border-radius: 8px; color: #4b5563; font-size: 15px; font-weight: 500; }
        .gs-admin-tabs > a:hover { background: #f3f4f6; color: #111827; }
        .gs-header .gs-rolebar-status { margin-left: auto; padding: 4px 10px; border-radius: 999px; background: #e0f2fe; color: #075985; font-size: 12px; font-weight: 600; white-space: nowrap; }
        .gs-header .gs-rolebar-status.is-warn { background: #fef3c7; color: #92400e; }
        .gs-subbar { position: fixed; top: 72px; left: 0; right: 0; z-index: 99997; height: 40px; border-bottom: 1px solid #e5e7eb; background: #fff; }
        .gs-subbar .ui.container.gs-subbar-inner { height: 40px; }
        .gs-subbar .gs-rb-sub { display: none; align-items: center; gap: 2px; height: 40px; overflow-x: auto; white-space: nowrap; scrollbar-width: none; }
        .gs-subbar .gs-rb-sub.active { display: flex; }
        .gs-subbar a { padding: 6px 10px; border-radius: 7px; color: #4b5563; font-size: 13px; text-decoration: none; }
        .gs-subbar a:hover { background: #f1f5f9; color: #111827; }
        .gs-subbar a.active { color: #2563eb; font-weight: 600; background: #eff6ff; }
        .gs-subbar a.is-danger { color: #dc2626; }
        /* 관리자 페이지로 가는 헤더 버튼(관리 권한이 있는 사용자만) */
        .gs-admin-link { display: inline-flex; align-items: center; height: 34px; margin-right: 8px; padding: 0 12px; border: 1px solid #d1d5db; border-radius: 8px; color: #111827 !important; font-size: 13px; font-weight: 600; white-space: nowrap; }
        .gs-admin-link:hover { background: #f3f4f6; }
        body.has-rolebar#MainBg-C { min-height: calc(100vh - 112px); }
        .gs-header a { text-decoration: none; }
        #menu.gs-header-inner {
            display: flex;
            align-items: center;
            height: 72px;
        }
        .gs-logo {
            display: inline-flex;
            flex: 0 0 auto;
            align-items: center;
            gap: 10px;
            min-width: 0;
            color: #111827;
        }
        .gs-logo:hover { color: #111827; }
        .gs-logo-mark {
            display: inline-flex;
            flex: 0 0 30px;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: #2563eb;
            color: #fff;
        }
        .gs-logo-mark svg { width: 18px; height: 18px; }
        .gs-logo-text {
            overflow: hidden;
            font-size: 20px;
            font-weight: 600;
            letter-spacing: -.01em;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .gs-collapse {
            display: flex;
            flex: 1 1 auto;
            align-items: center;
            min-width: 0;
        }
        .gs-nav {
            display: flex;
            flex: 0 0 auto; /* 메뉴는 줄어들지 않고, 남는 폭은 검색창이 줄어서 맞춘다. */
            align-items: center;
            gap: 2px;
            min-width: 0;
            margin-left: 34px;
        }
        .gs-nav a, .gs-nav .gs-nav-group > span {
            display: inline-flex;
            align-items: center;
            padding: 8px 15px;
            border-radius: 8px;
            color: #4b5563;
            font-size: 16px;
            font-weight: 500;
            white-space: nowrap;
            cursor: pointer;
            transition: color .15s ease, background-color .15s ease;
        }
        .gs-nav a:hover, .gs-nav .gs-nav-group:hover > span { color: #111827; }
        .gs-nav a.active, .gs-nav .gs-nav-group > span.active { color: #111827; font-weight: 600; }
        .gs-nav i.icon { display: none; }
        .gs-nav-group, .gs-user { position: relative; }
        .gs-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            z-index: 10;
            display: none;
            min-width: 200px;
            padding: 6px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 12px 28px rgba(15, 23, 42, .12);
        }
        .gs-nav-group > .gs-dropdown { left: 0; right: auto; }
        .gs-nav-group:hover > .gs-dropdown,
        .gs-nav-group:focus-within > .gs-dropdown,
        .gs-user:hover > .gs-dropdown,
        .gs-user:focus-within > .gs-dropdown { display: block; }
        .gs-dropdown a, .gs-nav .gs-dropdown a {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            padding: 9px 12px;
            border-radius: 8px;
            color: #374151;
            font-size: 14px;
            font-weight: 500;
            white-space: nowrap;
            cursor: pointer;
        }
        .gs-dropdown a:hover { background: #f3f4f6; color: #111827; }
        .gs-dropdown a i.icon { display: inline-block; margin: 0; color: #9ca3af; }
        .gs-dropdown #red { color: #ef4444; }
        .gs-user-meta {
            margin-bottom: 6px;
            padding: 8px 12px 10px;
            border-bottom: 1px solid #f1f3f5;
            color: #6b7280;
            font-size: 13px;
            line-height: 1.5;
            white-space: nowrap;
        }
        .gs-user-meta strong { display: block; color: #111827; font-size: 14px; }
        .gs-actions {
            display: flex;
            flex: 0 1 auto;
            align-items: center;
            gap: 12px;
            min-width: 0;
            margin-left: auto;
            padding-left: 16px;
        }
        .gs-search { position: relative; flex: 0 1 240px; width: 240px; min-width: 140px; margin: 0 28px 0 0; }
        .gs-search > svg {
            position: absolute;
            top: 20px; /* 입력창(40px) 기준 중앙. 모바일에서 결과 패널이 펼쳐져도 위치가 유지된다. */
            left: 14px;
            width: 18px;
            height: 18px;
            color: #9ca3af;
            transform: translateY(-50%);
            pointer-events: none;
        }
        .gs-search input[type="search"] {
            width: 100%;
            height: 40px;
            min-height: 40px;
            padding: 0 64px 0 40px !important;
            border: 1px solid transparent;
            border-radius: 10px;
            outline: 0;
            background: #f3f4f6;
            color: #111827;
            font-size: 14px;
            font-weight: 400;
        }
        .gs-search input[type="search"]::placeholder { color: #9ca3af; }
        .gs-search input[type="search"]:focus { border-color: #2563eb; background: #fff; box-shadow: 0 0 0 3px rgba(37, 99, 235, .12); }
        .gs-search kbd {
            position: absolute;
            top: 20px;
            right: 8px;
            padding: 1px 6px;
            border: 1px solid #d1d5db;
            border-radius: 5px;
            background: #fff;
            color: #9ca3af;
            font-family: inherit;
            font-size: 11px;
            line-height: 1.5;
            transform: translateY(-50%);
            pointer-events: none;
        }
        /* 검색창이 좁아져도(1299px 이하에서 180px) 안내 문구가 잘리지 않도록 패널은 최소 300px을 유지한다. */
        .gs-search-panel { top: calc(100% + 6px); left: 0; right: auto; width: max(100%, 300px); min-width: 0; }
        .gs-search-panel.open { display: block; }
        .gs-search-panel a { display: block; overflow: hidden; text-overflow: ellipsis; }
        .gs-search-panel a.selected { background: #f3f4f6; }
        .gs-search-panel b { color: #2563eb; font-weight: 600; }
        .gs-bell {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            color: #374151;
        }
        .gs-bell:hover { background: #f3f4f6; color: #111827; }
        .gs-bell svg { width: 22px; height: 22px; }
        .gs-bell-dot { position: absolute; top: 6px; right: 7px; width: 8px; height: 8px; border-radius: 50%; background: #ef4444; box-shadow: 0 0 0 2px #fff; }
        .gs-user-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 0;
            max-width: 180px;
            padding: 4px 0;
            border: 0;
            background: transparent;
            color: #111827;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        .gs-avatar {
            display: inline-flex;
            flex: 0 0 32px;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e8f0fe;
            color: #2563eb;
            font-size: 13px;
            font-weight: 600;
        }
        .gs-user-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .gs-auth { display: flex; align-items: center; gap: 8px; }
        .gs-auth a {
            display: inline-flex;
            align-items: center;
            height: 38px;
            padding: 0 16px;
            border-radius: 10px;
            color: #374151;
            font-size: 14px;
            font-weight: 600;
            white-space: nowrap;
        }
        .gs-auth a:hover { background: #f3f4f6; color: #111827; }
        .gs-auth a.primary { background: #2563eb; color: #fff; }
        .gs-auth a.primary:hover { background: #1d4ed8; color: #fff; }
        #mobile-menu-toggle {
            display: none;
            flex: 0 0 40px;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            min-height: 0;
            margin-left: auto;
            padding: 0;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: #111827;
            cursor: pointer;
        }
        #mobile-menu-toggle:hover { background: #f3f4f6; }
        #mobile-menu-toggle svg { width: 22px; height: 22px; }

        /* 1300px 미만에서는 메뉴 6개와 검색창이 겹치므로 간격을 줄인다. */
        @media (max-width: 1299px) {
            .gs-nav { margin-left: 20px; }
            .gs-nav a, .gs-nav .gs-nav-group > span { padding: 8px 10px; font-size: 15px; }
            .gs-search { flex-basis: 180px; width: 180px; margin-right: 8px; }
            .gs-search input[type="search"] { padding-right: 12px !important; }
            .gs-search kbd { display: none; }
            .gs-user-button { max-width: 130px; }
        }

        @media (max-width: 991px) {
            .mobile-only { display: block !important; }
            .desktop-only { display: none !important; }
            #mobile-menu-toggle { display: inline-flex; }
            .gs-collapse {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                display: none;
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
                max-height: calc(100vh - 72px);
                padding: 12px 1em 16px;
                overflow-y: auto;
                border-bottom: 1px solid #eef0f2;
                background: #fff;
                box-shadow: 0 12px 20px rgba(15, 23, 42, .08);
            }
            .gs-collapse.open { display: flex; }
            .gs-nav { flex-direction: column; align-items: stretch; gap: 0; margin-left: 0; }
            .gs-nav a, .gs-nav .gs-nav-group > span { padding: 11px 12px; font-size: 15px; }
            .gs-nav a.active { background: #f3f4f6; }
            .gs-nav { flex: none; }
            .gs-actions { flex-direction: column; align-items: stretch; gap: 10px; margin-left: 0; padding-left: 0; }
            .gs-search { order: -1; flex: none; width: 100%; margin: 0; }
            /* 모바일 메뉴 안에서는 패널을 띄우지 않고 검색창 아래에 펼쳐, 메뉴 밖으로 잘리거나 본문 글자와 겹치지 않게 한다. */
            .gs-search-panel { position: static; width: 100%; margin-top: 6px; box-shadow: none; }
            .gs-bell { width: auto; justify-content: flex-start; gap: 8px; padding: 0 12px; }
            .gs-bell::after { content: attr(aria-label); font-size: 15px; font-weight: 500; }
            .gs-bell-dot { left: 26px; right: auto; }
            .gs-user-button { max-width: none; padding: 4px 12px; cursor: default; }
            .gs-nav-group > .gs-dropdown, .gs-user > .gs-dropdown {
                position: static;
                display: block;
                padding: 0;
                border: 0;
                box-shadow: none;
            }
            .gs-user-meta { display: none; }
            .gs-dropdown a, .gs-nav .gs-dropdown a { padding: 11px 12px; font-size: 15px; }
            .gs-auth a { flex: 1 1 0; justify-content: center; background: #f3f4f6; }
        }
    </style>

    <script src="<?php echo "$OJ_CDN_URL/template/syzoj/js/"?>jquery.min.js"></script>

<script>
    console.log('\n %c HUSTOJ %c https://github.com/zhblue/hustoj %c\n', 'color: #fadfa3; background: #000000; padding:5px 0;', 'background: #fadfa3; padding:5px 0;', '');
    console.log('\n %c Theme By %c Baoshuo ( @renbaoshuo ) %c https://baoshuo.ren %c\n', 'color: #fadfa3; background: #000000; padding:5px 0;', 'background: #fadfa3; padding:5px 0;', 'background: #ffbf33; padding:5px 0;', '');
    console.log('\n GitHub Homepage: https://github.com/zhblue/hustoj \n Document: https://zhblue.github.io/hustoj \n Bug report URL: https://github.com/zhblue/hustoj/issues \n \n%c ★ Please give us a star on GitHub! ★ %c \n', 'color: red;', '')
</script>
</head>

<?php
        if(!isset($_GET['spa'])){
?>
   
<?php
        // 관리 권한(관리자·교사·출제자 등)이 있으면 헤더에 관리자/교사 페이지 버튼을 보여 준다.
        // 관리자 메뉴 줄(그룹 탭 + 하위 기능 줄)은 관리자 페이지(admin/index.php) 안에서만 보여 준다.
        $gs_rolebar_status = "";
        $gs_is_admin = isset($_SESSION[$OJ_NAME.'_'.'administrator']);
        $gs_is_teacher = isset($_SESSION[$OJ_NAME.'_'.'teacher']);
        $gs_admin_url = $path_fix."admin/";
        // 관리 항목 그룹(공지·사용자·문제·대회·수행평가·시스템 등)은 admin/menu_items.inc.php가 권한별로 정한다.
        require_once(dirname(__FILE__)."/../../admin/menu_items.inc.php");
        $gs_admin_groups = gs_admin_menu();
        $gs_has_admin_page = count($gs_admin_groups) || isset($_SESSION[$OJ_NAME.'_'.'user_adder']) || isset($_SESSION[$OJ_NAME.'_'.'password_setter']);
        $gs_admin_page_name = $gs_is_admin || !$gs_is_teacher ? "관리자 페이지" : "교사 페이지";
        $gs_in_admin = ($dir == "admin") && $gs_has_admin_page;
        $gs_active_group = 0;
        if ($gs_in_admin) {
            if ($gs_is_admin) {
                $gs_running = mysql_query_cache("select count(*) from contest where defunct='N' and start_time<=now() and end_time>now()");
                $gs_waiting = mysql_query_cache("select count(*) from solution where problem_id>0 and result in (0,1,2,3)");
                $gs_running = empty($gs_running) ? 0 : intval($gs_running[0][0]);
                $gs_waiting = empty($gs_waiting) ? 0 : intval($gs_waiting[0][0]);
                $gs_rolebar_status = array("monitor.php#".($gs_waiting ? "queue" : "contest"),
                    "진행 중 대회 ".$gs_running."개 · 채점 대기 ".$gs_waiting."건", $gs_waiting >= 20 ? "is-warn" : "");
            } else if ($gs_is_teacher) {
                $gs_mine = mysql_query_cache("select count(*) from assessment_session s join assessment a on a.assessment_id=s.assessment_id join contest c on c.contest_id=s.contest_id where a.owner=? and c.start_time<=now() and c.end_time>now()", $_SESSION[$OJ_NAME.'_'.'user_id']);
                $gs_mine = empty($gs_mine) ? 0 : intval($gs_mine[0][0]);
                $gs_rolebar_status = array("assessment_list.php", "진행 중인 내 수행평가 회차 ".$gs_mine."개", "");
            }
            // 처음 여는 본문($main_src)이 속한 그룹을 둘째 줄에 펼쳐 둔다. 주소가 그대로 같은 항목을 먼저 찾고, 없으면 파일 이름으로 찾는다.
            $gs_main_page = isset($main_src) ? preg_replace('/#.*$/', '', $main_src) : "";
            $gs_main_file = preg_replace('/\?.*$/', '', $gs_main_page);
            $gs_file_group = -1;
            foreach ($gs_admin_groups as $gs_i => $gs_group) {
                foreach ($gs_group[1] as $gs_item) {
                    if ($gs_item[0] === $gs_main_page) { $gs_file_group = $gs_i; break 2; }
                    if ($gs_file_group < 0 && preg_replace('/\?.*$/', '', $gs_item[0]) === $gs_main_file) $gs_file_group = $gs_i;
                }
            }
            if ($gs_file_group >= 0) $gs_active_group = $gs_file_group;
            // 서버 모니터링을 열면 그룹 탭 대신 서버 모니터링 탭을 선택하고, 하위 기능 줄은 비워 둔다.
            if ($gs_main_file === "monitor.php") $gs_active_group = "monitor";
        }
        $gs_top = $gs_in_admin ? 112 : 72;  // 고정 헤더 72px. 관리자 페이지는 같은 높이의 관리자 헤더 + 하위 기능 줄 40px
?>
<body id="MainBg-C" class="<?php if ($gs_in_admin) echo 'has-rolebar'?>" style="position: relative; margin-top: <?php echo $gs_top?>px; min-height: calc(100vh - <?php echo $gs_top?>px); overflow-x: hidden; overflow-y: auto;">
<?php
        $gs_logged_in = isset($_SESSION[$OJ_NAME.'_'.'user_id']);
        $gs_site_name = $domain==$DOMAIN?$OJ_NAME:ucwords($OJ_NAME)."'s OJ";

        // 쪽지 기능이 꺼져 있거나 시험·현장 대회 모드이면 알림(쪽지)을 표시하지 않는다.
        $gs_mail_html = false;
        $gs_mail_count = 0;
        if($gs_logged_in && !((isset($OJ_EXAM_CONTEST_ID)&&$OJ_EXAM_CONTEST_ID>0)||
               (isset($OJ_ON_SITE_CONTEST_ID)&&$OJ_ON_SITE_CONTEST_ID>0)||
               (isset($OJ_MAIL)&&!$OJ_MAIL))){
            $gs_mail_html = checkmail();
            if($gs_mail_html) $gs_mail_count = intval(preg_replace('/\D/', '', strip_tags($gs_mail_html)));
        }
?>
    <?php if (!$gs_in_admin) { // 관리자 페이지에서는 사이트 헤더를 그리지 않는다. ?>
    <header id="page-header" class="gs-header">
        <div id="menu" class="ui container gs-header-inner">
            <a class="gs-logo" href="/">
                <span class="gs-logo-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m9 7-5 5 5 5"/><path d="m15 7 5 5-5 5"/></svg></span>
                <span class="gs-logo-text"><?php echo $gs_site_name?></span>
            </a>
            <button type="button" id="mobile-menu-toggle" aria-controls="gs-collapse" aria-expanded="false" aria-label="<?php echo htmlentities($MSG_MENU, ENT_QUOTES, 'UTF-8');?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>

            <div class="gs-collapse" id="gs-collapse">
            <nav class="gs-nav" aria-label="<?php echo htmlentities($MSG_MENU, ENT_QUOTES, 'UTF-8');?>">
          <?php
            if(isset($OJ_AI_HTML)&&$OJ_AI_HTML && !isset($OJ_ON_SITE_CONTEST_ID) ) echo $OJ_AI_HTML;

            if(file_exists("moodle")){
              echo '<a class="item '.($url=="moodle" ? "active" : "").'" href="moodle">Moodle</a>';
            }
            if(file_exists("hello")){
              echo '<a class="item" onclick=\'window.open("/hello/index.html", "_blank",
        "width=600,height=850,left=" + (window.screen.width-600)  + ",top=0,toolbar=no,menubar=no,location=no,status=no,resizable=yes");\'>Hello알고리즘</a>';
            }

             if( !isset($OJ_ON_SITE_CONTEST_ID) && (!isset($_GET['cid'])||$cid==0) ){
          ?>
            <a class="item <?php if ($url=="problemset.php" || $url=="problem.php" || $url=="category.php") echo "active";?>"
                href="<?php echo $path_fix?>problemset.php"><?php echo $MSG_PROBLEMS?></a>
            <a class="item <?php if ($url=="contest.php") echo "active";?>" href="<?php echo $path_fix?>contest.php<?php if(isset($_SESSION[$OJ_NAME."_user_id"])) echo "?my" ?>"><?php echo $MSG_CONTEST?></a>
            <a class="item <?php if ($url=="assessment.php") echo "active";?>" href="<?php echo $path_fix?>assessment.php<?php if(isset($_SESSION[$OJ_NAME."_user_id"])) echo "?my" ?>"><?php echo isset($MSG_ASSESSMENT) ? $MSG_ASSESSMENT : "수행평가"?></a>
            <a class="item <?php if ($url=="status.php" || $url=="showsource.php") echo "active";?>" href="<?php echo $path_fix?>status.php"><?php echo $MSG_STATUS?></a>
            <a class="item <?php if ($url=="ranklist.php") echo "active";?>"
                href="<?php echo $path_fix?>ranklist.php"><?php echo $MSG_RANKLIST?></a>
            <?php if(isset($OJ_RECENT_CONTEST)&&$OJ_RECENT_CONTEST){    ?>
            <a class="item <?php if ($url=="recent-contest.php") echo "active";?>"
                href="<?php echo $path_fix?>recent-contest.php"><?php echo $MSG_RECENT_CONTEST?></a>
            <?php } ?>
            <?php if (isset($OJ_BBS)&& $OJ_BBS){ ?>
                  <a class='item <?php if ($url=="discuss.php") echo "active";?>' href="discuss.php"><?php echo $MSG_BBS?></a>
              <?php }

            }
                ?>
            <?php if( isset($_GET['cid']) && intval($_GET['cid'])>0 ){
                     $cid=intval($_GET['cid']);
                     if(!isset($OJ_ON_SITE_CONTEST_ID)){
                       // 수행평가 안에서는 목록 링크를 수행평가 목록으로 보낸다.
                       $gs_cid_assessment = function_exists("is_assessment_contest") && is_assessment_contest($cid);   ?>
                            <a class="item" href="<?php echo $path_fix.($gs_cid_assessment ? "assessment.php" : "contest.php")?>"><?php echo ($gs_cid_assessment ? (isset($MSG_ASSESSMENT) ? $MSG_ASSESSMENT : "수행평가") : $MSG_CONTEST).$MSG_LIST?></a>
            <?php    }      ?>
            <a class="item active" href="<?php echo $path_fix?>contest.php?cid=<?php echo $cid?>"><?php echo $MSG_PROBLEMS.$MSG_LIST?></a>
            <a class="item active" href="<?php echo $path_fix?>status.php?cid=<?php echo $cid?>"><?php echo $MSG_STATUS.$MSG_LIST?></a>
            <?php // 수행평가는 순위를 공개하지 않으므로 관리자와 회차 관리자(교사)에게만 순위 메뉴를 보여 준다.
            if (!(function_exists("is_assessment_contest") && is_assessment_contest($cid)) || isset($_SESSION[$OJ_NAME.'_'.'administrator']) || isset($_SESSION[$OJ_NAME.'_m'.$cid])) { ?>
            <a class="item active" href="<?php echo $path_fix?>contestrank.php?cid=<?php echo $cid?>"><?php echo $MSG_RANKLIST?></a>
            <a class="item active" href="<?php echo $path_fix?>contestrank-oi.php?cid=<?php echo $cid?>">OI-<?php echo $MSG_RANKLIST?></a>
            <?php } ?>
            <?php if (isset($OJ_BBS)&& $OJ_BBS){ ?>
                  <a class='item active' href="discuss.php?cid=<?php echo $cid?>"><?php echo $MSG_BBS?></a>
             <?php } ?>

                    <?php if(isset($_SESSION[$OJ_NAME.'_'.'administrator'])||isset($_SESSION[$OJ_NAME.'_'.'contest_creator'])||isset($_SESSION[$OJ_NAME.'_'.'problem_editor'])){ ?>
                            <a class="item active" href="<?php echo $path_fix?>conteststatistics.php?cid=<?php echo $cid?>"><?php echo $MSG_STATISTICS?></a>
                    <?php }  ?>
            <?php }  ?>
            <?php
                if($OJ_MENU_DROPDOWN){
            ?>
            <div class="gs-nav-group">
                        <span tabindex="0" class="<?php if ($url=="viewnews.php") echo "active";?>">학습자료</span>
                        <div class="gs-dropdown">
            <?php  } ?>
            <?php echo $sql_news_menu_result_html; ?>
            <?php
            if($OJ_MENU_DROPDOWN){
                ?>
                        </div>
            </div>
            <?php } ?>
            </nav>

            <div class="gs-actions">
                <form class="gs-search" id="gs-search" action="<?php echo $path_fix?>problemset.php" method="get" role="search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input type="search" name="search" id="gs-search-input" placeholder="문제, 사용자 검색" aria-label="문제, 사용자 검색" autocomplete="off">
                    <kbd>Ctrl K</kbd>
                    <div class="gs-dropdown gs-search-panel" id="gs-search-panel">
                        <a data-base="<?php echo $path_fix?>problemset.php?search=">문제에서 <b></b> 검색</a>
                        <a data-base="<?php echo $path_fix?>ranklist.php?prefix=">사용자에서 <b></b> 검색</a>
                    </div>
                </form>
                <?php if($gs_logged_in) {
                        $gs_user_id = $_SESSION[$OJ_NAME.'_'.'user_id'];
                        $gs_user_name = !empty($_SESSION[$OJ_NAME.'_nick']) ? $_SESSION[$OJ_NAME.'_nick'] : $gs_user_id;
                ?>
                <?php if($gs_mail_html){ ?>
                <a class="gs-bell" href="<?php echo $path_fix?>mail.php" aria-label="<?php echo htmlentities($MSG_MAIL, ENT_QUOTES, 'UTF-8');?>" title="<?php echo htmlentities($MSG_MAIL, ENT_QUOTES, 'UTF-8');?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9a6 6 0 0 1 12 0c0 5 2 6.5 2 6.5H4S6 14 6 9"/><path d="M10 19.5a2.2 2.2 0 0 0 4 0"/></svg>
                    <?php if($gs_mail_count>0){ ?><span class="gs-bell-dot"></span><?php } ?>
                </a>
                <?php } ?>
                <?php if ($gs_has_admin_page && !$gs_in_admin) { ?>
                <a class="gs-admin-link" href="<?php echo $gs_admin_url?>"><?php echo $gs_admin_page_name?></a>
                <?php } ?>
                <div class="gs-user">
                    <button type="button" class="gs-user-button" aria-haspopup="true">
                        <span class="gs-avatar" aria-hidden="true"><?php echo htmlentities(mb_substr($gs_user_name, 0, 1, 'UTF-8'), ENT_QUOTES, 'UTF-8')?></span>
                        <span class="gs-user-name"><?php echo htmlentities($gs_user_name, ENT_QUOTES, 'UTF-8')?></span>
                    </button>
                    <div class="gs-dropdown">
                        <div class="gs-user-meta">
                            <strong><?php echo htmlentities($gs_user_id, ENT_QUOTES, 'UTF-8')?></strong>
                            <?php
                              if(!empty($_SESSION[$OJ_NAME.'_group_name'])) echo htmlentities($_SESSION[$OJ_NAME.'_group_name'], ENT_QUOTES, 'UTF-8')." ";
                              if($coin>0) echo "💰️$coin";
                            ?>
                        </div>
                                <a class="item" href="<?php echo $path_fix?>userinfo.php?user=<?php echo htmlentities(urlencode($gs_user_id), ENT_QUOTES, 'UTF-8')?>"><i class="user icon"></i><?php echo $MSG_USERINFO;?></a>
                                <a class="item" href="modifypage.php"><i class="edit icon"></i><?php echo $MSG_REG_INFO;?></a>
                                <a class="item" href="portal.php"><i class="tasks icon"></i><?php echo $MSG_TODO;?></a>
                                <?php if ($OJ_SaaS_ENABLE){ ?>
                                <?php if($_SERVER['HTTP_HOST']==$DOMAIN)
                                        echo  "<a class='item' href='http://".htmlentities($_SESSION[$OJ_NAME.'_'.'user_id']).".$DOMAIN'><i class='globe icon' ></i>MyOJ</a>";?>
                                <?php } ?>
                            <?php if($gs_has_admin_page){ ?>
                            <a class="item" href="<?php echo $gs_admin_url?>"><i class="settings icon"></i><?php echo $gs_admin_page_name;?></a>
                            <?php }
if(isset($_SESSION[$OJ_NAME.'_'.'balloon'])){
  echo "<a class=item href='balloon.php'><i class='golf ball icon'></i>$MSG_BALLOON</a>";
}
                              if($gs_mail_html) echo "<a class='item mail' href=".$path_fix."mail.php><i class='mail icon'></i>$MSG_MAIL$gs_mail_html</a>";
                            ?>
        <?php
        if(isset($OJ_PRINTER) && $OJ_PRINTER)
        {
        ?>
          <a  class="item"  href="printer.php">
            <i class="print icon"></i> <?php echo $MSG_PRINTER?>
          </a>
        <?php
        }
        ?>
                            <a class="item" href="logout.php"><i class="power icon"></i><?php echo $MSG_LOGOUT;?></a>
                    </div>
                </div>
                <?php } else { ?>
                <div class="gs-auth">
                    <a href="loginpage.php"><?php echo $MSG_LOGIN?></a>
                    <?php if(isset($OJ_REGISTER)&&$OJ_REGISTER ){ ?>
                    <a class="primary" href="registerpage.php"><?php echo $MSG_REGISTER?></a>
                    <?php } ?>
                </div>
                <?php } ?>
            </div>
            </div>
        </div>
        <script>
        (function () {
            var panel = document.getElementById('gs-collapse');
            var toggle = document.getElementById('mobile-menu-toggle');
            if (panel && toggle) {
                toggle.addEventListener('click', function () {
                    var isOpen = panel.classList.toggle('open');
                    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                });

                window.addEventListener('resize', function () {
                    if (window.innerWidth > 991 && panel.classList.contains('open')) {
                        panel.classList.remove('open');
                        toggle.setAttribute('aria-expanded', 'false');
                    }
                });
            }

            // 통합 검색: 기본은 문제 검색이고, 목록에서 사용자 검색을 고를 수 있다.
            var form = document.getElementById('gs-search');
            var input = document.getElementById('gs-search-input');
            var results = document.getElementById('gs-search-panel');
            if (!form || !input || !results) return;
            var links = results.querySelectorAll('a');
            var selected = 0;

            function render() {
                var keyword = input.value.replace(/^\s+|\s+$/g, '');
                for (var i = 0; i < links.length; i++) {
                    links[i].href = links[i].getAttribute('data-base') + encodeURIComponent(keyword);
                    links[i].querySelector('b').textContent = '"' + keyword + '"';
                    links[i].className = i === selected ? 'selected' : '';
                }
                if (keyword !== '' && document.activeElement === input) results.classList.add('open');
                else results.classList.remove('open');
            }

            input.addEventListener('input', function () { selected = 0; render(); });
            input.addEventListener('focus', render);
            input.addEventListener('blur', function () { window.setTimeout(function () { results.classList.remove('open'); }, 150); });
            input.addEventListener('keydown', function (e) {
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    selected = (selected + (e.key === 'ArrowDown' ? 1 : links.length - 1)) % links.length;
                    render();
                } else if (e.key === 'Escape') {
                    input.blur();
                }
            });
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (input.value.replace(/^\s+|\s+$/g, '') === '') return;
                render();
                window.location.href = links[selected].href;
            });
            document.addEventListener('keydown', function (e) {
                if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
                    e.preventDefault();
                    if (window.innerWidth <= 991 && panel && toggle && !panel.classList.contains('open')) toggle.click();
                    input.focus();
                    input.select();
                }
            });
        })();
        </script>
    </header>
    <?php } ?>
    <?php if ($gs_in_admin) { ?>
    <header id="page-header" class="gs-header">
        <div id="menu" class="ui container gs-header-inner">
            <a class="gs-logo" href="/" title="첫 화면으로">
                <span class="gs-logo-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m9 7-5 5 5 5"/><path d="m15 7 5 5-5 5"/></svg></span>
                <span class="gs-logo-text"><?php echo $gs_site_name?></span>
            </a>
            <nav class="gs-admin-tabs" role="tablist" aria-label="관리 메뉴">
                <?php if ($gs_is_admin) { ?><a class="gs-rb-tab<?php if ($gs_active_group === "monitor") echo ' active'?>" href="monitor.php" target="main" role="tab" data-group="monitor" aria-selected="<?php echo $gs_active_group === "monitor" ? 'true' : 'false'?>">서버 모니터링</a><?php } ?>
                <?php foreach ($gs_admin_groups as $gs_i => $gs_group) { ?>
                <button type="button" class="gs-rb-tab<?php if ($gs_i === $gs_active_group) echo ' active'?>" role="tab" data-group="<?php echo $gs_i?>" aria-selected="<?php echo $gs_i === $gs_active_group ? 'true' : 'false'?>"><?php echo htmlentities($gs_group[0], ENT_QUOTES, 'UTF-8')?></button>
                <?php } ?>
            </nav>
            <?php if ($gs_rolebar_status) { ?>
            <a class="gs-rolebar-status <?php echo $gs_rolebar_status[2]?>" href="<?php echo htmlentities($gs_rolebar_status[0], ENT_QUOTES, 'UTF-8')?>" target="main" title="자세히 보기"><?php echo htmlentities($gs_rolebar_status[1], ENT_QUOTES, 'UTF-8')?></a>
            <?php } ?>
        </div>
    </header>
    <nav class="gs-subbar" aria-label="하위 기능">
        <div class="ui container gs-subbar-inner">
            <?php foreach ($gs_admin_groups as $gs_i => $gs_group) { ?>
            <div class="gs-rb-sub<?php if ($gs_i === $gs_active_group) echo ' active'?>" role="tabpanel" data-group="<?php echo $gs_i?>">
                <?php foreach ($gs_group[1] as $gs_item) {
                    list($gs_href, $gs_target) = gs_admin_menu_link($gs_item[0], true, $path_fix);
                    $gs_class = trim((isset($gs_item[2]) && $gs_item[2] == "danger" ? "is-danger " : "").(isset($main_src) && $gs_item[0] === preg_replace('/#.*$/', '', $main_src) ? "active" : "")); ?>
                <a href="<?php echo htmlentities($gs_href, ENT_QUOTES, 'UTF-8')?>" target="<?php echo $gs_target?>"<?php if ($gs_class) echo ' class="'.$gs_class.'"'?>><?php echo htmlentities($gs_item[1], ENT_QUOTES, 'UTF-8')?></a>
                <?php } ?>
            </div>
            <?php } ?>
        </div>
    </nav>
    <script>
    // 그룹 탭을 누르면 둘째 줄을 그 그룹의 하위 기능으로 바꾼다. 하위 기능은 본문(iframe)으로 열리고 선택 표시만 옮긴다.
    (function () {
        var tabs = document.querySelectorAll('.gs-rb-tab');
        var subs = document.querySelectorAll('.gs-rb-sub');
        function select(tab) {
            tabs.forEach(function (t) { var on = t === tab; t.classList.toggle('active', on); t.setAttribute('aria-selected', on ? 'true' : 'false'); });
            subs.forEach(function (s) { s.classList.toggle('active', s.getAttribute('data-group') === tab.getAttribute('data-group')); });
        }
        tabs.forEach(function (tab) { tab.addEventListener('click', function () { select(tab); }); });
        // 상태 배지는 서버 모니터링을 여므로 서버 모니터링 탭을 선택한다.
        var monitorTab = document.querySelector('.gs-rb-tab[data-group="monitor"]');
        var badge = document.querySelector('.gs-rolebar-status');
        if (monitorTab && badge && badge.getAttribute('href').indexOf('monitor.php') === 0) badge.addEventListener('click', function () { select(monitorTab); });
        document.querySelectorAll('.gs-subbar a[target="main"]').forEach(function (a) {
            a.addEventListener('click', function () {
                document.querySelectorAll('.gs-subbar a.active').forEach(function (x) { x.classList.remove('active'); });
                a.classList.add('active');
            });
        });
    })();
    </script>
    <?php } ?>
    <div style="margin-top: <?php echo $gs_top?>px; ">
        <div id="main" class="ui main container">
<?php } ?>
