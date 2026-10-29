<?php $show_title="$MSG_REG_INFO - $OJ_NAME"; ?>
<?php include("template/$OJ_TEMPLATE/header.php");?>
<div class="padding">
  <h1><?php echo $MSG_REG_INFO?></h1>
  <div class="ui error message" id="error" data-am-alert hidden>
    <p id="error_info"></p>
  </div>
  
  <form action="register.php" method="post" role="form" class="ui form" onsubmit="return check();">
    
    <div class="field">
      <label for="user_id"><?php echo $MSG_USER_ID?>*</label>
      <input id="user_id" name="user_id" placeholder="아이디 입력 (3자 이상)*" type="text" required>
    </div>

    <div class="field">
      <label for="nick"><?php echo $MSG_NICK?>*</label>
      <input id="nick" name="nick" placeholder="닉네임 또는 본명 입력*" type="text" required>
    </div>

    <div class="field">
      <label><?php echo $MSG_PASSWORD?>*</label>
      <input id="password" name="password" placeholder="비밀번호 입력 (6자 이상)*" type="password" autocomplete="off" required>
    </div>

    <div class="field">
      <label><?php echo $MSG_REPEAT_PASSWORD?>*</label>
      <input id="rptpassword" name="rptpassword" placeholder="비밀번호 재입력*" type="password" autocomplete="off" required>
    </div>

    <div class="field">
      <label for="school"><?php echo $MSG_SCHOOL?></label>
      <input id="school" name="school" placeholder="광주소프트웨어마이스터고등학교" type="text" value="">
    </div>

    <div class="field">
      <label for="email"><?php echo $MSG_EMAIL?>*</label>
      <input id="email" name="email" placeholder="example@gsm.hs.kr*" type="text" required>
    </div>

    <?php if($OJ_VCODE){?>
      <div class="field">
        <label for="vcode"><?php echo $MSG_VCODE?>*</label>
        <input name="vcode" placeholder="보안문자 입력*" type="text" autocomplete="off" required>
        <img alt="click to change" src="vcode.php" onclick="this.src='vcode.php?'+Math.random()" height="30px" style="cursor: pointer; margin-top: 5px;">
      </div>
    <?php }?>

    <button name="submit" type="submit" class="ui primary button"><?php echo $MSG_REGISTER; ?></button>
    <button name="submit" type="reset" class="ui button"><?php echo $MSG_RESET; ?></button>
  </form>
</div>

<script>
function check(){
  // 1. 아이디 길이 검사
  if($("#user_id").val().length < 3) {
    alert("아이디는 최소 3글자 이상이어야 합니다.");
    $("#user_id").focus();
    return false;
  }
  // 2. 비밀번호 길이 검사
  if($("#password").val().length < 6) {
    alert("비밀번호는 최소 6글자 이상이어야 합니다.");
    $("#password").focus();
    return false;
  }
  // 3. 비밀번호 일치 검사
  if($("#password").val() != $("#rptpassword").val()) {
    alert("비밀번호 확인이 일치하지 않습니다.");
    $("#rptpassword").focus();
    return false;
  }
  // 4. GSM 학교 이메일 도메인 체크 (@gsm.hs.kr 고정)
  var email = $("#email").val().trim();
  if(!email.endsWith("@gsm.hs.kr")) {
    alert("광주소프트웨어마이스터고 이메일(@gsm.hs.kr)로만 가입할 수 있습니다.");
    $("#email").focus();
    return false;
  }
  return true;
}
</script>

<?php include("template/$OJ_TEMPLATE/footer.php");?>