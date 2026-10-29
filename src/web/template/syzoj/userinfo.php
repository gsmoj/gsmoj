<?php $show_title="$MSG_USERINFO - $OJ_NAME"; ?>
<?php include("template/$OJ_TEMPLATE/header.php");?>
<style>
.padding.up-page {
    border-radius: 0;
    overflow: visible;
    box-shadow: none;
    background: transparent;
    transition: none;
}
.padding.up-page::before,
.padding.up-page::after {
    display: none;
}
.up-wrap {
    max-width: 1100px;
    margin: 0 auto;
    padding: 24px 0 8px;
    color: #1b1c1d;
}
.up-header {
    display: flex;
    align-items: center;
    gap: 24px;
    padding-bottom: 28px;
    border-bottom: 1px solid #e9ebf1;
}
.up-avatar {
    flex: 0 0 auto;
    width: 88px;
    height: 88px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 22px;
    font-size: 40px;
    font-weight: 800;
}
.up-info {
    flex: 1 1 auto;
    min-width: 0;
}
.up-nick-row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}
.up-nick {
    margin: 0;
    font-size: 32px;
    font-weight: 800;
    line-height: 1.2;
}
.up-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px 4px 10px;
    border-radius: 999px;
    font-size: 14px;
    font-weight: 800;
}
.up-badge .icon {
    margin: 0;
    width: auto;
}
.up-school {
    margin-top: 8px;
    font-size: 16px;
    font-weight: 700;
    color: #2b2f3a;
}
.up-school .icon {
    margin-right: 6px;
}
.up-sub {
    margin-top: 6px;
    font-size: 14px;
    font-weight: 500;
    color: #8a90a0;
}
.up-sub a {
    color: inherit;
}
.up-sub .up-dot {
    margin: 0 8px;
}
.up-edit {
    flex: 0 0 auto;
    align-self: flex-start;
    padding: 12px 20px;
    border: 1px solid #e3e5ec;
    border-radius: 12px;
    background: #fff;
    font-size: 15px;
    font-weight: 700;
    color: #1b1c1d;
    box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
}
.up-edit:hover {
    background: #f6f7fb;
    color: #1b1c1d;
}
.up-tiers {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 32px;
    padding: 22px 0;
    border-bottom: 1px solid #e9ebf1;
}
.up-tier-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}
.up-tier-item {
    position: relative;
    width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
}
.up-tier-item .icon {
    margin: 0;
    width: auto;
    height: auto;
    font-size: 22px;
    line-height: 1;
}
.up-tier-item.is-off {
    background: #f3f4f8;
}
.up-tier-item.is-off .icon {
    color: #c9ccd6 !important;
}
.up-tier-item.is-now::after {
    content: "";
    position: absolute;
    left: 50%;
    bottom: -9px;
    width: 18px;
    height: 3px;
    margin-left: -9px;
    border-radius: 2px;
    background: #1b1c1d;
}
.up-progress {
    flex: 0 0 380px;
    max-width: 100%;
}
.up-progress-top {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 12px;
}
.up-progress-name {
    font-size: 18px;
    font-weight: 800;
}
.up-progress-next {
    font-size: 14px;
    font-weight: 500;
    color: #6b7280;
}
.up-progress-next b {
    color: #1b1c1d;
    font-weight: 800;
}
.up-bar {
    height: 8px;
    margin-top: 12px;
    border-radius: 4px;
    background: #e8ebf2;
    overflow: hidden;
}
.up-bar > span {
    display: block;
    height: 100%;
    border-radius: 4px;
    background: #63c69b;
}
.up-stats {
    display: flex;
    border-bottom: 1px solid #e9ebf1;
}
.up-stat {
    flex: 1 1 0;
    display: flex;
    align-items: center;
    gap: 18px;
    padding: 26px 0 26px 32px;
    border-left: 1px solid #e9ebf1;
}
.up-stat:first-child {
    padding-left: 0;
    border-left: none;
}
.up-stat-icon {
    flex: 0 0 auto;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 16px;
}
.up-stat-icon .icon {
    margin: 0;
    width: auto;
    height: auto;
    font-size: 26px;
    line-height: 1;
}
.up-stat-label {
    font-size: 14px;
    font-weight: 600;
    color: #8a90a0;
}
.up-stat-value {
    font-size: 36px;
    font-weight: 800;
    line-height: 1.15;
}
.up-stat-value small {
    margin-left: 4px;
    font-size: 15px;
    font-weight: 600;
    color: #8a90a0;
}
.up-stat-note {
    margin-top: 2px;
    font-size: 12px;
    font-weight: 600;
    color: #8a90a0;
}
.up-section-title {
    margin: 44px 0 18px;
    font-size: 22px;
    font-weight: 800;
}
.up-tabs {
    display: flex;
    gap: 6px;
    margin-bottom: 8px;
}
.up-tab {
    padding: 10px 16px;
    border: none;
    border-radius: 10px;
    background: transparent;
    font-size: 15px;
    font-weight: 700;
    color: #6b7280;
    cursor: pointer;
}
.up-tab.is-active {
    background: #111827;
    color: #fff;
}
.up-table-wrap {
    overflow-x: auto;
}
.up-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 15px;
}
.up-table th {
    padding: 18px 14px;
    border-bottom: 2px solid #1b1c1d;
    font-size: 14px;
    font-weight: 800;
    text-align: left;
    white-space: nowrap;
}
.up-table td {
    padding: 16px 14px;
    border-bottom: 1px solid #eef0f5;
    font-weight: 500;
    white-space: nowrap;
}
.up-table .up-num {
    text-align: right;
}
.up-table .up-sid {
    color: #8a90a0;
}
.up-table .up-pid {
    margin-right: 8px;
    font-weight: 800;
    color: #4f5bd5;
}
.up-table .up-when {
    color: #8a90a0;
}
.up-pill {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 14px;
    font-weight: 800;
}
.up-pill.ok {
    background: #e2f5e9;
    color: #1f7a45;
}
.up-pill.bad {
    background: #fde7e7;
    color: #c0362c;
}
.up-pill.warn {
    background: #fdf0dc;
    color: #b7791f;
}
.up-pill.wait {
    background: #e8ecf8;
    color: #4f5bd5;
}
.up-lang {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 8px;
    background: #f3f4f8;
    font-size: 14px;
    font-weight: 600;
    color: #4b5563;
}
.up-empty {
    padding: 40px 0;
    text-align: center;
    color: #8a90a0;
    font-weight: 600;
}
.up-actions {
    flex: 0 0 auto;
    align-self: flex-start;
    display: flex;
    gap: 8px;
}
.up-actions .up-edit {
    align-self: auto;
}
.up-edit .icon {
    margin: 0 6px 0 0;
}
.up-edit.is-primary {
    border-color: #111827;
    background: #111827;
    color: #fff;
}
.up-edit.is-primary:hover {
    background: #1f2937;
    color: #fff;
}
.up-section-head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 12px;
    margin: 44px 0 18px;
}
.up-section-head .up-section-title {
    margin: 0;
}
.up-section-count {
    margin-left: 8px;
    font-size: 16px;
    font-weight: 700;
    color: #8a90a0;
}
.up-section-link {
    font-size: 14px;
    font-weight: 700;
    color: #4f5bd5;
}
.up-results {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.up-result-row {
    display: grid;
    grid-template-columns: 160px 1fr 64px;
    align-items: center;
    gap: 16px;
}
.up-result-name {
    font-size: 15px;
    font-weight: 700;
}
.up-result-bar {
    height: 10px;
    border-radius: 5px;
    background: #f1f3f8;
    overflow: hidden;
}
.up-result-bar > span {
    display: block;
    height: 100%;
    border-radius: 5px;
}
.up-result-num {
    font-size: 15px;
    font-weight: 800;
    text-align: right;
}
.up-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.up-chip {
    display: inline-block;
    min-width: 56px;
    padding: 7px 12px;
    border-radius: 10px;
    background: #f3f4f8;
    font-size: 14px;
    font-weight: 700;
    text-align: center;
    color: #4b5563;
}
.up-chip:hover {
    background: #e8ebf2;
    color: #1b1c1d;
}
.up-chip.ok {
    background: #e2f5e9;
    color: #1f7a45;
}
.up-chip.ok:hover {
    background: #cdeedb;
}
.up-chip.bad {
    background: #fde7e7;
    color: #c0362c;
}
.up-chip.bad:hover {
    background: #fbd3d3;
}
.up-plist {
    padding: 18px 0;
    border-bottom: 1px solid #eef0f5;
}
.up-plist:first-child {
    padding-top: 0;
}
.up-plist-name {
    margin-bottom: 12px;
    font-size: 16px;
    font-weight: 800;
}
.up-table tfoot td {
    border-bottom: none;
    font-weight: 800;
}
@media (max-width: 767px) {
    .up-header {
        flex-wrap: wrap;
        gap: 16px;
    }
    .up-actions {
        order: 3;
        width: 100%;
    }
    .up-actions .up-edit {
        flex: 1 1 0;
        text-align: center;
    }
    .up-result-row {
        grid-template-columns: 110px 1fr 48px;
        gap: 10px;
    }
    .up-tiers {
        flex-direction: column;
        align-items: stretch;
        gap: 28px;
    }
    .up-progress {
        flex-basis: auto;
    }
    .up-stats {
        flex-direction: column;
    }
    .up-stat,
    .up-stat:first-child {
        padding: 18px 0;
        border-left: none;
        border-top: 1px solid #e9ebf1;
    }
    .up-stat:first-child {
        border-top: none;
    }
}
</style>
<?php
    $calsed = '비단잉어'; // 锦鲤 -> 행운의 비단잉어 (예외 최고 등급)
    $calledid = -1;
    $acneed = [10,20,30,50,80,100,200,300,500,800,1000];

    // 💡 [티어 번역] 한국 정서에 맞춘 알고리즘 티어 명칭 맵핑
    $accall = ["뉴비", "초보", "중수", "고수", "치터킬러", "킹", "마스터", "그랜드마스터", "신", "올림피아드 갓"];
    for ($i = count($accall);$i > 0; $i--) {
        if ($AC < $acneed[$i]) {$calsed = $accall[$i - 1];$calledid=$i-1;}
    }

    // 아이콘, Semantic 색상, 글자색, 배경색
    $tier_icons = [
        ['leaf', 'olive', '#5c7a1a', '#eef5d8'],
        ['paw', 'green', '#1e7f4a', '#dcf3e5'],
        ['bolt', 'teal', '#0d7a76', '#d7f1ef'],
        ['fire', 'orange', '#b45309', '#fdebd3'],
        ['shield alternate', 'blue', '#1d5fb8', '#dbeafe'],
        ['chess king', 'violet', '#6d3fc0', '#ece4fb'],
        ['graduation cap', 'purple', '#8a2fb0', '#f3e1fa'],
        ['gem', 'pink', '#b4235f', '#fbe0ec'],
        ['sun', 'yellow', '#a16207', '#fdf1c4'],
        ['trophy', 'red', '#b42318', '#fde2e0'],
        ['certificate', 'yellow', '#a16207', '#fdf1c4'],
    ];
    $tier_names = array_merge($accall, ['비단잉어']);
    $tier_now = $calledid < 0 ? count($accall) : $calledid;
    $tier_cur = $tier_icons[$tier_now];
    if ($calledid < 0) {
        $tier_pct = 100;
    } else {
        $tier_from = $calledid == 0 ? 0 : $acneed[$calledid];
        $tier_to = $acneed[$calledid + 1];
        $tier_pct = max(0, min(100, round(($AC - $tier_from) / ($tier_to - $tier_from) * 100)));
        $tier_next = $tier_names[$calledid + 1];
    }

    date_default_timezone_set('Asia/Seoul'); // 💡 타임존을 PRC(중국)에서 Asia/Seoul(한국)로 변경
    $sql = "select solution_id,problem_id,result,language,time,memory,in_date,(select title from problem p where p.problem_id=solution.problem_id) as title from solution where user_id=? and problem_id>0 $not_in_noip order by solution_id desc limit 15";
    $recent = pdo_query($sql, $user);
    $up_ago = function ($t) {
        $diff = time() - strtotime($t);
        if ($diff < 60) return '방금 전';
        if ($diff < 3600) return floor($diff / 60) . '분 전';
        if ($diff < 86400) return floor($diff / 3600) . '시간 전';
        if ($diff < 172800) return '어제';
        if ($diff < 2592000) return floor($diff / 86400) . '일 전';
        return date('Y-m-d', strtotime($t));
    };

    $stat_total = 0;
    $stat_ac = 0;
    foreach ($view_userstat as $row) {
        $stat_total += $row[1];
        if ($row[0] == 4) $stat_ac = $row[1];
    }
    $stat_max = 0;
    foreach ($view_userstat as $row) $stat_max = max($stat_max, $row[1]);
    $stat_rate = $stat_total ? round($stat_ac / $stat_total * 100, 1) : 0;

    $up_initial = '';
    if (preg_match('/^./u', trim($nick), $m)) $up_initial = $m[0];
    elseif (preg_match('/^./u', $user, $m)) $up_initial = strtoupper($m[0]);
    $is_mine = isset($_SESSION[$OJ_NAME.'_'.'user_id']) && $_SESSION[$OJ_NAME.'_'.'user_id'] == $user;
?>

<div class="padding up-page">
<div class="up-wrap">

    <div class="up-header">
        <div class="up-avatar" style="color:<?php echo $tier_cur[2] ?>;background:<?php echo $tier_cur[3] ?>"><?php echo htmlspecialchars($up_initial) ?></div>
        <div class="up-info">
            <div class="up-nick-row">
                <h1 class="up-nick"><?php echo $nick?></h1>
                <span class="up-badge" style="color:<?php echo $tier_cur[2] ?>;background:<?php echo $tier_cur[3] ?>"><i class="<?php echo $tier_cur[1] . ' ' . $tier_cur[0] ?> icon"></i><?php echo $calsed ?></span>
            </div>
            <?php if ($school != "") { ?>
            <div class="up-school"><i class="blue graduation cap icon"></i><?php echo $school?></div>
            <?php } ?>
            <div class="up-sub">
                @<?php echo htmlspecialchars($user) ?>
                <?php if ($group_name != "") { ?><span class="up-dot">·</span><?php echo $group_name?><?php } ?>
                <?php if ($email != "") { ?><span class="up-dot">·</span><a href="mailto:<?php echo htmlspecialchars($email) ?>"><?php echo $email?></a><?php } ?>
            </div>
        </div>
        <div class="up-actions">
            <a class="up-edit is-primary" href="status.php?user_id=<?php echo urlencode($user) ?>"><i class="search icon"></i>제출 기록</a>
            <?php if ($is_mine) { ?>
            <a class="up-edit" href="modifypage.php">정보 수정</a>
            <?php } ?>
        </div>
    </div>

    <div class="up-tiers">
        <div class="up-tier-list">
            <?php foreach ($tier_icons as $ti => $tier) { ?>
            <span class="up-tier-item <?php echo $ti < $tier_now ? 'is-done' : ($ti == $tier_now ? 'is-now' : 'is-off') ?>" style="<?php echo $ti <= $tier_now ? 'background:' . $tier[3] : '' ?>" title="<?php echo $tier_names[$ti] ?>"><i class="<?php echo $tier[1] . ' ' . $tier[0] ?> icon"></i></span>
            <?php } ?>
        </div>
        <div class="up-progress">
            <div class="up-progress-top">
                <span class="up-progress-name"><?php echo $calsed ?></span>
                <?php if ($calledid >= 0) { ?>
                <span class="up-progress-next"><?php echo $tier_next ?>까지 <b><?php echo $tier_pct ?>%</b> · AC <?php echo $acneed[$calledid+1]-$AC ?>개 남음</span>
                <?php } else { ?>
                <span class="up-progress-next">최고 등급입니다</span>
                <?php } ?>
            </div>
            <div class="up-bar"><span style="width:<?php echo $tier_pct ?>%"></span></div>
        </div>
    </div>

    <div class="up-stats">
        <div class="up-stat">
            <div class="up-stat-icon" style="background:#e2f5e9"><i class="green check circle icon"></i></div>
            <div>
                <div class="up-stat-label">해결 문제</div>
                <div class="up-stat-value" style="color:#1f7a45"><?php echo $AC ?><small>문제</small></div>
            </div>
        </div>
        <div class="up-stat">
            <div class="up-stat-icon" style="background:#fdf1c4"><i class="yellow trophy icon"></i></div>
            <div>
                <div class="up-stat-label">문제풀이 순위</div>
                <div class="up-stat-value" style="color:#a16207">#<?php echo $Rank ?><small>위</small><?php if ($starred) { ?> <i class="yellow star icon" title='동일한 계정 이름으로 HUSTOJ GitHub 프로젝트에 Star를 누르면 불이 들어옵니다.'></i><?php } ?></div>
            </div>
        </div>
        <div class="up-stat">
            <div class="up-stat-icon" style="background:#e8ecf8"><i class="blue chart line icon"></i></div>
            <div>
                <div class="up-stat-label">정답률</div>
                <div class="up-stat-value" style="color:#4f5bd5"><?php echo $stat_rate ?><small>%</small></div>
                <div class="up-stat-note">총 <?php echo number_format($stat_total) ?>번 제출</div>
            </div>
        </div>
    </div>

    <h2 class="up-section-title">최근 제출</h2>
    <div class="up-tabs">
        <button type="button" class="up-tab is-active" data-kind="all">전체</button>
        <button type="button" class="up-tab" data-kind="ok">맞은 제출</button>
        <button type="button" class="up-tab" data-kind="bad">틀린 제출</button>
    </div>
    <div class="up-table-wrap">
        <table class="up-table">
            <thead>
                <tr>
                    <th>제출 번호</th>
                    <th>문제</th>
                    <th>결과</th>
                    <th>언어</th>
                    <th class="up-num">시간</th>
                    <th class="up-num">메모리</th>
                    <th class="up-num">제출 시각</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $sub) {
                    $res = intval($sub['result']);
                    if ($res == 4) { $kind = 'ok'; $pill = 'ok'; }
                    elseif ($res < 4 || $res >= 12) { $kind = 'wait'; $pill = 'wait'; }
                    elseif ($res >= 7 && $res <= 9) { $kind = 'bad'; $pill = 'warn'; }
                    else { $kind = 'bad'; $pill = 'bad'; }
                    $lang = isset($language_name[$sub['language']]) ? $language_name[$sub['language']] : '';
                ?>
                <tr data-kind="<?php echo $kind ?>">
                    <td class="up-sid"><?php echo intval($sub['solution_id']) ?></td>
                    <td><a class="up-pid" href="problem.php?id=<?php echo intval($sub['problem_id']) ?>"><?php echo intval($sub['problem_id']) ?></a><?php echo htmlspecialchars((string)$sub['title']) ?></td>
                    <td><span class="up-pill <?php echo $pill ?>"><?php echo isset($jresult[$res]) ? $jresult[$res] : $res ?></span></td>
                    <td><span class="up-lang"><?php echo $lang ?></span></td>
                    <td class="up-num"><?php echo $res == 4 || $sub['time'] > 0 ? number_format($sub['time']) . ' ms' : '-' ?></td>
                    <td class="up-num"><?php echo $res == 4 || $sub['memory'] > 0 ? number_format($sub['memory']) . ' KB' : '-' ?></td>
                    <td class="up-num up-when"><?php echo $up_ago($sub['in_date']) ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
        <div class="up-empty" id="up_empty" style="<?php echo count($recent) ? 'display:none' : '' ?>">제출 내역이 없습니다.</div>
    </div>

    <?php
    $sql="select c.contest_id,c.title,c.start_time,rt.tried,rt.ac  from contest c
            inner join (select contest_id,count(distinct(problem_id)) as tried, count(distinct(if(result=4,problem_id,null))) as ac from solution
                where user_id=? and contest_id>0
                group by contest_id order by contest_id) rt on c.contest_id=rt.contest_id and not (c.title like '%$OJ_NOIP_KEYWORD%' or  ((c.contest_type & 20) >0 and end_time>now() )) ;";
    $contests=pdo_query($sql,$user);
    $total=$total_ac=0;
    foreach($contests as $row){
        $total+=$row['tried'];
        $total_ac+=$row['ac'];
    }
    ?>
    <div class="up-section-head">
        <h2 class="up-section-title"><?php echo $MSG_CONTEST?><span class="up-section-count"><?php echo count($contests) ?></span></h2>
    </div>
    <?php if (count($contests)) { ?>
    <div class="up-table-wrap">
        <table class="up-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?php echo $MSG_TITLE ?></th>
                    <th><?php echo $MSG_START_TIME?></th>
                    <th class="up-num">시도한 문제</th>
                    <th class="up-num">맞은 문제</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($contests as $ci => $row){ ?>
                <tr>
                    <td class="up-sid"><?php echo $ci + 1 ?></td>
                    <td><a href="contestrank.php?cid=<?php echo intval($row['contest_id']) ?>#<?php echo htmlentities($user) ?>" target="_blank"><?php echo $row['title'] ?></a></td>
                    <td class="up-when"><?php echo $row['start_time'] ?></td>
                    <td class="up-num"><?php echo $row['tried'] ?></td>
                    <td class="up-num"><?php echo $row['ac'] ?></td>
                </tr>
                <?php } ?>
            </tbody>
            <tfoot>
                <tr>
                    <td></td>
                    <td>합계</td>
                    <td></td>
                    <td class="up-num"><?php echo $total ?></td>
                    <td class="up-num"><?php echo $total_ac ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php } else { ?>
    <div class="up-empty">참가한 대회가 없습니다.</div>
    <?php } ?>

    <?php
    $result_colors = [4 => '#3fb672', 5 => '#f0a43a', 6 => '#e5534b', 7 => '#f0a43a', 8 => '#d97706', 9 => '#b45309', 10 => '#8b5cf6', 11 => '#64748b'];
    ?>
    <div class="up-section-head">
        <h2 class="up-section-title">채점 결과</h2>
    </div>
    <?php if ($stat_total) { ?>
    <div class="up-results">
        <?php foreach ($view_userstat as $row) {
            $color = isset($result_colors[$row[0]]) ? $result_colors[$row[0]] : '#94a3b8';
        ?>
        <div class="up-result-row">
            <span class="up-result-name"><?php echo $jresult[$row[0]] ?></span>
            <div class="up-result-bar"><span style="width:<?php echo round($row[1] / $stat_max * 100, 1) ?>%;background:<?php echo $color ?>"></span></div>
            <span class="up-result-num"><?php echo number_format($row[1]) ?></span>
        </div>
        <?php } ?>
    </div>
    <?php } else { ?>
    <div class="up-empty">채점 기록이 없습니다.</div>
    <?php } ?>

    <?php
    $ac=array();
    $ac_list=array();
    $sql = "select `problem_id`,count(1) from solution where `user_id`=? and result=4 and problem_id != 0 $not_in_noip group by `problem_id` ORDER BY `problem_id` ASC";
    if ($ret = mysql_query_cache($sql, $user)) {
        foreach ($ret as $row){
            $ac_list[] = $row[0];
            array_push($ac,$row[0]);
        }
    }
    ?>
    <div class="up-section-head">
        <h2 class="up-section-title">맞은 문제<span class="up-section-count"><?php echo count($ac_list) ?></span></h2>
    </div>
    <?php if (count($ac_list)) { ?>
    <div class="up-chips">
        <?php foreach ($ac_list as $pid) { ?>
        <a class="up-chip ok" href="problem.php?id=<?php echo intval($pid) ?>"><?php echo intval($pid) ?></a>
        <?php } ?>
    </div>
    <?php } else { ?>
    <div class="up-empty">아직 맞은 문제가 없습니다.</div>
    <?php } ?>

    <?php
    $wrong_list=array();
    $sql = "select `sol`.`problem_id`, count(1) from solution sol where `sol`.`user_id`=? and `sol`.`result`!=4 and sol.problem_id != 0  $not_in_noip group by `sol`.`problem_id` ORDER BY `sol`.`problem_id` ASC";
    if ($result = mysql_query_cache($sql, $user)) {
        foreach ($result as $row)
            if(!in_array($row[0],$ac)) $wrong_list[] = $row[0];
    }
    ?>
    <div class="up-section-head">
        <h2 class="up-section-title">시도했지만 맞지 못한 문제<span class="up-section-count"><?php echo count($wrong_list) ?></span></h2>
    </div>
    <?php if (count($wrong_list)) { ?>
    <div class="up-chips">
        <?php foreach ($wrong_list as $pid) { ?>
        <a class="up-chip bad" href="problem.php?id=<?php echo intval($pid) ?>"><?php echo intval($pid) ?></a>
        <?php } ?>
    </div>
    <?php } else { ?>
    <div class="up-empty">틀린 채로 남은 문제가 없습니다.</div>
    <?php } ?>

    <?php if (count($plista)) { ?>
    <div class="up-section-head">
        <h2 class="up-section-title">추천 문제집</h2>
    </div>
    <?php foreach($plista as $plist){ ?>
    <div class="up-plist">
        <div class="up-plist-name"><?php echo $plist["name"] ?></div>
        <div class="up-chips">
            <?php foreach(explode(",",$plist['list']) as $pid){ ?>
            <a class="up-chip <?php echo in_array($pid,$ac) ? 'ok' : '' ?>" href="problem.php?id=<?php echo intval($pid) ?>"><?php echo isset($bible[$pid]) ? $bible[$pid] : intval($pid) ?></a>
            <?php } ?>
        </div>
    </div>
    <?php } ?>
    <?php } ?>

    <?php if(isset($_SESSION[$OJ_NAME.'_'.'administrator'])){ ?>
    <div class="up-section-head">
        <h2 class="up-section-title">최근 로그인 기록</h2>
    </div>
    <div class="up-table-wrap">
        <table class="up-table">
            <thead>
                <tr>
                    <th>사용자 ID</th>
                    <th>상태</th>
                    <th>접속 IP</th>
                    <th class="up-num">접속 시간</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($view_userinfo as $row){ ?>
                <tr>
                    <td><?php echo $row[0] ?></td>
                    <td><?php echo $row[1] ?></td>
                    <td class="up-sid"><?php echo $row[2] ?></td>
                    <td class="up-num up-when"><?php echo $row[3] ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php } ?>

</div>
</div>

<script>
    (function() {
        var tabs = document.querySelectorAll('.up-tab');
        var rows = document.querySelectorAll('.up-table tbody tr');
        var empty = document.getElementById('up_empty');
        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                var kind = tab.getAttribute('data-kind');
                var shown = 0;
                tabs.forEach(function(t) { t.classList.toggle('is-active', t === tab); });
                rows.forEach(function(r) {
                    var on = kind === 'all' || r.getAttribute('data-kind') === kind;
                    r.style.display = on ? '' : 'none';
                    if (on) shown++;
                });
                empty.style.display = shown ? 'none' : '';
            });
        });
    })();
</script>

<?php include("template/$OJ_TEMPLATE/footer.php");?>