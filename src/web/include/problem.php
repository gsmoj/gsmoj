<?php
// 문제 번호 대역: 일반 문제는 1000번부터, 수행평가 전용 문제는 5000번부터 매긴다(asm_next_problem_id).
// AUTO_INCREMENT(최댓값+1)에 맡기면 5000번대 문제가 생긴 뒤로 일반 문제도 5000번대로 이어지므로 일반 문제 번호를 직접 정한다.
define('GSMOJ_PROBLEM_ID_START', 1000);
define('GSMOJ_ASSESSMENT_PROBLEM_ID_START', 5000);

// 일반 문제(1000~4999)의 다음 번호. 대역이 가득 차면 0을 돌려 AUTO_INCREMENT에 맡긴다.
function next_problem_id() {
  $row = pdo_query("SELECT MAX(`problem_id`) FROM `problem` WHERE `problem_id`>=? AND `problem_id`<?", GSMOJ_PROBLEM_ID_START, GSMOJ_ASSESSMENT_PROBLEM_ID_START);
  $max = (!empty($row) && $row[0][0] !== null) ? intval($row[0][0]) : 0;
  $next = max(GSMOJ_PROBLEM_ID_START, $max + 1);
  return $next < GSMOJ_ASSESSMENT_PROBLEM_ID_START ? $next : 0;
}

function addproblem($title, $time_limit, $memory_limit, $description, $input, $output, $sample_input, $sample_output, $hint, $source, $spj, $OJ_DATA) {
  //$spj=($spj);
  $args = array($title, $time_limit, $memory_limit, $description, $input, $output, $sample_input, $sample_output, $hint, $source, $spj);
  // 수행평가 전용 문제(problem_add.php에서 assessment가 넘어온 경우)는 5000번부터, 일반 문제는 1000~4999 안에서 번호를 직접 정한다. 번호가 겹치면 다시 받는다.
  $asm = isset($_POST['assessment']) && intval($_POST['assessment']) > 0 && function_exists("asm_next_problem_id");
  for ($try = 0; $try < 3; $try++) {
    $id = $asm ? asm_next_problem_id() : next_problem_id();
    $a = $args;
    if ($id > 0) array_unshift($a, $id);
    $sql = "INSERT INTO `problem` (".($id > 0 ? "`problem_id`," : "")."`title`,`time_limit`,`memory_limit`,`description`,`input`,`output`,`sample_input`,`sample_output`,`hint`,`source`,`spj`,`in_date`,`defunct`) VALUES(".($id > 0 ? "?," : "")."?,?,?,?,?,?,?,?,?,?,?,NOW(),'Y')";
    $pid = pdo_query($sql, $a);
    if ($id == 0 || $pid !== -1) break;
  }
  if ($id > 0 && $pid !== -1) $pid = $id;

  echo "New Problem:<a target=_blank href='../problem.php?id=$pid'>$pid ".htmlentities($title,ENT_QUOTES)."</a> added!<br>";

  // 대회에 문제를 넣는 것은 관리자, 그 대회의 관리자(mNNNN), 수행평가가 아닌 대회의 대회 생성자만 할 수 있다.
  global $OJ_NAME;
  $can_add_contest = false;
  if (isset($_POST['contest_id']) && intval($_POST['contest_id'])>0) {
    $cid = intval($_POST['contest_id']);
    $can_add_contest = isset($_SESSION[$OJ_NAME.'_'.'administrator']) || isset($_SESSION[$OJ_NAME.'_'."m$cid"]);
    if (!$can_add_contest && isset($_SESSION[$OJ_NAME.'_'.'contest_creator'])) {
      $ct = pdo_query("SELECT `contest_type` FROM `contest` WHERE `contest_id`=?", $cid);
      $can_add_contest = !empty($ct) && !(intval($ct[0][0]) & 512);
    }
    if (!$can_add_contest) echo "&nbsp;&nbsp;- 이 대회에 문제를 추가할 권한이 없어 대회에는 넣지 않았습니다.<br>";
  }
  if ($can_add_contest) {
    $sql = "SELECT count(*) FROM `contest_problem` WHERE `contest_id`=?";
    $result = pdo_query($sql, $cid);
    $row = $result[0];
    $num = $row[0];
    
    echo "&nbsp;&nbsp;- Contest Problem Num = ".$num.":";

    $sql = "INSERT INTO `contest_problem` (`problem_id`,`contest_id`,`num`) VALUES(?,?,?)";	
    pdo_query($sql, $pid, $cid, $num);
  }

  $basedir = "$OJ_DATA/$pid";
  if (!is_dir($basedir)) {
    mkdir($basedir, 0771, true);
  }
  // 그룹(devgroup)은 목록 조회·수정 가능, 기타 사용자는 통과만 허용(spj가 judge 사용자로 파일을 읽음). umask와 무관하게 고정한다.
  @chmod($basedir, 02771);

  return $pid;
}

function mkdata($pid, $filename, $input, $OJ_DATA) {
  $basedir = "$OJ_DATA/$pid";

  $fp = @fopen($basedir."/$filename","w");

  if ($fp) {
    fputs($fp, preg_replace("(\r\n)", "\n", $input));
    fclose($fp);
    @chmod($basedir."/$filename", 0664);
  }
  else {
    echo "- Error while opening".$basedir."/$filename ,try [chgrp -R www-data $OJ_DATA] and [chmod -R 771 $OJ_DATA ] ";
  }
}
?>
