<?php require("admin-header.php");
if (!(isset($_SESSION[$OJ_NAME.'_'.'administrator']) || isset($_SESSION[$OJ_NAME.'_'.'contest_creator']))) {
	exit(1);
}?>
<?php

if(isset($_POST['idlist']) && isset($_POST['pwdlist']) && isset($_POST['nicklist']))
{
	require_once("../include/check_post_key.php");
	require_once("../include/my_func.inc.php");
	
    $idPieces = explode("\n", trim($_POST['idlist']));
    $pwdPieces = explode("\n", trim($_POST['pwdlist']));
	$nickPieces = explode("\n", trim($_POST['nicklist']));

	$teamnumber=count($idPieces);

	if($teamnumber==count($pwdPieces) && $teamnumber==count($nickPieces))
	{
		$ok=true;
		for($i=0;$i<$teamnumber;$i++)
		{
			$idPieces[$i]=trim($idPieces[$i]);
			$pwdPieces[$i]=trim($pwdPieces[$i]);
			$nickPieces[$i]=trim($nickPieces[$i]);

			if(strlen($idPieces[$i])==0)
			{
				echo "<h3>로그인 ID에 빈 줄이 있어 계정을 생성하지 않았습니다.</h3>";
				$ok=false;
			}
			if(strlen($pwdPieces[$i])==0)
			{
				echo "<h3>비밀번호에 빈 줄이 있어 계정을 생성하지 않았습니다.</h3>";
				$ok=false;
			}
			if(strlen($nickPieces[$i])==0)
			{
				echo "<h3>팀 이름에 빈 줄이 있어 계정을 생성하지 않았습니다.</h3>";
				$ok=false;
			}
			if (!is_valid_user_name($idPieces[$i])){
				echo "<h3>로그인 ID \"".$idPieces[$i]."\"을(를) 사용할 수 없어 계정을 생성하지 않았습니다.</h3>";
				$ok=false;				
			}
		}

		if($ok)
		{
			echo "<table class='table admin-result-table'>";
			echo "<tr><th colspan=3>배부할 팀 계정</th></tr>";
			echo "<tr><th>팀 이름</th><th>로그인 ID</th><th>비밀번호</th></tr>";

			$max_length=20;
	
			for($i=0;$i<$teamnumber;$i++){
				$user_id=$idPieces[$i];
				$nick=$nickPieces[$i];

				echo "<tr><td>$nick<td>$user_id</td><td>$pwdPieces[$i]</td></tr>";

				$password=pwGen($pwdPieces[$i]);

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
		}

		echo  "</table><br/>";
		
	}else{
		echo "<h3>로그인 ID, 비밀번호, 팀 이름의 줄 수가 서로 다릅니다.</h3>";
	}
	
	
}

?>
<div class="admin-form-shell admin-compact-form">
<header class="admin-form-title"><h3>대회 팀 생성 (직접 입력)</h3><p>로그인 ID가 이미 있으면 해당 계정 정보가 입력한 내용으로 바뀝니다.</p></header>
	<form action='team_generate2.php' method=post class="admin-team-form">
		<div class="row">
			<div class="col-md-4">
			<label class="admin-field-label">로그인 ID</label><textarea name="idlist" rows="12" cols="40" placeholder="팀 ID를 한 줄에 하나씩 입력하세요."></textarea>
			</div>
			<div class="col-md-4">
			<label class="admin-field-label">비밀번호</label><textarea name="pwdlist" rows="12" cols="40" placeholder="비밀번호를 한 줄에 하나씩 입력하세요."></textarea>
			</div>
			<div class="col-md-4">
			<label class="admin-field-label">팀 이름</label><textarea name="nicklist" rows="12" cols="40" placeholder="팀 이름을 한 줄에 하나씩 입력하세요."></textarea>
			</div>
		</div>
		<div class="row">
		<br/>
		<?php require_once("../include/set_post_key.php");?>
		<input class="btn btn-primary" type=submit value="팀 계정 생성"><br>
		</div>
	</form>

</div>
