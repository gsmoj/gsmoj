<?php
// 수행평가 목록. 내 수행평가는 관리, 다른 교사의 수행평가는 보기·복사만 할 수 있다.
require("admin-header.php");
require_once("../include/set_get_key.php");
require_once("assessment_lib.php");
if (!asm_can_use()) {
    echo "<div class='admin-content-page'><div class='alert alert-danger'>관리자, 교사, 대회 생성자만 사용할 수 있습니다.</div></div>";
    exit(1);
}
$MSG_ASSESSMENT = isset($MSG_ASSESSMENT) ? $MSG_ASSESSMENT : "수행평가";
$me = asm_me();
$scope = isset($_GET['scope']) && $_GET['scope'] === "all" ? "all" : "mine";

$where = $scope === "mine" && !asm_is_admin() ? "a.defunct='N' and a.owner=?" : "a.defunct='N'";
$args = $scope === "mine" && !asm_is_admin() ? array($me) : array();
if ($scope === "mine" && asm_is_admin()) { $where = "a.defunct='N' and a.owner=?"; $args = array($me); }
$rows = call_user_func_array("pdo_query", array_merge(array("select a.assessment_id, a.title, a.owner, a.created_at, a.published_at,
        (select count(*) from assessment_problem p where p.assessment_id=a.assessment_id) as problems,
        (select count(*) from assessment_session s where s.assessment_id=a.assessment_id) as sessions,
        (select count(*) from assessment_session s join contest c on c.contest_id=s.contest_id where s.assessment_id=a.assessment_id and c.start_time<=now() and c.end_time>now()) as running,
        (select count(*) from assessment_session s join contest c on c.contest_id=s.contest_id where s.assessment_id=a.assessment_id and c.start_time>now()) as upcoming,
        (select group_concat(s.group_name order by s.group_name separator ', ') from assessment_session s where s.assessment_id=a.assessment_id) as groups_list
    from assessment a where $where order by a.assessment_id desc limit 200"), $args));

// 내가 만든 문제(수정 권한 pNNNN이 있는 문제)
$my_problems = pdo_query("select p.problem_id, p.title, p.defunct, p.in_date,
        (select count(distinct ap.assessment_id) from assessment_problem ap join assessment a on a.assessment_id=ap.assessment_id and a.defunct='N' where ap.problem_id=p.problem_id) as used
    from privilege pv join problem p on concat('p', p.problem_id)=pv.rightstr
    where pv.user_id=? and pv.rightstr like 'p%' order by p.problem_id desc limit 200", $me);
$getkey = isset($_SESSION[$OJ_NAME.'_'.'getkey']) ? $_SESSION[$OJ_NAME.'_'.'getkey'] : "";
?>
<title><?php echo $MSG_ASSESSMENT?> 관리</title>
<style>
  .asm-scope { display: flex; gap: 6px; margin: 0 0 14px; }
  .asm-scope a { padding: 6px 14px; border: 1px solid var(--admin-border); border-radius: 999px; background: #fff; color: var(--admin-text); font-size: 13px; text-decoration: none; }
  .asm-scope a.is-active { border-color: var(--admin-primary); background: #eff6ff; color: var(--admin-primary-dark); font-weight: 600; }
  .asm-table td, .asm-table th { vertical-align: middle !important; white-space: nowrap; }
  .asm-table td.asm-title { white-space: normal; min-width: 220px; }
  .asm-table .btn { margin: 0 2px; }
</style>
<div class="admin-content-page">
  <div class="admin-page-heading">
    <div>
      <h3><?php echo $MSG_ASSESSMENT?> 관리</h3>
      <p>문제를 묶어 <?php echo $MSG_ASSESSMENT?>를 만들고, 반마다 시간을 정해 회차를 추가합니다. 회차의 참가자는 그 반 학생이며, 진행 중 결과·감지 기록은 만든 사람과 관리자만 볼 수 있습니다.</p>
    </div>
    <div class="admin-heading-actions">
      <a class="btn btn-default" href="problem_add_page.php">문제 등록</a>
      <a class="btn btn-primary" href="assessment_edit.php">새 <?php echo $MSG_ASSESSMENT?></a>
    </div>
  </div>

  <nav class="asm-scope">
    <a href="assessment_list.php" class="<?php if ($scope === "mine") echo 'is-active'?>">내 <?php echo $MSG_ASSESSMENT?></a>
    <a href="assessment_list.php?scope=all" class="<?php if ($scope === "all") echo 'is-active'?>">전체 (다른 교사 것은 보기·복사)</a>
  </nav>

  <section class="admin-card" style="margin-bottom:16px">
    <div class="admin-table-wrap">
      <table class="table asm-table">
        <thead><tr><th>번호</th><th>제목</th><th>만든 사람</th><th>문제</th><th>반별 회차</th><th>상태</th><th>작업</th></tr></thead>
        <tbody>
        <?php if (!empty($rows)) foreach ($rows as $r) {
            $mine = asm_can_manage($r);
            if ($r['running'] > 0) $state = "<span class='admin-badge admin-badge--on'>진행 중 ".$r['running']."</span>";
            else if ($r['upcoming'] > 0) $state = "<span class='admin-badge admin-badge--info'>예정 ".$r['upcoming']."</span>";
            else if ($r['sessions'] > 0) $state = "<span class='admin-badge admin-badge--off'>종료</span>";
            else $state = "<span class='admin-badge admin-badge--warn'>회차 없음</span>";
            if ($r['published_at']) $state .= " <span class='admin-badge admin-badge--on'>공개됨</span>";
        ?>
          <tr>
            <td><?php echo intval($r['assessment_id'])?></td>
            <td class="asm-title"><a href="assessment_edit.php?id=<?php echo intval($r['assessment_id'])?>"><b><?php echo asm_h($r['title'])?></b></a></td>
            <td><?php echo asm_h($r['owner'])?></td>
            <td><?php echo intval($r['problems'])?>문제</td>
            <td><?php echo intval($r['sessions'])?>회 <span class="admin-field-help"><?php echo asm_h($r['groups_list'])?></span></td>
            <td><?php echo $state?></td>
            <td><a class="btn btn-default btn-sm" href="assessment_edit.php?id=<?php echo intval($r['assessment_id'])?>"><?php echo $mine ? "관리" : "보기·복사"?></a></td>
          </tr>
        <?php } else { ?>
          <tr><td colspan="7" class="admin-field-help" style="padding:20px;text-align:center"><?php echo $scope === "mine" ? "아직 만든 ".$MSG_ASSESSMENT."가 없습니다. 오른쪽 위 \"새 ".$MSG_ASSESSMENT."\"로 시작하세요." : $MSG_ASSESSMENT."가 없습니다."?></td></tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="admin-card">
    <div class="admin-card-title">내가 만든 문제 (새 문제는 비공개로 만들어집니다)</div>
    <div class="admin-table-wrap">
      <table class="table asm-table">
        <thead><tr><th>번호</th><th>제목</th><th>공개</th><th>사용 중인 <?php echo $MSG_ASSESSMENT?></th><th>등록일</th><th>작업</th></tr></thead>
        <tbody>
        <?php if (!empty($my_problems)) foreach ($my_problems as $p) { ?>
          <tr>
            <td><?php echo intval($p['problem_id'])?></td>
            <td class="asm-title"><a href="../problem.php?id=<?php echo intval($p['problem_id'])?>" target="_blank"><?php echo asm_h($p['title'])?></a></td>
            <td><?php echo $p['defunct'] === 'N' ? "<span class='admin-badge admin-badge--on'>공개</span>" : "<span class='admin-badge admin-badge--off'>비공개</span>"?></td>
            <td><?php echo intval($p['used'])?>개</td>
            <td><?php echo asm_h($p['in_date'])?></td>
            <td>
              <a class="btn btn-default btn-sm" href="problem_edit.php?id=<?php echo intval($p['problem_id'])?>&getkey=<?php echo asm_h($getkey)?>">수정</a>
              <a class="btn btn-default btn-sm" href="testdata.php?pid=<?php echo intval($p['problem_id'])?>">채점 데이터</a>
            </td>
          </tr>
        <?php } else { ?>
          <tr><td colspan="6" class="admin-field-help" style="padding:20px;text-align:center">아직 만든 문제가 없습니다. "문제 등록"으로 수행평가용 문제를 만드세요.</td></tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
