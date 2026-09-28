<?php
require_once("admin-header.php");
if(!(isset($_SESSION[$OJ_NAME.'_'.'administrator']))){
  exit(1);
}

$page_heading = $MSG_NEWS." - ".$MSG_SETMESSAGE;
$saved_notice = "";
$msgfile="msg/".$domain.".txt";
if(isset($_POST['do'])){
  require_once("../include/check_post_key.php");
  mkdir("msg");
  $fp = fopen($msgfile,"w");
  $msg = $_POST['msg'];

  $msg = str_replace("<p>", "", $msg);
  $msg = str_replace("</p>", "<br />", $msg);
  $msg = str_replace(",", "&#44;", $msg);

  if(false){
    $title = stripslashes($title);
  }

  $msg = RemoveXSS($msg);
  fputs($fp,$msg);
  fclose($fp);
  $saved_notice = $MSG_SUCCESS." · ".$MSG_SAVED_DATE." ".date('Y-m-d H:i:s');
}
if(file_exists($msgfile))
    $msg = file_get_contents($msgfile);

include("kindeditor.php");
?>

<script>document.body.classList.add('admin-form-page');</script>
<div class="admin-form-shell">
  <header class="admin-form-title">
    <h3><?php echo htmlentities($page_heading, ENT_QUOTES, 'UTF-8')?></h3>
  </header>
  <?php if($saved_notice){ ?>
  <div class="alert alert-success" role="status"><?php echo htmlentities($saved_notice, ENT_QUOTES, 'UTF-8')?></div>
  <?php } ?>
  <form action='setmsg.php' method='post'>
    <textarea name='msg' class="kindeditor" ><?php echo $msg?></textarea>
    <input type='hidden' name='do' value='do'>
    <div class="admin-form-actions">
      <input type='submit' class='btn btn-primary' value='<?php echo $MSG_SAVE?>'>
    </div>
<!--    <br>
      如果升级无法修改公告，发送“修改公告”到微信公众号onlinejudge看解决方案。<br>
      if this does not work, try run "sudo chown -R www-data /home/judge/src/web " in terminal.
-->
    <?php require_once("../include/set_post_key.php");?>
  </form>
</div>

<?php require_once('../oj-footer.php'); ?>
