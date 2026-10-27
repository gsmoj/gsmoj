<!DOCTYPE html>
<html lang="ko">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="../../favicon.ico">

    <title><?php echo $OJ_NAME?></title>  
    <?php include("template/$OJ_TEMPLATE/css.php");?>    

    </head>

  <body>

  <div class="container">
    <?php include("template/$OJ_TEMPLATE/nav.php");?>    
    
    <div class="jumbotron">
      <form action="register.php" onsubmit="return check();" method="post" role="form" class="form-horizontal">
        
        <div class="form-group">
          <label class="col-sm-4 control-label"><?php echo $MSG_REG_INFO?></label>
        </div>

        <div class="form-group">
          <label class="col-sm-4 control-label"><?php echo $MSG_USER_ID?></label>
          <div class="col-sm-4">
            <input id="user_id" name="user_id" class="form-control" placeholder="아이디 입력 (3자 이상)*" type="text" required>
          </div>
        </div>

        <div class="form-group">
          <label class="col-sm-4 control-label"><?php echo $MSG_NICK?></label>
          <div class="col-sm-4">
            <input id="nick" name="nick" class="form-control" placeholder="닉네임 또는 본명 입력*" type="text" required>
          </div>
        </div>

        <div class="form-group">
          <label class="col-sm-4 control-label"><?php echo $MSG_PASSWORD?></label>
          <div class="col-sm-4">
            <input id="password" name="password" class="form-control" placeholder="비밀번호 입력 (6자 이상)*" type="password" autocomplete="off" required>
          </div>
        </div>

        <div class="form-group">
          <label class="col-sm-4 control-label"><?php echo $MSG_REPEAT_PASSWORD?></label>
          <div class="col-sm-4">
            <input id="rptpassword" name="rptpassword" class="form-control" placeholder="비밀번호 재입력*" type="password" autocomplete="off" required>
          </div>
        </div>

        <div class="form-group">
          <label class="col-sm-4 control-label"><?php echo $MSG_SCHOOL?></label>
          <div class="col-sm-4">
            <input id="school" name="school" class="form-control" placeholder="광주소프트웨어마이스터고등학교" type="text">
          </div>
        </div>

        <div class="form-group">
          <label class="col-sm-4 control-label"><?php echo $MSG_EMAIL?></label>
          <div class="col-sm-4">
            <input id="email" name="email" class="form-control" placeholder="example@gsm.hs.kr*" type="text" required>
          </div>
        </div>

        <?php if($OJ_VCODE){?>
        <div class="form-group">
          <label class="col-sm-4 control-label"><?php echo $MSG_VCODE?></label>
          <div class="col-sm-3">
            <input name="vcode" class="form-control" placeholder="보안문자 입력*" type="text" autocomplete="off">
          </div>
          <div class="col-sm-4">
            <img alt="클릭하시면 이미지가 변경됩니다." src="vcode.php" onclick="this.src='vcode.php?'+Math.random()" height="30px">*
          </div>
        </div>
        <?php }?>

        <div class="form-group">
          <div class="col-sm-offset-4 col-sm-2">
            <button name="submit" type="submit" class="btn btn-default btn-block"><?php echo $MSG_REGISTER; ?></button>
          </div>
          <div class="col-sm-2">
            <button name="submit" type="reset" class="btn btn-default btn-block"><?php echo $MSG_RESET; ?></button>
          </div>
        </div>
        
      </form>

      <script>
      function check(){
        // 1. 아이디 길이 제한 체크
        if($("#user_id").val().length < 3) {
          alert("아이디는 최소 3글자 이상이어야 합니다.");
          $("#user_id").focus();
          return false;
        }
        // 2. 비밀번호 길이 제한 체크
        if($("#password").val().length < 6) {
          alert("비밀번호는 최소 6글자 이상이어야 합니다.");
          $("#password").focus();
          return false;
        }
        // 3. 비밀번호 일치 체크
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

      // 페이지 로드가 끝난 후, 자바스크립트로 회색 가이드 글자를 강제 변경합니다.
      $(document).ready(function() {
          $("#user_id").attr("placeholder", "아이디 입력 (3자 이상)*");
          $("#nick").attr("placeholder", "닉네임 또는 본명 입력*");
          $("#password").attr("placeholder", "비밀번호 입력 (6자 이상)*");
          $("#rptpassword").attr("placeholder", "비밀번호 재입력*");
          $("#school").attr("placeholder", "광주소프트웨어마이스터고등학교");
          $("#email").attr("placeholder", "example@gsm.hs.kr*");
      });
      </script> 
    </div>
  </div> <?php include("template/$OJ_TEMPLATE/js.php");?>      
    <script>
         // 기존에 있던 코드 아래에 placeholder 강제 적용을 한 번 더 입힙니다.
         $("input").attr("class","form-control");
         $("#user_id").attr("placeholder", "아이디 입력 (3자 이상)*");
         $("#nick").attr("placeholder", "닉네임 또는 본명 입력*");
         $("#password").attr("placeholder", "비밀번호 입력 (6자 이상)*");
         $("#rptpassword").attr("placeholder", "비밀번호 재입력*");
         $("#school").attr("placeholder", "광주소프트웨어마이스터고등학교");
         $("#email").attr("placeholder", "example@gsm.hs.kr*");
    </script>
  </body>
</html>