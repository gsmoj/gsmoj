<?php require("admin-header.php");
if (!(isset($_SESSION[$OJ_NAME.'_'.'administrator']) || isset($_SESSION[$OJ_NAME.'_'.'contest_creator']))) {
	exit(1);
}?>
<?php if(isset($_POST['prefix'])){
	require_once("../include/check_post_key.php");
	$prefix=$_POST['prefix'];
	require_once("../include/my_func.inc.php");
	if (!is_valid_user_name($prefix)){
		echo "Prefix is not valid.";
		exit(0);
	}
	$teamnumber=intval($_POST['teamnumber']);
        $pieces = explode("\n", trim($_POST['ulist']));
	
	if ($teamnumber>0){
		echo "<table class='table admin-result-table'>";
		echo "<tr><th colspan=3>배부할 팀 계정</th></tr>";
		echo "<tr><th>팀 이름</th><th>로그인 ID</th><th>비밀번호</th></tr>";
		$max_length=20;
		for($i=1;$i<=$teamnumber;$i++){
			
        $user_id=$prefix.($i<10?('0'.$i):$i);
			$password=strtoupper(substr(MD5($user_id.rand(0,9999999)),0,10));
                        if(isset($pieces[$i-1]))
                        	$nick=$pieces[$i-1];
                        else
				$nick="your_own_nick";
			if($teamnumber==1) $user_id=$prefix;

			echo "<tr><td>$nick<td>$user_id</td><td>$password</td></tr>";
			
			$password=pwGen($password);
			$email="your_own_email@internet";
                       
			$school="your_own_school";
			$ip = ($_SERVER['REMOTE_ADDR']);
			if( !empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ){
			    $REMOTE_ADDR = $_SERVER['HTTP_X_FORWARDED_FOR'];
			    $tmp_ip=explode(',',$REMOTE_ADDR);
			    $ip =(htmlentities($tmp_ip[0],ENT_QUOTES,"UTF-8"));
			}
			
			if(mb_strlen($nick,'utf-8')>20){
				$new_len=mb_strlen($nick,'utf-8');
				if($new_len>$max_length){
					$max_length=$new_len;
					$longer="ALTER TABLE `users` MODIFY COLUMN `nick` varchar($max_length) NULL DEFAULT '' ";
					pdo_query($longer);
				}
			}
			if( $user_id == "admin" ) continue;
			$sql="INSERT INTO `users`("."`user_id`,`email`,`ip`,`accesstime`,`password`,`reg_time`,`nick`,`school`)".
			"VALUES(?,?,?,NOW(),?,NOW(),?,?)on DUPLICATE KEY UPDATE `email`=?,`ip`=?,`accesstime`=NOW(),`password`=?,`reg_time`=now(),nick=?,`school`=?";
			pdo_query($sql,$user_id,$email,$ip,$password,$nick,$school,$email,$ip,$password,$nick,$school) ;
		}
		echo  "</table>";
		
		
	}
	
}
?>
<div class="admin-form-shell admin-compact-form">
<header class="admin-form-title"><h3>대회 팀 생성</h3><p>접두어와 인원 수로 팀 계정을 한 번에 만듭니다.</p></header>
	
	<form action='team_generate.php' method=post class="admin-team-form">
	    <label class="admin-field-label">계정 접두어</label><input type='text' name='prefix' value='team' placeholder="예: team"><br>
		<label class="admin-field-label">생성할 팀 수</label><input class="input-mini" type=input name='teamnumber' value=5 size=3><br>
				<label class="admin-field-label">팀 이름 목록</label><textarea name="ulist" rows="12" cols="40" placeholder="팀 이름을 한 줄에 하나씩 입력하세요."></textarea>
		<?php require_once("../include/set_post_key.php");?>
		<input class="btn btn-primary" type=submit value="팀 계정 생성"><br>
	</form>
	<p class="text-danger"><strong>같은 접두어로 다시 생성하면 기존 계정의 팀 이름과 비밀번호가 바뀝니다.</strong></p>

</div>
