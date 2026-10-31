<?php
// 관리 메뉴 항목. 헤더 아래 관리자 메뉴 줄(template/syzoj/header.php)이 그룹 상자와 하위 기능 목록으로 보여 준다.
// 항목의 주소는 admin 폴더 기준이다. "../"로 시작하면 사이트 페이지, "http"로 시작하면 외부 링크다.
// 권한 조건은 기존 왼쪽 메뉴(menu2.php)와 같다.
function gs_admin_menu() {
    global $OJ_NAME, $OJ_TEMPLATE, $OJ_REMOTE_JUDGE, $domain, $DOMAIN;
    $has = function ($right) use ($OJ_NAME) { return isset($_SESSION[$OJ_NAME.'_'.$right]); };
    $admin = $has('administrator');
    $groups = array();

    $items = array();
    if ($admin) {
        if ($OJ_TEMPLATE == "bs3") $items[] = array("setmsg.php", "공지 메시지 설정");
        $items[] = array("news_list.php", "공지 목록");
        $items[] = array("news_add_page.php", "공지 등록");
    }
    $groups[] = array("공지", $items);

    $items = array();
    if ($admin || $has('password_setter')) $items[] = array("user_list.php", "사용자 목록");
    if ($admin) {
        $items[] = array("user_group.php", "반 지정");
        $items[] = array("user_merge.php", "계정 통합");
    }
    if ($admin || $has('user_adder')) {
        $items[] = array("user_add.php", "사용자 추가");
        $items[] = array("user_import.php", "사용자 가져오기");
    }
    if ($admin || $has('password_setter')) $items[] = array("changepass.php", "비밀번호 설정");
    if ($admin) {
        $items[] = array("privilege_list.php", "권한 목록");
        $items[] = array("privilege_add.php", "권한 추가");
    }
    $groups[] = array("사용자", $items);

    $items = array();
    if ($admin || $has('problem_editor') || $has('contest_creator')) $items[] = array("problem_list.php", "문제 목록");
    if ($admin || $has('problem_editor')) $items[] = array("problem_add_page.php", "문제 등록");
    if ($admin || $has('problem_importer')) {
        $items[] = array("problem_import.php", "문제 가져오기");
        $items[] = array("problem_export.php", "문제 내보내기");
    }
    $groups[] = array("문제", $items);

    $items = array();
    if ($admin || $has('contest_creator')) {
        $items[] = array("contest_list.php", "대회 목록");
        $items[] = array("contest_add.php", "대회 등록");
        $items[] = array("user_set_ip.php", "로그인 IP 지정");
        $items[] = array("team_generate.php", "팀 계정 생성");
        $items[] = array("team_generate2.php", "팀 계정 생성 2");
        $items[] = array("offline_import.php", "오프라인 대회 가져오기");
    }
    $groups[] = array("대회", $items);

    $items = array();
    $teacher = $has('teacher');
    if ($admin || $has('contest_creator') || $teacher) {
        $items[] = array("assessment_list.php", "수행평가 목록");
        $items[] = array("assessment_edit.php", "새 수행평가");
        $items[] = array("problem_add_page.php", "수행평가 문제 등록");
        if ($admin || $teacher) $items[] = array("user_group.php", "반 지정");
        if ($admin || $has('contest_creator')) $items[] = array("contest_list.php?type=assessment", "수행평가 회차 목록");
    }
    $groups[] = array("수행평가", $items);

    $items = array();
    if ($admin) {
        if ($domain == $DOMAIN) $items[] = array("settings.php", "시스템 설정");
        $items[] = array("rejudge.php", "재채점");
        $items[] = array("source_give.php", "소스 코드 전달");
        $items[] = array("../online.php", "접속자");
        $items[] = array("update_db.php", "데이터베이스 업데이트");
        $items[] = array("backup.php", "데이터베이스 백업");
        $items[] = array("ranklist_export.php", "순위 내보내기");
    }
    $groups[] = array("시스템", $items);

    $items = array();
    if ($admin) {
        $items[] = array("solution_statistics.php", "채점 통계 리포트");
        $items[] = array("problem_copy.php", "문제 복사");
        // 문제 번호를 실제로 바꾸는 기능이라 되돌리기 어렵다. 색으로 구분해 둔다.
        $items[] = array("problem_changeid.php", "문제 번호 재정렬", "danger");
    }
    $groups[] = array("고급 도구", $items);

    $items = array();
    if ($admin) {
        $items[] = array("https://github.com/zhblue/hustoj/", "HUSTOJ");
        $items[] = array("http://hustoj.com", "관리자 FAQ");
        $items[] = array("https://github.com/zhblue/freeproblemset/", "FreeProblemSet");
        $items[] = array("http://tk.hustoj.com", "셀프 문제은행");
        if (isset($OJ_REMOTE_JUDGE) && $OJ_REMOTE_JUDGE) $items[] = array("https://www.ssoier.cn/api/", "원격 계정 관리");
    }
    $groups[] = array("외부 링크", $items);

    // 권한에 따라 항목이 하나도 없는 그룹은 보여 주지 않는다.
    return array_values(array_filter($groups, function ($g) { return count($g[1]) > 0; }));
}

// 항목 주소를 링크로 바꾼다. 관리자 화면(admin/index.php) 안에서는 본문 iframe(name="main")으로 열고,
// 다른 페이지에서는 관리자 화면을 그 기능으로 연다(?main=파일&type=...).
function gs_admin_menu_link($href, $in_admin, $path_fix) {
    if (strpos($href, "http") === 0) return array($href, "_blank");
    if (strpos($href, "../") === 0) return $in_admin ? array($href, "main") : array($path_fix.substr($href, 3), "");
    if ($in_admin) return array($href, "main");
    $parts = explode("?", $href, 2);
    return array($path_fix."admin/?main=".$parts[0].(isset($parts[1]) ? "&".$parts[1] : ""), "");
}
