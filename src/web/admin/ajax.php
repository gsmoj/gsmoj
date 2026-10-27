<?php
ini_set("display_errors", "Off");  //set this to "On" for debugging  ,especially when no reason blank shows up.
require_once("../include/db_info.inc.php");
if(!(isset($_SESSION[$OJ_NAME.'_administrator'])||isset($_SESSION[$OJ_NAME.'_problem_editor'])||isset($_SESSION[$OJ_NAME.'_contest_creator'])||isset($_SESSION[$OJ_NAME.'_tag_adder']))){
  exit(1);  
}
/**
 * 관리자 목록 화면의 인라인 수정 요청을 처리한다.
 *
 * $tb: 대상 테이블 접두어(user, problem, news 등)
 * $fd: 수정할 필드명
 * $pr: 필요한 권한명
 */
function try_ajax($tb,$fd,$pr){
	global $OJ_NAME,$_SESSION,$_POST;
	if(!isset($_POST["m"])) return;
	$m=$_POST["m"];	
	if($m==$tb."_update_".$fd  && ( isset($_SESSION[$OJ_NAME.'_'.$pr]) )){
                $new_value=$_POST[$fd];
				if($tb=="user"){ 
					$tb_name="users";
					$data_id=$_POST[$tb.'_id'];
				}else{
					$tb_name=$tb;
					$data_id=intval($_POST[$tb.'_id']);
				}
                $sql="update `".$tb_name."` set `".$fd."`=? where `".$tb."_id`=?";
                echo pdo_query($sql,$new_value,$data_id);
        }
}

/**
 * 공백으로 구분된 source/tag 문자열에서 중복 값을 제거한다.
 * 문제 출처나 태그를 AJAX로 추가할 때 같은 단어가 반복 저장되는 것을 막는다.
 */
function uniqueSource($str) {
    // 정규식으로 분리하여 여러 개의 연속 공백을 하나의 구분자로 처리한다.
    $arr = preg_split('/\s+/', trim($str));

    // 배열 안의 중복 값을 제거한다.
    $uniqueArr = array_unique($arr);

    // 다시 공백 하나로 이어 붙여 DB에 저장할 문자열을 만든다.
    $result = implode(' ', $uniqueArr);

    return $result;
}
if($_SERVER['REQUEST_METHOD']=="POST"){
	$m=$_POST["m"];	
	if($m=="problem_set_source" && ( isset($_SESSION[$OJ_NAME.'_administrator']) || isset($_SESSION[$OJ_NAME.'_problem_editor']) || isset($_SESSION[$OJ_NAME.'_tag_adder']) ) ){
		$pid=intval($_POST['pid']);
		$new_source=uniqueSource($_POST['ns']);	
		$sql= "update problem set source=? where problem_id=?";		
		echo pdo_query($sql,$new_source,$pid);
		//echo $sql." [".$new_source."]";
	}
	if($m=="problem_add_source" && ( isset($_SESSION[$OJ_NAME.'_administrator']) || isset($_SESSION[$OJ_NAME.'_problem_editor']) || isset($_SESSION[$OJ_NAME.'_tag_adder']) ) ){
		$pid=intval($_POST['pid']);
		$new_source=($_POST['ns']);	
		$old_source=pdo_query("select source from problem where problem_id=?",$pid)[0][0];
		$new_source=uniqueSource($new_source." ".$old_source);
		$sql= "update problem set source=? where problem_id=?";		
		echo pdo_query($sql,$new_source,$pid);
		//echo $sql;
	}
	if($m=="problem_update_time" && ( isset($_SESSION[$OJ_NAME.'_administrator']) || isset($_SESSION[$OJ_NAME.'_problem_editor']) ) ){
		$pid=intval($_POST['pid']);
		$time=intval($_POST['t']);	
		$sql= "update problem set time_limit=? where problem_id=?";		
		echo pdo_query($sql,$time,$pid);
	}
	if($m=="problem_get_title"  && ( isset($_SESSION[$OJ_NAME.'_administrator']) || isset($_SESSION[$OJ_NAME.'_problem_editor']) )){
                $pid=intval($_POST['pid']);
                $sql= "select title,source from problem where problem_id=?";
                $row=mysql_query_cache($sql,$pid)[0];
                echo $row['title']."&nbsp;&nbsp;<span class='label label-success'>".$row['source']."</span>";
	}
	
        if($m=="user_update_nick"  && ( isset($_SESSION[$OJ_NAME.'_administrator']) )){
                $user_id=$_POST['user_id'];
                $nick=$_POST['nick'];
                $sql= "update users set nick=? where user_id=?";
                echo pdo_query($sql,$nick,$user_id);
		$sql= "update solution set nick=? where user_id=?";
                pdo_query($sql,$nick,$user_id);
        }
	/*
        if($m=="user_update_expiry_date"  && ( isset($_SESSION[$OJ_NAME.'_administrator']) )){
                $user_id=$_POST['user_id'];
                $expiry_date=$_POST['expiry_date'];
                $sql= "update users set expiry_date=? where user_id=?";
                echo pdo_query($sql,$expiry_date,$user_id);
        }
	if($m=="user_update_school"  && ( isset($_SESSION[$OJ_NAME.'_administrator']) )){
                $user_id=$_POST['user_id'];
                $school=$_POST['school'];
                $sql= "update users set school=? where user_id=?";
                echo pdo_query($sql,$school,$user_id);
        }
	if($m=="user_update_group_name"  && ( isset($_SESSION[$OJ_NAME.'_administrator']) )){
                $user_id=$_POST['user_id'];
                $group_name=$_POST['group_name'];
                $sql="update users set group_name=? where user_id=?";
                echo pdo_query($sql,$group_name,$user_id);
	}
	 */
	// try_ajax("user","nick","administrator");
	try_ajax("user","expiry_date","administrator");
	try_ajax("user","school","administrator");
	try_ajax("user","group_name","administrator");
	try_ajax("user","coin_bonus","administrator");
	try_ajax("problem","coin","administrator");
	try_ajax("problem","coin","problem_editor");
	try_ajax("user","coin_spent","administrator");
	try_ajax("news","importance","administrator");
	try_ajax("problem","time_limit","administrator");
        try_ajax("problem","memory_limit","administrator");

	if($m=="get_user_list_of_contest"  && ( isset($_SESSION[$OJ_NAME.'_administrator'])||isset($_SESSION[$OJ_NAME.'_contest_creator']) )){
			$contest_id=intval($_POST['contest_id']);
			$sql= "select distinct user_id from privilege where rightstr=? ";
			$users=pdo_query($sql,"c".$contest_id);
			foreach($users as $user){
					echo $user['user_id']."\r\n";
			}
	}

}




