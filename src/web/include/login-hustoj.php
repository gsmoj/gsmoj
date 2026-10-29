<?php 
	require_once("./include/my_func.inc.php");
	require_once("./include/login_session.php");
    
	function check_login($user_id,$password){
		global $ip,$view_errors;
		$pass2 = 'No Saved';
		if(isset($_SESSION))session_destroy();
		session_start();
		$sql="SELECT `user_id`,`password` FROM `users` WHERE `user_id`=? and defunct='N'  and expiry_date >= curdate()  ";  //有效期当天可以登录
		$result=pdo_query($sql,$user_id);
		if($result==-1){
                        //database need update 
                        $sql="alter table users add column expiry_date date not null default '2099-01-01' after reg_time;";  // 更新库结构
                        $result=pdo_query($sql);
			$sql="SELECT `user_id`,`password` FROM `users` WHERE `user_id`=? and defunct='N'  and expiry_date >= curdate()  ";  //有效期当天可以登录
			$result=pdo_query($sql,$user_id);
                }
		if(count($result)==1){
			$row = $result[0];
			if( pwCheck($password,$row['password'])){
				$user_id=$row['user_id'];
				if(!check_exam_login_ip($user_id)) return false;  // 시험 모드 IP 확인 (login_session.php)

				$sql="UPDATE users set accesstime=now(),ip=? where user_id=?";
				pdo_query($sql,$ip,$user_id);
				return $user_id;
			}
		}
		return false; 
	}
?>

