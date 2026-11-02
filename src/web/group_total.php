<?php
$cache_time = 30;
$OJ_CACHE_SHARE = false;
require_once('./include/cache_start.php');
require_once('./include/db_info.inc.php');
require_once('./include/memcache.php');
require_once('./include/setlang.php');
require_once('./include/bbcode.php');
$view_title = "$MSG_PROBLEM$MSG_LIST - $MSG_GROUP_NAME$MSG_STATISTICS";
$result = false;
if (isset($OJ_ON_SITE_CONTEST_ID)) {
    header("location:contest.php?cid=" . $OJ_ON_SITE_CONTEST_ID);
    exit();
}
function extractPlistBlocks($inputString)
{
    // 定义正则表达式模式
    $pattern = '/\[plist=[^\]]*\][^[]*?\[\/plist\]/';

    // 使用 preg_match_all 函数查找所有匹配项
    preg_match_all($pattern, $inputString, $matches);

    return $matches[0];
}

function extractPlistData($inputString)
{
    // 定义正则表达式模式
    $pattern = '/\[plist=([^]]*)\](.*?)\[\/plist\]/s';

    // 使用 preg_match 函数查找匹配项
    if (preg_match($pattern, $inputString, $matches)) {
        // 返回匹配到的 example1 和 内容1
        return [
            'name' => $matches[2],
            'list' => str_replace("&#44;", ",", trim($matches[1]))
        ];
    }

    return null;
}

$sql = "select content from news where content like '%[plist=%' and defunct='N' ";
// 示例输入字符串
$news = array_column(pdo_query($sql), 'content');
$news = array_unique($news);
$plista = array();
$bible = array();
foreach ($news as $plists) {
// 提取 plist 块
    $plistBlocks = extractPlistBlocks($plists);
    foreach ($plistBlocks as $plistB) {
        $plist = extractPlistData($plistB);
        if (!empty($plist)) array_push($plista, $plist);
    }
// 输出结果
    //$plista=array_merge($plist,$plistBlocks);
}
foreach ($plista as $plist) {
    $name = $plist["name"];
    $list = explode(",", $plist['list']);
    foreach ($list as $pid) {
        if (!empty($pid)) array_push($bible, $pid);
    }
}
$bible = array_unique($bible);
if (!empty($bible)) {
    $bible = pdo_query("select problem_id,title from problem where problem_id in (" . implode(",", $bible) . ")  and defunct='N' ");
    // 提取 id 列作为键
    $keys = array_column($bible, 'problem_id');
    // 提取 name 列作为值
    $values = array_column($bible, 'title');
    // 使用 array_combine 将键和值组合成一个映射
    $bible = array_combine($keys, $values);
}

///////////////////////////MAIN	
//NOIP赛制比赛时，移除相关题目

$exceptions = array();
$not_in_noip = "";
if (!isset($_SESSION[$OJ_NAME . "_administrator"])) {  // 管理员不受限
    // 진행 중인 수행평가(contest_type & 512)도 제외한다. 키워드가 비어 있으면 모든 대회와 일치하므로 제목 조건은 키워드가 있을 때만 붙인다.
    $noip_title_sql = (isset($OJ_NOIP_KEYWORD) && $OJ_NOIP_KEYWORD) ? "c.title like ? or " : "";
    $noip_params = $noip_title_sql ? array("%$OJ_NOIP_KEYWORD%") : array();
    $now = date('Y-m-d H:i', time());
    $sql = "select contest_id from contest c where  c.start_time<'$now' and c.end_time>'$now' and ( $noip_title_sql( c.contest_type & (20|512) ) > 0 )";
    $row = pdo_query($sql, $noip_params);
    if (count($row) > 0) {
        $exceptions = array_column($row, 'contest_id');
        //      var_dump($exceptions);
        // 템플릿의 제출 조회가 진행 중인 대회의 제출을 집계하지 않도록 조건을 만든다.
        $not_in_noip = " and contest_id not in (" . implode(",", array_map('intval', $exceptions)) . ")";
    }
}
if (isset($_GET['group_name'])) $group_name = basename($_GET['group_name']);
else $group_name = $_SESSION[$OJ_NAME . '_group_name'];
if (isset($_GET['down'])) {
    header("Content-type:   application/excel");
    header("content-disposition:   attachment;   filename=$MSG_GROUP_NAME.$MSG_STATISTICS" . "_" . $group_name . ".xls");
}
$limit = 10;
if (isset($_SESSION[$OJ_NAME . '_contest_creator'])) $limit += 70;
if (isset($_SESSION[$OJ_NAME . '_administrator'])) $limit += 100;
$users = pdo_query("select user_id,nick from users where group_name=? and defunct='N'  order by solved desc  limit $limit ", $group_name);
$user_ida = array_column($users, 0);
/////////////////////////Template
require("template/" . $OJ_TEMPLATE . "/" . basename(__FILE__));
/////////////////////////Common foot
if (file_exists('./include/cache_end.php'))
    require_once('./include/cache_end.php');
