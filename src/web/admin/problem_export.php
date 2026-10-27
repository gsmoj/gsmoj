<?php 
  require_once("../include/db_info.inc.php");
  require_once("admin-header.php");

  if (!(isset($_SESSION[$OJ_NAME.'_'.'administrator']) || isset($_SESSION[$OJ_NAME.'_'.'problem_importer']) )) {
    exit(1);
  }

  $page_heading = $MSG_PROBLEM." - ".$MSG_EXPORT;

?>
<html>
<head>
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Cache-Control" content="no-cache">
  <meta http-equiv="Content-Language" content="ko">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title><?php echo htmlentities($page_heading, ENT_QUOTES, 'UTF-8')?></title>
</head>

<body>
  <script>document.body.classList.add('admin-form-page');</script>
  <div class="admin-form-shell">
    <header class="admin-form-title">
      <h3><?php echo htmlentities($page_heading, ENT_QUOTES, 'UTF-8')?></h3>
      <p>문제를 FPS XML 파일로 내보냅니다.</p>
    </header>
    <form class="admin-export-form" action="problem_export_xml.php" method="post"  onsubmit="setTimeout('location.reload()',3000)" >
      <div class="admin-export-field">
        <label class="admin-field-label" for="export-start">1) 연속된 문제 번호</label>
        <div class="admin-export-range">
          <input class="form-control" id="export-start" name="start" type="text" placeholder="1001">
          <span>~</span>
          <input class="form-control" name="end" type="text" placeholder="1009" aria-label="끝 문제 번호">
        </div>
      </div>
      <div class="admin-export-field">
        <label class="admin-field-label" for="export-in">2) 개별 문제 번호</label>
        <input class="form-control" id="export-in" name="in" type="text" placeholder="1001,1003,1005, ... ">
      </div>
      <div class="admin-export-field">
        <label class="admin-field-label" for="export-remote">3) 원격 별칭</label>
        <input class="form-control" id="export-remote" name="remote_name" type="text" placeholder="my">
        <p class="admin-field-help">원격 채점용 문제를 내보낼 때만 입력하고, 일반 내보내기에서는 비워 두세요.</p>
      </div>

      <!--
      * from-to will working if empty IN <br>
      * if using IN,from-to will not working.<br>
      * IN can go with "," seperated problem_ids like [1000,1020]
      -->
      <ul class="admin-field-help admin-help-list">
        <li>개별 문제 번호가 비어 있으면 연속된 문제 번호를 적용합니다.</li>
        <li>연속된 문제 번호가 비어 있으면 개별 문제 번호를 적용합니다.</li>
      </ul>

      <input type="hidden" name="do" value="do">
      <!-- <input type="submit" name="submit" value="Export to XML Script"> -->
      <?php require_once("../include/set_post_key.php");?>
      <div class="admin-form-actions">
        <button class='btn btn-primary' type=submit>XML 파일 다운로드</button>
      </div>
    </form>
  </div>

</body>
</html>

