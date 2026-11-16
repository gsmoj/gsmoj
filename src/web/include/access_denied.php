<?php
// 다른 사람의 제출 코드·채점 결과처럼 볼 권한이 없는 기록에 들어왔을 때 안내 화면을 보여 주고 끝낸다.
// showsource.php, reinfo.php, ceinfo.php에서 권한 확인 뒤에 포함한다. 부르기 전에 $contest_id가 있으면 그 대회의 채점 현황으로 돌아간다.
$gs_notice_icon = "lock";
$gs_notice_title = isset($MSG_RECORD_DENIED) ? $MSG_RECORD_DENIED : $MSG_WARNING_ACCESS_DENIED;
$gs_notice_detail = isset($MSG_RECORD_DENIED_DETAIL) ? $MSG_RECORD_DENIED_DETAIL : "";
$gs_notice_back_url = (isset($contest_id) && intval($contest_id) > 0) ? "status.php?cid=" . intval($contest_id) : "status.php";
$gs_notice_back_label = isset($MSG_STATUS_BACK) ? $MSG_STATUS_BACK : $MSG_STATUS;
if (file_exists(dirname(__FILE__) . "/../template/" . $OJ_TEMPLATE . "/notice.php")) {
    require(dirname(__FILE__) . "/../template/" . $OJ_TEMPLATE . "/notice.php");
} else {
    $view_errors = $MSG_WARNING_ACCESS_DENIED;
    require(dirname(__FILE__) . "/../template/" . $OJ_TEMPLATE . "/error.php");
}
exit(0);
