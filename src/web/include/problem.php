<?php
function addproblem($title, $time_limit, $memory_limit, $description, $input, $output, $sample_input, $sample_output, $hint, $source, $spj, $OJ_DATA) {
  //$spj=($spj);
  $sql = "INSERT INTO `problem` (`title`,`time_limit`,`memory_limit`,`description`,`input`,`output`,`sample_input`,`sample_output`,`hint`,`source`,`spj`,`in_date`,`defunct`) VALUES(?,?,?,?,?,?,?,?,?,?,?,NOW(),'Y')";
  //echo $sql;
  $pid = pdo_query($sql, $title, $time_limit, $memory_limit, $description, $input, $output, $sample_input, $sample_output, $hint, $source, $spj);

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
