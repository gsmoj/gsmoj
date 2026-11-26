<?php
// 채점 결과·소스 보기 화면의 "코드 수정 후 재제출" 버튼.
// 링크 규칙은 status.php의 수정 링크와 같다. 본인 제출이고, 대회 제출이면 대회가 끝나기 전에만 표시한다.

// 재제출할 수 있으면 편집 화면 주소를, 아니면 빈 문자열을 돌려준다.
function gs_resubmit_url($row) {
    global $OJ_NAME, $OJ_OLD_FASHINED, $OJ_TEMPLATE, $OJ_AUTO_SHOW_OFF;
    if (empty($row) || intval($row['problem_id']) <= 0) return '';
    if (!isset($_SESSION[$OJ_NAME . '_' . 'user_id'])) return '';
    if (strtolower($row['user_id']) != strtolower($_SESSION[$OJ_NAME . '_' . 'user_id'])) return '';

    if ((!(isset($OJ_OLD_FASHINED) && $OJ_OLD_FASHINED)) && ($OJ_TEMPLATE == "syzoj" || $OJ_TEMPLATE == "bs3") && $OJ_AUTO_SHOW_OFF)
        $edit_link = "problem.php";
    else
        $edit_link = "submitpage.php";

    $sid = intval($row['solution_id']);
    $contest_id = intval($row['contest_id']);
    if ($contest_id > 0) {
        $contest = pdo_query("select end_time from contest where contest_id=?", $contest_id);
        if (empty($contest) || strtotime($contest[0]['end_time']) <= time()) return '';
        return "$edit_link?cid=$contest_id&pid=" . intval($row['num']) . "&sid=$sid";
    }
    return "$edit_link?id=" . intval($row['problem_id']) . "&sid=$sid";
}

// 재제출 버튼 HTML. 주소가 없으면 아무것도 출력하지 않는다.
function gs_resubmit_button($url) {
    global $MSG_RESUBMIT;
    if ($url === '' || $url === null) return '';
    $label = isset($MSG_RESUBMIT) ? $MSG_RESUBMIT : '코드 수정 후 재제출';
    return "<div class='gs-resubmit-bar'><a class='ui primary button' href='" . htmlentities($url, ENT_QUOTES, 'UTF-8') . "'><i class='edit icon'></i>" . htmlentities($label, ENT_QUOTES, 'UTF-8') . "</a></div>";
}
