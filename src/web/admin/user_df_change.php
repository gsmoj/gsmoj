<?php require_once("admin-header.php");
require_once "../include/email.class.php";
require_once("../include/check_get_key.php");
$user_id=$_GET['cid'];
//echo htmlentities($user_id,ENT_QUOTE,'UTF-8');
if(!isset($_SESSION[$OJ_NAME.'_'.'administrator'])) exit();
if($_SESSION[$OJ_NAME."_user_id"]==$user_id){
        echo "자기 자신의 계정은 비활성화할 수 없습니다!";
        exit(0);
}
$sql="select `defunct`,email FROM `users` WHERE `user_id`=?";
$result=pdo_query($sql,$user_id);
$num=count($result);
if ($num<1){
        echo "사용자가 없습니다!";
        exit(0);
}
$row=$result[0];
if ($row[0]=='N'){
        $sql="UPDATE `users` SET `defunct`='Y' WHERE `user_id`=?";
        pdo_query($sql,$user_id);
}else{
        $sql="UPDATE `users` SET `defunct`='N' WHERE `user_id`=?";
        pdo_query($sql,$user_id);
        if(isset($OJ_SaaS_ENABLE) && $OJ_SaaS_ENABLE && $domain == $DOMAIN ){
                create_subdomain($user_id,'syzoj',3);
                $address=$row['email'];
                $mailtitle = "$OJ_NAME 계정 재활성화 안내";//邮件主题
                $mailcontent = "$user_id:\n안녕하세요.\n
                                              ".$DOMAIN." 시스템에 하위 OJ가 개설되었습니다. 하위 시스템 도메인은 ".$user_id.".".$DOMAIN." 입니다.\n
                                              시스템에 접속해 새 계정 ".$user_id."을(를) 등록하면 자동으로 관리자가 됩니다.\n\n\n
                                                                                        $OJ_NAME 온라인 저지";//邮件内容
                email($address,$mailtitle,$mailcontent);

        }
?>
        <a href="user_list.php"><?php echo $MSG_BACK?></a>
<?php
}
?>

<script language=javascript>
        history.go(-1);
</script>
