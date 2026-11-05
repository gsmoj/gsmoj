<?php
if (!isset($_SESSION[$OJ_NAME.'_'.'postkey'])||!isset($_POST['postkey'])||!is_string($_POST['postkey'])||!hash_equals((string)$_SESSION[$OJ_NAME.'_'.'postkey'], $_POST['postkey'])){
	exit(1);
}
unset($_SESSION[$OJ_NAME.'_'.'postkey']);
?>
