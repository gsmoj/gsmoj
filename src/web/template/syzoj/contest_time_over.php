<?php
  // 개인 제한시간이 지난 대회·수행평가에 들어왔을 때 보여 주는 안내 화면. contest-check.php에서 불러온다.
  $show_title = "$MSG_CONTEST_TIME_OVER - $OJ_NAME";
  include(dirname(__FILE__) . "/header.php");
?>
<style>
  .ui.segment.contest-time-over { max-width: 560px; margin: 48px auto; padding: 48px 24px; text-align: center; box-sizing: border-box; }
  .contest-time-over svg { width: 56px; height: 56px; color: #db2828; }
  .contest-time-over h2 { margin: 18px 0 10px; font-size: 1.6em; font-weight: 600; word-break: keep-all; }
  .contest-time-over p { margin: 0 auto 28px; max-width: 420px; font-size: 1.05em; color: rgba(0, 0, 0, .6); line-height: 1.7; word-break: keep-all; }
  @media (max-width: 480px) {
    .ui.segment.contest-time-over { margin: 24px auto; padding: 36px 16px; }
    .contest-time-over h2 { font-size: 1.35em; }
  }
</style>
<div class="ui segment contest-time-over">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <circle cx="12" cy="12" r="10"></circle>
    <polyline points="12 6 12 12 16 14"></polyline>
  </svg>
  <h2><?php echo $MSG_CONTEST_TIME_OVER; ?></h2>
  <p><?php echo $MSG_CONTEST_TIME_OVER_DETAIL; ?></p>
  <a class="ui primary button" href="<?php echo $gs_time_over_back_url; ?>"><?php echo $gs_time_over_back_label; ?></a>
</div>
<?php include(dirname(__FILE__) . "/footer.php"); ?>
