<?php
// GSMOJ 티어와 순위 표시 공통 함수.
// 티어는 users.solved(맞힌 문제 수) 기준이며, 순위 계산식은 userinfo.php와 같다.
// 어느 범위에서 include되어도 동작하도록 전역 변수 대신 함수 안의 static 값을 쓴다.

// [시작 맞힌 문제 수, 이름, Semantic 아이콘, Semantic 색상, 글자색, 배경색]
function gs_tier_list() {
    static $tiers = [
        [0, '아이언', 'leaf', 'olive', '#5c6b73', '#e8edf0'],
        [20, '브론즈', 'paw', 'brown', '#8a4b1f', '#f6e6d8'],
        [30, '실버', 'bolt', 'grey', '#475569', '#e2e8f0'],
        [50, '골드', 'fire', 'yellow', '#a16207', '#fdf1c4'],
        [80, '플래티넘', 'shield alternate', 'teal', '#0d7a76', '#d7f1ef'],
        [100, '에메랄드', 'chess king', 'green', '#1e7f4a', '#dcf3e5'],
        [200, '다이아몬드', 'gem', 'blue', '#1d5fb8', '#dbeafe'],
        [300, '마스터', 'graduation cap', 'purple', '#8a2fb0', '#f3e1fa'],
        [500, '그랜드마스터', 'sun', 'red', '#b42318', '#fde2e0'],
        [800, '챌린저', 'trophy', 'orange', '#b45309', '#fdebd3'],
        [1000, '레전드', 'certificate', 'pink', '#b4235f', '#fbe0ec'],
    ];
    return $tiers;
}

// 맞힌 문제 수로 티어 정보를 만든다. 최고 티어면 next/to는 null이다.
function gs_tier($ac) {
    $tiers = gs_tier_list();
    $ac = max(0, intval($ac));
    $index = 0;
    for ($i = count($tiers) - 1; $i >= 0; $i--) {
        if ($ac >= $tiers[$i][0]) { $index = $i; break; }
    }
    $t = $tiers[$index];
    $last = $index == count($tiers) - 1;
    return [
        'index' => $index,
        'name' => $t[1],
        'icon' => $t[2],
        'semantic' => $t[3],
        'color' => $t[4],
        'bg' => $t[5],
        'from' => $t[0],
        'to' => $last ? null : $tiers[$index + 1][0],
        'next' => $last ? null : $tiers[$index + 1][1],
    ];
}

// 티어 배지 HTML. $rank가 0보다 크면 배지 안에 순위를 함께 표시한다.
function gs_tier_badge($ac, $rank = 0, $show_name = true) {
    $t = gs_tier($ac);
    $title = $t['name'] . ' · 맞힌 문제 ' . intval($ac) . '개';
    if ($rank > 0) $title .= ' · 전체 ' . intval($rank) . '위';
    $html = "<span class='gs-tier gs-tier-" . $t['index'] . "' style='color:" . $t['color'] . ";background:" . $t['bg'] . "' title='" . htmlentities($title, ENT_QUOTES, 'UTF-8') . "'>";
    $html .= "<i class='" . $t['icon'] . " icon'></i>";
    if ($show_name) $html .= "<span class='gs-tier-name'>" . $t['name'] . "</span>";
    if ($rank > 0) $html .= "<span class='gs-tier-rank'>#" . intval($rank) . "</span>";
    return $html . "</span>";
}

// 사용자별 맞힌 문제 수와 순위 캐시. $user_ids가 있으면 아직 없는 사용자만 한 번의 쿼리로 읽는다.
function gs_tier_users($user_ids = null) {
    static $users = [];
    if ($user_ids === null) return $users;
    global $OJ_RANK_HIDDEN;
    $todo = [];
    foreach ((array)$user_ids as $uid) {
        $key = strtolower(trim(strval($uid)));
        if ($key === '' || array_key_exists($key, $users) || isset($todo[$key])) continue;
        $todo[$key] = strval($uid);
    }
    if (count($todo) == 0) return $users;
    $hidden = !empty($OJ_RANK_HIDDEN) ? $OJ_RANK_HIDDEN : "''";
    foreach (array_chunk(array_values($todo), 200) as $chunk) {
        $marks = implode(',', array_fill(0, count($chunk), '?'));
        // 순위에 빠지는 계정(defunct, $OJ_RANK_HIDDEN)은 순위를 표시하지 않는다.
        $sql = "select u.user_id, u.solved, (u.defunct='N' and u.user_id not in ($hidden)) as ranked,"
             . " (select count(*) from users v where v.solved>u.solved and v.defunct='N' and v.user_id not in ($hidden))+1 as `rank`"
             . " from users u where u.user_id in ($marks)";
        $rows = pdo_query($sql, $chunk);
        if (is_array($rows)) foreach ($rows as $row) {
            $users[strtolower($row['user_id'])] = [
                'solved' => intval($row['solved']),
                'rank' => intval($row['ranked']) ? intval($row['rank']) : 0,
            ];
        }
    }
    // 없는 사용자는 다시 조회하지 않도록 표시해 둔다.
    foreach ($todo as $key => $uid) if (!array_key_exists($key, $users)) $users[$key] = null;
    return $users;
}

// 표에 나올 사용자들을 미리 한 번에 읽어 둔다.
function gs_tier_prefetch($user_ids) {
    gs_tier_users($user_ids);
}

// 사용자 아이디 옆에 붙일 배지. 없는 사용자는 빈 문자열을 돌려준다.
function gs_user_tier_badge($user_id, $show_rank = true, $show_name = true) {
    $key = strtolower(trim(strval($user_id)));
    if ($key === '') return '';
    $users = gs_tier_users([$user_id]);
    if (!isset($users[$key])) return '';
    $info = $users[$key];
    return gs_tier_badge($info['solved'], $show_rank ? $info['rank'] : 0, $show_name);
}
