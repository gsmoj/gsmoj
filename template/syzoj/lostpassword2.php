<?php $show_title="$MSG_LOSTPASSWORD_MAILBOX - $OJ_NAME"; ?>
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
.password-reset-notice { margin-bottom:1.25rem !important; text-align:left; }
@media (max-width:520px) { .password-reset-page { padding:2rem .75rem; } .password-reset-card { padding:1.4rem !important; } }
</style>

<main class="password-reset-page">
  <section class="padding password-reset-card" aria-labelledby="password-reset-title">
    <div class="password-reset-icon"><i class="key icon"></i></div>
    <h1 id="password-reset-title">새 비밀번호 설정</h1>
    <p class="password-reset-description">사용할 새 비밀번호를 입력해 주세요.</p>
    <?php if (!empty($reset_error)): ?>
      <div class="ui negative message password-reset-notice"><i class="warning circle icon"></i><?php echo htmlentities($reset_error, ENT_QUOTES, 'UTF-8')?></div>
    <?php endif; ?>

    <form class="ui form" action="lostpassword2.php" method="post">
      <input name="user_id" type="hidden" value="<?php echo htmlentities($_SESSION[$OJ_NAME.'_lost_user_id']??'',ENT_QUOTES,'UTF-8')?>">
      <input name="lost_key" type="hidden" value="<?php echo htmlentities($_SESSION[$OJ_NAME.'_lost_key']??'',ENT_QUOTES,'UTF-8')?>">
      <div class="field">
        <label for="new-password">새 비밀번호</label>
        <div class="ui left icon input"><i class="lock icon"></i><input id="new-password" name="new_password" type="password" minlength="6" autocomplete="new-password" required autofocus></div>
      </div>
      <div class="field">
        <label for="confirm-password">새 비밀번호 확인</label>
        <div class="ui left icon input"><i class="lock icon"></i><input id="confirm-password" name="confirm_password" type="password" minlength="6" autocomplete="new-password" required></div>
      </div>
      <button class="ui primary button" name="submit" type="submit"><i class="check icon"></i>비밀번호 재설정</button>
    </form>
  </section>
</main>

<?php include("template/$OJ_TEMPLATE/footer.php");?>
