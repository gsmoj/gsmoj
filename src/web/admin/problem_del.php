<?php
 require_once("admin-header.php");
ini_set("display_errors","Off");

function recursiveDelete($dir) {
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                $path = $dir . DIRECTORY_SEPARATOR . $file;
                if (is_dir($path)) {
                    recursiveDelete($path);
                } else {
                    unlink($path);
                }
            }
        }
        rmdir($dir);
    }
}

// 관리자 또는 이 문제의 작성자(pNNNN)만 삭제할 수 있다.
function problem_del_allowed($id) {
  global $OJ_NAME;
  return isset($_SESSION[$OJ_NAME.'_'.'administrator']) || isset($_SESSION[$OJ_NAME.'_'."p".$id]);
}

// 전용 문제는 시작한 회차·제출 여부를 확인하는 수행평가 화면에서만 삭제한다.
function problem_del_is_assessment($id) {
  $df=pdo_query("select defunct from problem where problem_id=?",$id);
  return !empty($df)&&$df[0][0]==='A';
}

// 문제 하나의 채점 데이터, 문제, 작성자 권한을 지우고 제출 기록은 문제 번호 0으로 남긴다.
function problem_del_one($id) {
  global $OJ_DATA;
  $basedir = "$OJ_DATA/$id";
  if(strlen($basedir)>16&&$id>0){
    //system("rm -rf $basedir");
    recursiveDelete($basedir);
  }
  $sql="delete FROM `problem` WHERE `problem_id`=?";
  pdo_query($sql,$id) ;
  $sql = "delete from `privilege` where `rightstr`=? ";
  pdo_query($sql, "p$id");
  $sql = "update solution set problem_id=0,result=13 where `problem_id`=? ";
  pdo_query($sql, $id);
}

// 다음 문제 번호가 지운 번호 뒤로 이어지도록 AUTO_INCREMENT를 맞춘다.
function problem_del_reset_auto_increment() {
  $sql="select max(problem_id) FROM `problem`" ;
  $result=pdo_query($sql);
  $row=$result[0];
  $max_id=$row[0];
  $max_id++;
  if($max_id<1000)$max_id=1000;

  $sql="ALTER TABLE problem AUTO_INCREMENT = $max_id";
  pdo_query($sql);
}

// 문제 목록에서 선택한 문제 일괄 삭제
if($_SERVER['REQUEST_METHOD']=="POST"){
  require_once("../include/check_post_key.php");
  $ids = array();
  if(isset($_POST['hlist']) && trim($_POST['hlist'])!==""){
    $ids = explode(",", $_POST['hlist']);
  }else if(isset($_POST['pid']) && is_array($_POST['pid'])){
    $ids = $_POST['pid'];
  }
  $ids = array_values(array_unique(array_filter(array_map('intval', $ids), function ($v) { return $v > 0; })));

  $deleted = 0;
  $skipped_assessment = 0;
  $skipped_denied = 0;
  if(count($ids)>0 && strlen($OJ_DATA)>8){
    foreach($ids as $id){
      if(!problem_del_allowed($id)){ $skipped_denied++; continue; }
      if(problem_del_is_assessment($id)){ $skipped_assessment++; continue; }
      if(empty(pdo_query("select 1 from problem where problem_id=?",$id))) continue;
      problem_del_one($id);
      $deleted++;
    }
    if($deleted>0) problem_del_reset_auto_increment();
  }

  $msg = "문제 ".$deleted."개를 삭제했습니다.";
  if($skipped_assessment>0) $msg .= "\n수행평가 전용 문제 ".$skipped_assessment."개는 수행평가 화면에서 삭제하세요.";
  if($skipped_denied>0) $msg .= "\n삭제 권한이 없는 문제 ".$skipped_denied."개는 건너뛰었습니다.";
  if(count($ids)>0 && strlen($OJ_DATA)<=8) $msg = "채점 데이터 경로 설정이 올바르지 않아 삭제하지 않았습니다.";
  ?>
        <script language=javascript>
                alert(<?php echo json_encode($msg, JSON_UNESCAPED_UNICODE)?>);
                location.href="problem_list.php";
        </script>
<?php
  exit(0);
}

require_once("../include/check_get_key.php");
$pid=intval($_GET['id']);
if(!problem_del_allowed($pid)){
  exit(1);
}
if(problem_del_is_assessment($pid)){
  echo "<script>alert('수행평가 전용 문제는 수행평가 화면에서 삭제하세요.');history.go(-1);</script>";
  exit(1);
}

// 문제 삭제 처리
  $id=intval($_GET['id']);
  if($id>0&&strlen($OJ_DATA)>8){
        problem_del_one($id);
        problem_del_reset_auto_increment();
        ?>
        <script language=javascript>
                history.go(-1);
        </script>
<?php
  }else{


  ?>
        <script language=javascript>
                alert("채점 데이터를 지우려면 php.ini에서 system() 함수를 허용해야 합니다.");
                history.go(-1);
        </script>
  <?php

  }

?>
