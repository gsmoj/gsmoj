<?php
// 수행평가 부정행위 감지 기록(assessment_event) 확인 화면.
// 학생별 요약을 먼저 보여 주고, 학생을 고르면 시간순 상세 기록을 보여 준다.
require("admin-header.php");
require_once("../include/const.inc.php");

$cid = intval($_GET['cid'] ?? 0);
if (!(isset($_SESSION[$OJ_NAME.'_'.'administrator']) || isset($_SESSION[$OJ_NAME.'_'.'contest_creator']) || isset($_SESSION[$OJ_NAME.'_'."m$cid"]))) {
    echo "<div class='admin-content-page'><div class='alert alert-danger'>권한이 없습니다.</div></div>";
    exit(1);
}
$MSG_ASSESSMENT = isset($MSG_ASSESSMENT) ? $MSG_ASSESSMENT : "수행평가";
$MSG_ASSESSMENT_EVENT = isset($MSG_ASSESSMENT_EVENT) ? $MSG_ASSESSMENT_EVENT : "감지 기록";

$contest = pdo_query("SELECT `title`,`start_time`,`end_time` FROM `contest` WHERE `contest_id`=? AND (`contest_type` & 512)<>0", $cid);
if (empty($contest)) {
    echo "<div class='admin-content-page'><div class='alert alert-danger'>".$MSG_ASSESSMENT."를 찾을 수 없습니다.</div></div>";
    exit(1);
}
$contest = $contest[0];
$event_names = array("leave" => "화면 이탈", "return" => "화면 복귀", "paste" => "붙여넣기", "burst" => "코드 급증", "copy" => "복사");
$place_names = array("problem" => "문제 화면", "editor" => "편집기");
$view_user = isset($_GET['user']) ? strval($_GET['user']) : "";
function ae_h($s) { return htmlentities(strval($s), ENT_QUOTES, 'UTF-8'); }
?>
<title><?php echo ae_h($MSG_ASSESSMENT." ".$MSG_ASSESSMENT_EVENT)?></title>

<div class="admin-content-page">
  <div class="admin-page-heading">
    <div>
      <h3><?php echo ae_h($MSG_ASSESSMENT." ".$MSG_ASSESSMENT_EVENT)?> - <?php echo $cid?>. <?php echo ae_h($contest['title'])?></h3>
      <p><?php echo ae_h($contest['start_time'])?> ~ <?php echo ae_h($contest['end_time'])?>. 화면 이탈·붙여넣기·복사·코드 급증(붙여넣기 없이 한 번에 많은 코드가 들어온 경우)을 학생별로 보여 줍니다. 기록은 참고 자료이며, 판단 전에 제출 코드와 함께 확인하세요.</p>
    </div>
    <a href="contest_list.php?type=assessment" class="btn btn-default"><?php echo ae_h($MSG_ASSESSMENT."-".$MSG_LIST)?></a>
  </div>

<?php if ($view_user === "") {
    $sql = "SELECT e.`user_id`, u.`nick`, u.`group_name`,
                SUM(e.`event_type`='leave') leave_cnt,
                SUM(IF(e.`event_type`='return', e.`amount`, 0)) away_sec,
                SUM(e.`event_type`='paste') paste_cnt,
                SUM(IF(e.`event_type`='paste', e.`amount`, 0)) paste_chars,
                SUM(e.`event_type`='burst') burst_cnt,
                SUM(e.`event_type`='copy') copy_cnt,
                MAX(e.`created_at`) last_at
            FROM `assessment_event` e LEFT JOIN `users` u ON u.`user_id`=e.`user_id`
            WHERE e.`contest_id`=?
            GROUP BY e.`user_id`, u.`nick`, u.`group_name`
            ORDER BY (leave_cnt + paste_cnt + burst_cnt + copy_cnt) DESC, e.`user_id`";
    $rows = pdo_query($sql, $cid);
?>
  <div class="admin-table-wrap">
  <table class="table">
    <tr>
      <td><?php echo $MSG_USER_ID?></td><td><?php echo $MSG_NICK?></td><td><?php echo isset($MSG_CLASS) ? $MSG_CLASS : "반"?></td>
      <td>화면 이탈</td><td>이탈 시간(초)</td><td>붙여넣기</td><td>붙여넣은 글자 수</td><td>코드 급증</td><td>복사</td><td>마지막 기록</td>
    </tr>
<?php if (empty($rows)) { ?>
    <tr><td colspan="10">아직 기록이 없습니다.</td></tr>
<?php }
    foreach ($rows as $r) {
        echo "<tr>";
        echo "<td><a href='assessment_event_list.php?cid=$cid&user=".urlencode($r['user_id'])."'>".ae_h($r['user_id'])."</a></td>";
        echo "<td>".ae_h($r['nick'])."</td><td>".ae_h($r['group_name'])."</td>";
        echo "<td>".intval($r['leave_cnt'])."</td><td>".intval($r['away_sec'])."</td>";
        echo "<td>".intval($r['paste_cnt'])."</td><td>".intval($r['paste_chars'])."</td>";
        echo "<td>".intval($r['burst_cnt'])."</td><td>".intval($r['copy_cnt'])."</td>";
        echo "<td>".ae_h($r['last_at'])."</td>";
        echo "</tr>";
    } ?>
  </table>
  </div>
<?php } else {
    $sql = "SELECT `created_at`,`num`,`place`,`event_type`,`amount`,`line_count`,`ip` FROM `assessment_event`
            WHERE `contest_id`=? AND `user_id`=? ORDER BY `event_id` LIMIT 1000";
    $rows = pdo_query($sql, $cid, $view_user);
?>
  <p><a href="assessment_event_list.php?cid=<?php echo $cid?>">&lt; 학생별 요약</a> · <b><?php echo ae_h($view_user)?></b>의 상세 기록 (최대 1000건)</p>
  <div class="admin-table-wrap">
  <table class="table">
    <tr><td>시각</td><td><?php echo $MSG_PROBLEM?></td><td>위치</td><td>종류</td><td>글자 수 / 이탈 시간(초)</td><td>줄 수</td><td>IP</td></tr>
<?php if (empty($rows)) { ?>
    <tr><td colspan="7">기록이 없습니다.</td></tr>
<?php }
    foreach ($rows as $r) {
        $num = intval($r['num']);
        echo "<tr>";
        echo "<td>".ae_h($r['created_at'])."</td>";
        echo "<td>".($num >= 0 && isset($PID[$num]) ? $PID[$num] : "-")."</td>";
        echo "<td>".ae_h($place_names[$r['place']] ?? $r['place'])."</td>";
        echo "<td>".ae_h($event_names[$r['event_type']] ?? $r['event_type'])."</td>";
        echo "<td>".($r['event_type'] === "leave" ? "-" : intval($r['amount']))."</td>";
        echo "<td>".($r['event_type'] === "leave" || $r['event_type'] === "return" ? "-" : intval($r['line_count']))."</td>";
        echo "<td>".ae_h($r['ip'])."</td>";
        echo "</tr>";
    } ?>
  </table>
  </div>
<?php } ?>
</div>
