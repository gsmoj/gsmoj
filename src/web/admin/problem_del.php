<?php
 require_once("admin-header.php");
ini_set("display_errors","Off");
require_once("../include/check_get_key.php");
$pid=intval($_GET['id']);
if(!(isset($_SESSION[$OJ_NAME.'_'.'administrator']) || isset($_SESSION[$OJ_NAME.'_'."p".$pid]) )){
  exit(1);
}
// 전용 문제는 시작한 회차·제출 여부를 확인하는 수행평가 화면에서만 삭제한다.
$df=pdo_query("select defunct from problem where problem_id=?",$pid);
if(!empty($df)&&$df[0][0]==='A'){
  echo "<script>alert('수행평가 전용 문제는 수행평가 화면에서 삭제하세요.');history.go(-1);</script>";
  exit(1);
}

?>
<?php
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

// 문제 삭제 처리
  $id=intval($_GET['id']);
  if($id>0&&strlen($OJ_DATA)>8){
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
	  
        $sql="select max(problem_id) FROM `problem`" ;
        $result=pdo_query($sql);
        $row=$result[0];
        $max_id=$row[0];
        $max_id++;
        if($max_id<1000)$max_id=1000;
        
        $sql="ALTER TABLE problem AUTO_INCREMENT = $max_id";
        pdo_query($sql);
        ?>
        <script language=javascript>
                history.go(-1);
        </script>
<?php 
  }else{
  
  
  ?>
        <script language=javascript>
                alert("Nees enable system() in php.ini");
                history.go(-1);
        </script>
  <?php 
  
  }

?>
