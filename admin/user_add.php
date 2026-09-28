<?php require_once("admin-header.php");

if (!(isset($_SESSION[$OJ_NAME.'_'.'administrator']) || isset($_SESSION[$OJ_NAME.'_'.'user_adder']) )){
  exit(1);
}

if(isset($OJ_LANG)){
  require_once("../lang/$OJ_LANG.php");
}
?>

<title><?php echo $MSG_USER." ".$MSG_ADD?></title>
<script>document.body.classList.add('admin-form-page');</script>

<div class="admin-form-shell">
<header class="admin-form-title"><h3><?php echo htmlentities($MSG_USER." - ".$MSG_ADD, ENT_QUOTES, 'UTF-8')?></h3></header>

<?php
if(isset($_POST['do'])){
  require_once("../include/check_post_key.php");
  require_once("../include/my_func.inc.php");

  $pieces = explode("\n", trim($_POST['ulist']));
  //想支持中文，修改下行代码为：$pieces = preg_replace("/[^\x20-\x7e\x{4e00}-\x{9fa5}]/u", " ", $pieces);  //修改代码支持user_id为中文
  $pieces = preg_replace("/[^\x20-\x7e]/", " ", $pieces);  //!!important

  $ulist = "";
  if(count($pieces)>0 && strlen($pieces[0])>0){
    echo "<div class='admin-result-log'>";
    for($i=0; $i<count($pieces); $i++){
      $id_pw = explode(" ", trim($pieces[$i]));
      if(count($id_pw) != 2){
        echo "&nbsp;&nbsp;&nbsp;&nbsp;".$id_pw[0]." ... Error : Line format error!<br>";
        for($j=0; $j<count($id_pw); $j++)
        {
          $ulist = $ulist.$id_pw[$j]." ";
        }
        $ulist = trim($ulist)."\n";
      } else {
        $sql = "SELECT `user_id` FROM `users` WHERE `users`.`user_id` = ?";
        $result = pdo_query($sql, $id_pw[0]);
        $rows_cnt = count($result);

        if($rows_cnt == 1){
          echo "&nbsp;&nbsp;&nbsp;&nbsp;".$id_pw[0]." ... Error : User already exist!<br>";
          $ulist = $ulist.$id_pw[0]." ".$id_pw[1]."\n";
        } else {
          $passwd = pwGen($id_pw[1]);
          if(!isset($OJ_EXPIRY_DAYS)) $OJ_EXPIRY_DAYS=365;
          $sql = "INSERT INTO `users` (`user_id`, `password`, `reg_time`,`expiry_date`, `nick`) VALUES (?, ?, NOW(),date_add(curdate(),interval $OJ_EXPIRY_DAYS  day), ?);";
          pdo_query($sql, $id_pw[0], $passwd, $id_pw[0]);
          echo $id_pw[0]." is added!<br>";

          $ip = ($_SERVER['REMOTE_ADDR']);
          $sql="INSERT INTO `loginlog`(user_id,password,ip,time) VALUES(?,?,?,NOW())";
          pdo_query($sql, $id_pw[0], "user added", $ip);
        }
      }
    }
    echo "<br>Remained lines have error!";
    echo "</div>";
  }
}
?>

<form action=user_add.php method=post>
  <div>
    <label class="admin-field-label" for="ulist"><?php echo $MSG_USER_ID?> <?php echo $MSG_PASSWORD?></label>
    <p class="admin-field-help">한 줄에 한 명씩 "아이디 비밀번호" 형식으로 입력합니다.</p>
    <textarea id='ulist' name='ulist' rows='10' class='form-control' placeholder='userid1 password1<?php echo "\n"?>userid2 password2<?php echo "\n"?>userid3 password3<?php echo "\n"?>
            <?php echo $MSG_PRIVATE_USERS_ADD?><?php echo "\n"?>'><?php if(isset($ulist)){ echo $ulist;}?></textarea>
  </div>

  <?php require_once("../include/set_post_key.php");?>
  <div class="admin-form-actions">
    <button name="do" type="submit" value="do" class="btn btn-primary" ><?php echo $MSG_SAVE?></button>
    <button name="submit" type="reset" class="btn btn-default"><?php echo $MSG_RESET?></button>
  </div>

</form>

</div>


