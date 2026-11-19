<?php
// 채점 결과(runtimeinfo)에서 테스트 데이터의 정답·출력 비교를 학생에게 숨긴다.
// 입력을 보여 주지 않는데 정답·출력을 보여 주면 테스트가 적은 문제에서 출력만 맞춰 통과할 수 있기 때문이다.
// 테스트별 결과 요약 표(파일·결과·메모리·시간)와 런타임 오류 메시지는 남겨 오답·시간 초과·런타임 오류를 구분할 수 있게 한다.

// 정답·출력 비교를 그대로 볼 수 있는 사용자: 관리자, 소스 열람 권한자, 문제 편집자, 문제 작성자(pNNNN), 그 대회·수행평가의 관리자(mNNNN)
function gs_can_see_test_output($problem_id, $contest_id) {
    global $OJ_NAME;
    foreach (array('administrator', 'source_browser', 'problem_editor') as $right)
        if (isset($_SESSION[$OJ_NAME.'_'.$right])) return true;
    if (intval($problem_id) > 0 && isset($_SESSION[$OJ_NAME.'_'.'p'.intval($problem_id)])) return true;
    if (intval($contest_id) > 0 && isset($_SESSION[$OJ_NAME.'_'.'m'.intval($contest_id)])) return true;
    return false;
}

// 채점기(judge_client)가 만드는 출력 비교 형식(OJ_FULL_DIFF 1~3, 특수 채점 메시지)을 지운다.
// 반환값: array(지운 뒤의 글, 지운 것이 있는지)
function gs_strip_test_output($text) {
    $text = str_replace("\r\n", "\n", (string)$text);
    $hidden = false;
    $patterns = array(
        // 1·2: "1.out\n--\n" 다음의 "|Expected|Yours" 또는 "Expected|Yours" 표(빈 줄까지)
        '/^[^\n]*\n--\n\n*\|?Expected\|Yours\n(?:[^\n]+\n?)*/m',
        // 3: 입력·정답·내 출력 앞부분을 통째로 붙이는 형식
        '/^========\[[^\n]*\]=========.*?^==============================\n?/ms',
        // 특수 채점·대화형 채점 메시지 코드 블록("1.out\n--\n```...```")
        '/^[^\n]*\n--\n```.*?(?:```\n?|\z)/ms',
    );
    foreach ($patterns as $p) {
        $count = 0;
        $next = preg_replace($p, '', $text, -1, $count);
        if ($next !== null && $count > 0) { $text = $next; $hidden = true; }
    }
    $text = preg_replace("/\n{3,}/", "\n\n", $text);
    return array(trim((string)$text), $hidden);
}
