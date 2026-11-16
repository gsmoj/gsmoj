<?php
$getkey_session = isset($_SESSION[$OJ_NAME.'_'.'getkey']) ? $_SESSION[$OJ_NAME.'_'.'getkey'] : '';
$getkey_request = isset($_GET['getkey']) ? $_GET['getkey'] : '';
if (!is_string($getkey_session) || !is_string($getkey_request) || $getkey_session === '' || $getkey_request === '' || !hash_equals($getkey_session, $getkey_request)){
?>
<script language=javascript>
        history.go(-1);
</script>
<?php 
	exit(1);
}
else{
   unset($_SESSION[$OJ_NAME.'_'.'getkey']);
}
?>
