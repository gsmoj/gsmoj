<?php require_once("admin-header.php");
require_once("../include/check_get_key.php");
// 비활성화된 사용자의 계정 정보(users, privilege)를 완전히 지운다.
// 지운 뒤에는 같은 ID로 다시 가입하거나 추가할 수 있다. 제출 기록(solution 등)은 남겨 둔다.
if(!isset($_SESSION[$OJ_NAME.'_'.'administrator'])) exit();
$user_id=isset($_GET['cid'])?$_GET['cid']:"";
if($user_id=="" || $_SESSION[$OJ_NAME."_user_id"]==$user_id){
        echo "자기 자신은 삭제할 수 없습니다.";
        exit(0);
}
$result=pdo_query("select `defunct` FROM `users` WHERE `user_id`=?",$user_id);
if(count($result)<1){
        echo "존재하지 않는 사용자입니다. <a href='user_list.php?trash'>돌아가기</a>";
        exit(0);
}
// 실수로 지우지 않도록 먼저 비활성화한 사용자만 완전히 삭제한다.
if($result[0]['defunct']!='Y'){
        echo "먼저 비활성화한 사용자만 완전히 삭제할 수 있습니다. <a href='user_list.php'>돌아가기</a>";
        exit(0);
}
pdo_query("DELETE FROM `privilege` WHERE `user_id`=?",$user_id);
pdo_query("DELETE FROM `users` WHERE `user_id`=? AND `defunct`='Y'",$user_id);
?>
<script language=javascript>
        location.href="user_list.php?trash";
</script>
