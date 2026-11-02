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
  <div class="admin-form-shell pm-page">
    <header class="admin-form-title">
      <div>
        <h3>문제 내보내기</h3>
        <p>문제를 FPS XML 파일로 내려받습니다. 받은 파일은 문제 가져오기의 FPS 항목으로 다시 올릴 수 있습니다.</p>
      </div>
    </header>
    <form class="admin-export-form" action="problem_export_xml.php" method="post"  onsubmit="setTimeout('location.reload()',3000)" >
      <section class="pm-section">
        <h4>내보낼 문제 고르기</h4>
        <p class="pm-section-desc">둘 중 한 가지만 입력하세요. 개별 문제 번호를 입력하면 연속된 문제 번호는 무시됩니다.</p>
        <div class="pm-export-options">
          <div class="pm-card">
            <div class="pm-card-head"><h4><label for="export-start" style="margin:0">연속된 문제 번호</label></h4><span class="pm-tag">범위</span></div>
            <div class="pm-card-body">
              <p>시작 번호부터 끝 번호까지 모두 내보냅니다.</p>
              <div class="pm-range">
                <input class="form-control" id="export-start" name="start" type="text" placeholder="1001" aria-label="시작 문제 번호">
                <span>~</span>
                <input class="form-control" name="end" type="text" placeholder="1009" aria-label="끝 문제 번호">
              </div>
            </div>
          </div>
          <div class="pm-card">
            <div class="pm-card-head"><h4><label for="export-in" style="margin:0">개별 문제 번호</label></h4><span class="pm-tag">골라서</span></div>
            <div class="pm-card-body">
              <p>쉼표(,)로 구분해 원하는 문제만 내보냅니다.</p>
              <input class="form-control" id="export-in" name="in" type="text" placeholder="1001,1003,1005, ... ">
            </div>
          </div>
        </div>
      </section>

      <section class="pm-section">
        <h4><label for="export-remote" style="margin:0">원격 별칭</label> <span class="pm-section-desc" style="font-weight:500">(선택)</span></h4>
        <p class="pm-section-desc">원격 채점용 문제를 내보낼 때만 입력하고, 일반 내보내기에서는 비워 두세요.</p>
        <input class="form-control" id="export-remote" name="remote_name" type="text" placeholder="my" style="max-width:320px">
      </section>

      <!--
      * from-to will working if empty IN <br>
      * if using IN,from-to will not working.<br>
      * IN can go with "," seperated problem_ids like [1000,1020]
      -->

      <input type="hidden" name="do" value="do">
      <!-- <input type="submit" name="submit" value="Export to XML Script"> -->
      <?php require_once("../include/set_post_key.php");?>
      <div class="pm-actions-row">
        <button class='btn btn-primary' type=submit>XML 파일 다운로드</button>
      </div>
    </form>
  </div>

</body>
</html>

