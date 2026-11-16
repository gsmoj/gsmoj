<?php
  // 개인 제한시간이 지나거나 끝난 수행평가에 들어왔을 때 보여 주는 안내 화면. contest-check.php에서 불러온다.
  // 화면 모양은 공용 안내 화면(notice.php)을 쓴다.
  $gs_notice_icon = "clock";
  $gs_notice_title = $MSG_CONTEST_TIME_OVER;
  $gs_notice_detail = $MSG_CONTEST_TIME_OVER_DETAIL;
  $gs_notice_back_url = $gs_time_over_back_url;
  $gs_notice_back_label = $gs_time_over_back_label;
  require(dirname(__FILE__) . "/notice.php");
