<?php
// 수행평가(contest_type 9번 비트)에 권한이 없는 사용자가 주소로 직접 들어오면 제목·안내문·시간 없이 권한 없음 메시지만 보여 준다.
// 권한이 있으면 참가 세션(cNNNN)을 넣어, 지정된 반 학생이 별도 참가자 등록 없이 입장할 수 있게 한다.
// 공유 캐시를 쓰는 페이지에서는 캐시(cache_start.php)보다 먼저 포함해야 한다.
require_once(dirname(__FILE__)."/db_info.inc.php");
require_once(dirname(__FILE__)."/my_func.inc.php");
$gs_access_cid = isset($_GET['cid']) ? abs(intval($_GET['cid'])) : 0;
$gs_access_row = $gs_access_cid > 0 ? pdo_query("SELECT contest_type, user_id FROM contest WHERE contest_id=?", $gs_access_cid) : array();
if (!empty($gs_access_row) && (intval($gs_access_row[0]['contest_type']) & 512) != 0) {
	if (can_access_assessment($gs_access_cid, $gs_access_row[0]['user_id'])) {
		$_SESSION[$OJ_NAME.'_'.'c'.$gs_access_cid] = true;
		return;
	}
	// 반을 옮긴 학생의 세션에 남은 참가 권한을 지운다.
	unset($_SESSION[$OJ_NAME.'_'.'c'.$gs_access_cid]);
	$view_errors = isset($_SESSION[$OJ_NAME.'_'.'user_id']) ? $MSG_PRIVATE_WARNING : "<a href=loginpage.php>$MSG_Login</a>";
	require(dirname(__FILE__)."/../template/".$OJ_TEMPLATE."/error.php");
	exit(0);
}
