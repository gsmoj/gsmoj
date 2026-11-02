<?php
/**
 * 문제 목록 페이지 처리 스크립트
 *
 * 온라인 저지의 문제 목록 페이지를 처리한다. 쪽 나누기, 검색, 권한 제어를 포함한다.
 * 문제 목록을 보여 주고 사용자가 본 쪽을 기억하며 문제 검색과 필터를 처리한다.
 */

/**
 * 전역 설정 변수
 */
$OJ_CACHE_SHARE = false;  // 캐시 공유 여부
$cache_time = 10;         // 캐시 시간

/**
 * 필요한 파일을 불러온다.
 * 데이터베이스 연결, 상수 정의, 캐시, cURL, Memcache, 언어 설정 모듈을 포함한다.
 */
require_once('./include/db_info.inc.php');
require_once('./include/const.inc.php');
require_once('./include/cache_start.php');
require_once('./include/curl.php');
require_once('./include/memcache.php');
require_once('./include/setlang.php');
require_once("./include/set_get_key.php");

/**
 * 페이지 제목 설정
 */
$view_title = "Problem Set";

/**
 * 사용자가 본 쪽 기억 기능
 *
 * GET 인자나 세션을 바탕으로 현재 쪽 위치를 기록한다.
 * 관리자와 일반 사용자는 쪽을 기억하는 방식이 다르다.
 */
// 목록 필터(풀이 상태·분류·정렬) 입력값. 허용된 값만 사용한다.
$view_filter = isset($_GET['filter']) ? $_GET['filter'] : "";
if (!isset($_SESSION[$OJ_NAME . '_' . 'user_id']) || !in_array($view_filter, array("unsolved", "solved", "tried"), true))
    $view_filter = "";
$view_category = (isset($_GET['category']) && is_string($_GET['category'])) ? trim($_GET['category']) : "";
$sort_sqls = array(
    "id" => " order by problem_id ",
    "rate" => " order by (accepted/GREATEST(submit,1)) desc, problem_id ",
    "accepted" => " order by accepted desc, problem_id ",
    "submit" => " order by submit desc, problem_id "
);
$view_sort = (isset($_GET['sort']) && is_string($_GET['sort']) && isset($sort_sqls[$_GET['sort']])) ? $_GET['sort'] : "id";
$has_filter = isset($_GET['search']) || $view_filter != "" || $view_category != "";

$page = "1";
if (isset($_GET['page'])) {
    $page = intval($_GET['page']);
    if (isset($_SESSION[$OJ_NAME . '_' . 'user_id']) && !$has_filter) {
        $sql = "update users set volume=? where user_id=?";
        pdo_query($sql, $page, $_SESSION[$OJ_NAME . '_' . 'user_id']);
    }
} else {
    if (isset($_SESSION[$OJ_NAME . '_' . 'user_id']) && !$has_filter) {
        $sql = "select volume from users where user_id=?";
        $result = pdo_query($sql, $_SESSION[$OJ_NAME . '_' . 'user_id']);
        $row = $result[0];
        $page = intval($row[0]);
    } else {
        $page = 1;
    }

    if (!is_numeric($page) || $page <= 0)
        $page = '1';
}
//end of remember page

/**
 * 쪽 나누기 설정
 *
 * 요청 인자(검색, 목록, 일반 탐색)에 따라 필터 조건과 정렬 방식을 정한다.
 * 제목·출처 검색, 지정한 문제 목록 표시, 일반 쪽 탐색을 지원한다.
 */
$page_cnt = 50;  //50 problems per page

$postfix = "";
$filter_sql = "";
$limit_sql = "";
$order_by = $sort_sqls[$view_sort];
$filter_params = array();
if (isset($_GET['search']) && trim($_GET['search']) != "") {
    $search = "%" . ($_GET['search']) . "%";
    $filter_sql = " ( title like ? or source like ?";
    $filter_params = array($search, $search);
    if (ctype_digit(trim($_GET['search']))) {  // 숫자만 입력하면 문제 번호로도 찾는다.
        $filter_sql .= " or problem_id = ?";
        $filter_params[] = intval(trim($_GET['search']));
    }
    $filter_sql .= ")";
    $limit_sql = " LIMIT " . ($page - 1) * $page_cnt . "," . $page_cnt;
    $postfix = "&search=" . urlencode($_GET['search']);
} else if (isset($_GET['list']) && trim($_GET['list'] != "")) {
    $plist = explode(",", $_GET['list']);
    $pids = "";
    foreach ($plist as $pid) {
        $pid = intval($pid);
        if ($pids == "")
            $pids .= $pid;
        else
            $pids .= ",$pid";
    }
    $filter_sql = " problem_id in ($pids) ";
    $order_by = "order by FIELD(problem_id,$pids)"; // 난이도순으로 보려면 order by accepted desc로 바꾼다.
    //$limit_sql = " LIMIT ".($page-1)*$page_cnt.",".$page_cnt;
    $limit_sql = "";  // list는 쪽을 나누지 않는다.
} else {
    $filter_sql = " problem_id > 0";
    $limit_sql = " LIMIT " . ($page - 1) * $page_cnt . "," . $page_cnt;
}

/**
 * 사용자 제출 상태 집계
 *
 * 로그인한 사용자의 제출 기록에서 제출한 문제와 맞힌 문제를 집계한다.
 * 문제 목록에 사용자의 풀이 상태를 표시하는 데 쓴다.
 */
$sub_arr = array();
$acc_arr = array();
if (isset($_SESSION[$OJ_NAME . '_' . 'user_id'])) {
    $sql = "select problem_id, MIN(result) AS result FROM solution WHERE user_id=? and result>=4 GROUP BY problem_id ";
    $result = pdo_query($sql, $_SESSION[$OJ_NAME . '_' . 'user_id']);
    foreach ($result as $row) {
        $sub_arr[$row['problem_id']] = true;
        if ($row['result'] == 4) $acc_arr[$row['problem_id']] = true;
    }
}

/**
 * 풀이 상태·분류 필터
 *
 * 문제 번호는 위 채점 결과에서 얻은 정수만 사용하고, 분류는 파라미터 바인딩으로 전달한다.
 */
if ($view_filter != "") {
    $acc_ids = implode(",", array_map('intval', array_keys($acc_arr)));
    $tried_ids = implode(",", array_map('intval', array_keys(array_diff_key($sub_arr, $acc_arr))));
    if ($view_filter == "solved")
        $filter_sql .= $acc_ids == "" ? " and 1=0 " : " and problem_id in ($acc_ids) ";
    else if ($view_filter == "tried")
        $filter_sql .= $tried_ids == "" ? " and 1=0 " : " and problem_id in ($tried_ids) ";
    else if ($acc_ids != "")
        $filter_sql .= " and problem_id not in ($acc_ids) ";
    $postfix .= "&filter=" . $view_filter;
}
if ($view_category != "") {
    $filter_sql .= " and source like ? ";
    $filter_params[] = "%" . $view_category . "%";
    $postfix .= "&category=" . urlencode($view_category);
}
if ($view_sort != "id")
    $postfix .= "&sort=" . $view_sort;

/**
 * 문제 목록 접근 권한 제어
 *
 * 사용자 권한(관리자, 일반 사용자, 자유 연습 모드)에 따라 문제 필터 조건을 정한다.
 * 관리자는 모든 문제를 볼 수 있고, 일반 사용자는 대회 기간의 문제를 볼 수 없다.
 */
//if($OJ_SAE) $first=1;
if (isset($_SESSION[$OJ_NAME . '_' . 'administrator'])) {  // 수행평가 전용 문제를 뺀 모든 문제
    $filter_sql .= " and defunct<>'A' ";
    //$limit = $limit_sql;
} else if ($OJ_FREE_PRACTICE) {  // open free practice without limit of contest using
    $filter_sql .= " and defunct='N' ";
} else {  //page problems (not include in contests period)
    $now = date('Y-m-d H:i', time());
    $filter_sql .= " and `defunct`='N' AND `problem_id` NOT IN (
					SELECT  `problem_id`
						FROM contest c
						INNER JOIN  `contest_problem` cp ON c.`contest_id` = cp.`contest_id` " .
        " AND (c.`defunct` = 'N' AND '$now'<c.`end_time`)" .    // option style show all non-running contest
        //"and (c.`end_time` >  '$now'  OR c.private =1)" . // original style , hidden all private contest problems
        ") ";
}
// End Page Setting

/**
 * 데이터베이스 조회
 *
 * 검색 조건에 따라 다른 방식으로 문제 목록을 조회한다.
 * 전체 쪽 수를 세고 현재 쪽의 문제를 가져온다.
 */
pdo_query("SET sort_buffer_size = 1024*1024");   // Out of sort memory, consider increasing server sort buffer size
$sql = "select `problem_id`,`title`,`source`,`submit`,`accepted`,defunct,coin FROM problem A WHERE $filter_sql $order_by $limit_sql ";
$count_sql = "select count(1) from problem where  $filter_sql ";
//echo htmlentities( $sql);
if (!empty($filter_params) || $view_filter != "") {
    $total = pdo_query($count_sql, $filter_params);
    $cnt = $total[0][0] / $page_cnt;
    $result = pdo_query($sql, $filter_params);
} else {
    $total = mysql_query_cache($count_sql);
    $cnt = $total[0][0] / $page_cnt;
    $result = mysql_query_cache($sql);
}
//echo "$cnt $count_sql";

$view_total_page = ceil($cnt * 1.0);
$view_total_count = intval($total[0][0]);

// 분류 선택 목록: category.php와 같은 방식으로 공개 문제의 출처/분류를 모은다.
$view_categories = array();
if (!isset($_GET['ajax'])) {
    $category_rows = mysql_query_cache("select distinct source FROM `problem` where defunct='N' order by source LIMIT 5000");
    if ($category_rows) {
        foreach ($category_rows as $row) {
            foreach (explode(" ", $row['source']) as $cat) {
                $cat = trim($cat);
                if (mb_ereg("^http", $cat))
                    $cat = trim(get_domain($cat));
                if ($cat != "" && $cat != "&nbsp")
                    $view_categories[$cat] = true;
            }
        }
    }
    $view_categories = array_keys($view_categories);
    sort($view_categories);
}

/**
 * 문제 목록 데이터 처리
 *
 * 조회 결과를 순회해 화면에 보일 문제 목록을 만든다.
 * 풀이 상태 표시, 출처 분류, 링크 생성을 처리한다.
 */
$cnt = 0;
$view_problemset = array();
$i = 0;
foreach ($result as $row) {
    $view_problemset[$i] = array();

    if (isset($sub_arr[$row['problem_id']])) {
        if (isset($acc_arr[$row['problem_id']]))
            $view_problemset[$i][0] = "<div class='label label-success'>Y</div>";
        else
            $view_problemset[$i][0] = "<div class='label label-danger'>N</div>";
    } else {
        $view_problemset[$i][0] = "<div class=none> </div>";
    }

    $category = array();
    $cate = explode(" ", $row['source']);
    foreach ($cate as $cat) {
        $cat = trim($cat);
        if (mb_ereg("^http", $cat)) {
            $cat = get_domain($cat);
        }
        array_push($category, trim($cat));
    }
    $view_problemset[$i][1] = "<div fd='problem_id' class='center'>" . $row['problem_id'] . "</div>";
    $view_problemset[$i][2] = "<div class='left'><a href='problem.php?id=" . $row['problem_id'] . "'>" . $row['title'] . "</a></div>";;
    $view_problemset[$i][3] = "<div pid='" . $row['problem_id'] . "' fd='source' class='center'>";

    foreach ($category as $cat) {
        if (trim($cat) == "" || trim($cat) == "&nbsp")
            continue;

        $hash_num = hexdec(substr(md5($cat), 0, 7));
        $label_theme = $color_theme[$hash_num % count($color_theme)];

        if ($label_theme == "")
            $label_theme = "default";

        $view_problemset[$i][3] .= "<a title='" . htmlentities($cat, ENT_QUOTES, 'UTF-8') . "' class='label label-$label_theme' style='display: inline-block;' href='problemset.php?search=" . htmlentities(urlencode($cat), ENT_QUOTES, 'UTF-8') . "'>" . mb_substr($cat, 0, 10, 'utf8') . "</a>&nbsp;";
    }

    $view_problemset[$i][3] .= "</div >";
    $view_problemset[$i][4] = "<div class='center'><a href='status.php?problem_id=" . $row['problem_id'] . "&jresult=4'>" . $row['accepted'] . "</a></div>";
    $view_problemset[$i][5] = "<div class='center'><a href='status.php?problem_id=" . $row['problem_id'] . "'>" . $row['submit'] . "</a></div>";
    $i++;
}

/**
 * 템플릿 렌더링
 *
 * 요청 종류(AJAX 또는 일반 요청)에 따라 템플릿 파일을 고른다.
 * 최종 문제 목록 페이지를 출력한다.
 */
if (isset($_GET['ajax'])) {
    require("template/bs3/problemset.php");
} else {
    require("template/" . $OJ_TEMPLATE . "/problemset.php");
}

/**
 * 캐시 종료 처리
 *
 * 캐시 종료 파일이 있으면 캐시 처리를 실행한다.
 */
if (file_exists('./include/cache_end.php'))
    require_once('./include/cache_end.php');
