<?php $show_title="$MSG_LOST_PASSWORD - $OJ_NAME"; ?>
<?php include("template/$OJ_TEMPLATE/header.php");?>

<style>
.password-reset-page { display:flex; align-items:flex-start; justify-content:center; min-height:calc(100vh - 170px); padding:4rem 1rem 2rem; }
.password-reset-card { box-sizing:border-box; width:100%; max-width:480px; padding:2rem !important; border-radius:1rem !important; }
.password-reset-icon { display:flex; align-items:center; justify-content:center; width:3.5rem; height:3.5rem; margin:0 auto 1rem; border-radius:50%; color:#2185d0; background:#eaf5ff; font-size:1.5rem; }
.password-reset-card h1 { margin:0 0 .65rem; color:#1f2937; font-size:1.65rem; text-align:center; }
.password-reset-description { margin:0 0 1.75rem; color:#64748b; line-height:1.65; text-align:center; }
.password-reset-card .field { margin-bottom:1.15rem !important; }
.password-reset-card .field label { margin-bottom:.5rem !important; color:#374151 !important; }
.password-reset-card .ui.input { width:100%; }
.password-reset-card .ui.input input { border-radius:.55rem !important; }
.password-reset-card .ui.primary.button { width:100%; margin-top:.5rem; padding:.9rem 1rem; border-radius:.55rem; }
.password-reset-vcode { display:flex; align-items:center; gap:.65rem; }
.password-reset-vcode .ui.input { flex:1; min-width:0; }
.password-reset-vcode img { flex:0 0 auto; height:38px; border:1px solid #d8dee8; border-radius:.45rem; cursor:pointer; background:#fff; }
.password-reset-alert { margin-bottom:1.25rem !important; text-align:left; }
.password-reset-help { margin-top:1.25rem; color:#94a3b8; font-size:.85rem; line-height:1.5; text-align:center; }
@media (max-width:520px) { .password-reset-page { padding:2rem .75rem; } .password-reset-card { padding:1.4rem !important; } }
</style>

<main class="password-reset-page">
  <section class="padding password-reset-card" aria-labelledby="password-reset-title">
    <div class="password-reset-icon"><i class="key icon"></i></div>
    <h1 id="password-reset-title">비밀번호 찾기</h1>
    <p class="password-reset-description">가입한 아이디와 이메일을 입력하면<br>비밀번호 재설정 인증 코드를 보내드립니다.</p>

    <?php if(!empty($error_msg)){?>
      <div class="ui negative message password-reset-alert"><i class="warning circle icon"></i><?php echo htmlentities($error_msg,ENT_QUOTES,'UTF-8')?></div>
    <?php }?>
    <?php if($SMTP_USER==="mailer@qq.com"){?>
      <div class="ui warning message password-reset-alert">메일 발송 계정이 설정되지 않았습니다. 관리자 또는 담당 교사에게 문의해 주세요.</div>
    <?php }?>

    <form class="ui form" action="lostpassword.php" method="post">
      <div class="field">
        <label for="reset-user-id"><?php echo $MSG_USER_ID?></label>
        <div class="ui left icon input"><i class="user icon"></i><input id="reset-user-id" name="user_id" type="text" autocomplete="username" required></div>
      </div>
      <div class="field">
        <label for="reset-email"><?php echo $MSG_EMAIL?></label>
        <div class="ui left icon input"><i class="mail icon"></i><input id="reset-email" name="email" type="email" autocomplete="email" required></div>
      </div>
      <div class="field">
        <label for="reset-vcode"><?php echo $MSG_VCODE?></label>
        <div class="password-reset-vcode">
          <div class="ui left icon input"><i class="shield alternate icon"></i><input id="reset-vcode" name="vcode" type="text" autocomplete="off" required></div>
          <img src="vcode.php" alt="인증 코드 새로고침" title="클릭하여 새 인증 코드 받기" onclick="this.src='vcode.php?'+Math.random()">
        </div>
      </div>
      <button class="ui primary button" name="submit" type="submit"><i class="paper plane icon"></i>인증 코드 받기</button>
    </form>
    <p class="password-reset-help">메일이 보이지 않으면 스팸 메일함도 확인해 주세요.</p>
  </section>
</main>

<?php include("template/$OJ_TEMPLATE/footer.php");?>
