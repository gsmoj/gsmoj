<?php
// 문제 번호 대역: 일반 문제는 1000번부터, 수행평가용 문제는 5000번부터 매긴다.
// AUTO_INCREMENT(최댓값+1)에 맡기면 5000번대 문제가 생긴 뒤로 일반 문제도 5000번대로 이어지므로 대역 안에서 직접 정한다.
define('GSMOJ_PROBLEM_ID_START', 1000);
define('GSMOJ_ASSESSMENT_PROBLEM_ID_START', 5000);

// 대역 안의 다음 문제 번호. 일반 대역이 가득 차면 0을 돌려 AUTO_INCREMENT에 맡긴다.
function next_problem_id($assessment) {
  if ($assessment) {
    $row = pdo_query("SELECT MAX(`problem_id`) FROM `problem` WHERE `problem_id`>=?", GSMOJ_ASSESSMENT_PROBLEM_ID_START);
    $start = GSMOJ_ASSESSMENT_PROBLEM_ID_START;
  } else {
    $row = pdo_query("SELECT MAX(`problem_id`) FROM `problem` WHERE `problem_id`>=? AND `problem_id`<?", GSMOJ_PROBLEM_ID_START, GSMOJ_ASSESSMENT_PROBLEM_ID_START);
    $start = GSMOJ_PROBLEM_ID_START;
  }
  $max = (!empty($row) && $row[0][0] !== null) ? intval($row[0][0]) : 0;
  $next = max($start, $max + 1);
  if (!$assessment && $next >= GSMOJ_ASSESSMENT_PROBLEM_ID_START) return 0;
  return $next;
}

function addproblem($title, $time_limit, $memory_limit, $description, $input, $output, $sample_input, $sample_output, $hint, $source, $spj, $OJ_DATA) {
  //$spj=($spj);
  // 수행평가 화면에서 연 문제 등록(assessment=1)만 5000번대를 쓴다.
  $assessment = isset($_POST['assessment']) && intval($_POST['assessment']) == 1;
  $pid = next_problem_id($assessment);
  if ($pid > 0) {
    $sql = "INSERT INTO `problem` (`problem_id`,`title`,`time_limit`,`memory_limit`,`description`,`input`,`output`,`sample_input`,`sample_output`,`hint`,`source`,`spj`,`in_date`,`defunct`) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,NOW(),'Y')";
    pdo_query($sql, $pid, $title, $time_limit, $memory_limit, $description, $input, $output, $sample_input, $sample_output, $hint, $source, $spj);
  } else {
    $sql = "INSERT INTO `problem` (`title`,`time_limit`,`memory_limit`,`description`,`input`,`output`,`sample_input`,`sample_output`,`hint`,`source`,`spj`,`in_date`,`defunct`) VALUES(?,?,?,?,?,?,?,?,?,?,?,NOW(),'Y')";
    $pid = pdo_query($sql, $title, $time_limit, $memory_limit, $description, $input, $output, $sample_input, $sample_output, $hint, $source, $spj);
  }

  echo "New Problem:<a target=_blank href='../problem.php?id=$pid'>$pid ".htmlentities($title,ENT_QUOTES)."</a> added!<br>";

  if (isset($_POST['contest_id']) && intval($_POST['contest_id'])>0) {
    $cid = intval($_POST['contest_id']);
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
    mkdir($basedir, 0711, true);
  }

  return $pid;
}

function mkdata($pid, $filename, $input, $OJ_DATA) {
  $basedir = "$OJ_DATA/$pid";

  $fp = @fopen($basedir."/$filename","w");

  if ($fp) {
    fputs($fp, preg_replace("(\r\n)", "\n", $input));
    fclose($fp);
  }
  else {
    echo "- Error while opening".$basedir."/$filename ,try [chgrp -R www-data $OJ_DATA] and [chmod -R 771 $OJ_DATA ] ";
  }
}
?>
