<?php
  // 접근이 막힌 화면에서 쓰는 공용 안내 화면. 부르기 전에 아래 값을 정한다.
  // $gs_notice_title, $gs_notice_detail, $gs_notice_back_url, $gs_notice_back_label, $gs_notice_icon("clock" 또는 "lock")
  if (!isset($gs_notice_icon)) $gs_notice_icon = "lock";
  $show_title = "$gs_notice_title - $OJ_NAME";
  include(dirname(__FILE__) . "/header.php");
?>
<style>
  .ui.segment.gs-notice { max-width: 560px; margin: 48px auto; padding: 48px 24px; text-align: center; box-sizing: border-box; }
  .gs-notice svg { width: 56px; height: 56px; color: #db2828; }
  .gs-notice h2 { margin: 18px 0 10px; font-size: 1.6em; font-weight: 600; word-break: keep-all; }
  .gs-notice p { margin: 0 auto 28px; max-width: 420px; font-size: 1.05em; color: rgba(0, 0, 0, .6); line-height: 1.7; word-break: keep-all; }
  @media (max-width: 480px) {
    .ui.segment.gs-notice { margin: 24px auto; padding: 36px 16px; }
    .gs-notice h2 { font-size: 1.35em; }
  }
</style>
<div class="ui segment gs-notice">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <?php if ($gs_notice_icon === "clock") { ?>
    <circle cx="12" cy="12" r="10"></circle>
    <polyline points="12 6 12 12 16 14"></polyline>
    <?php } else { ?>
    <rect x="4" y="11" width="16" height="10" rx="2"></rect>
    <path d="M8 11V7a4 4 0 0 1 8 0v4"></path>
    <?php } ?>
  </svg>
  <h2><?php echo $gs_notice_title; ?></h2>
  <p><?php echo $gs_notice_detail; ?></p>
  <a class="ui primary button" href="<?php echo $gs_notice_back_url; ?>"><?php echo $gs_notice_back_label; ?></a>
</div>
<?php include(dirname(__FILE__) . "/footer.php"); ?>
