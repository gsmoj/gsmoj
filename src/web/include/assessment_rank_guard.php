<?php
// 수행평가(contest_type 9번 비트)는 순위를 공개하지 않는다. 관리자와 그 회차의 관리자(교사, mNNNN)만 볼 수 있다.
// 순위 페이지는 순위를 여러 사용자가 함께 쓰는 캐시에 저장하므로, 캐시(cache_start.php)보다 먼저 포함해야 한다.
require_once(dirname(__FILE__)."/db_info.inc.php");
require_once(dirname(__FILE__)."/my_func.inc.php");
$gs_rank_cid = isset($_GET['cid']) ? abs(intval($_GET['cid'])) : 0;
if ($gs_rank_cid > 0 && is_assessment_contest($gs_rank_cid)
	&& !isset($_SESSION[$OJ_NAME.'_'.'administrator']) && !isset($_SESSION[$OJ_NAME.'_'.'m'.$gs_rank_cid])) {
	$view_errors = (isset($MSG_ASSESSMENT) ? $MSG_ASSESSMENT : "수행평가")."는 순위를 공개하지 않습니다.";
	require(dirname(__FILE__)."/../template/".$OJ_TEMPLATE."/error.php");
	exit(0);
}
