<?php 
  require_once("../include/db_info.inc.php");
  require_once("admin-header.php");

  if (!(isset($_SESSION[$OJ_NAME.'_'.'administrator']) || isset($_SESSION[$OJ_NAME.'_'.'problem_importer']) )) {
    exit(1);
  }

  echo "<center><h3>".$MSG_PROBLEM."-".$MSG_EXPORT."</h3></center>";

?>
<html>
<head>
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Cache-Control" content="no-cache">
  <meta http-equiv="Content-Language" content="zh-cn">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>Problem Export</title>
</head>
<hr>

<body leftmargin="30" >
  <div class="container">
    <br><br>
    - 문제 XML 내보내기<br><br>
    <form class="form-inline" action="problem_export_xml.php" method="post"  onsubmit="setTimeout('location.reload()',3000)" >
      <div class="form-group">
        <label>1) 연속된 문제 번호:</label>
        <input class="form-control" name="start" type="text" placeholder="1001">
      </div>
      <div class="form-group">
        <label> ~ </label>
        <input class="form-control" name="end" type="text" placeholder="1009">
      </div>
      <br><br>
      <div class="form-group">
        <label>2) 개별 문제 번호:</label>
        <input class="form-control" name="in" type="text" placeholder="1001,1003,1005, ... ">
      </div>
      <br><br>
     <div class="form-group">
        <label>3) 원격 채점용 문제를 내보낼 때 원격 별칭을 입력하세요. 일반 내보내기에서는 비워 두세요:</label>
        <input class="form-control" name="remote_name" type="text" placeholder="my">
      </div>
      <br><br>

      <center>
      <div class='form-group'>
        <input type="hidden" name="do" value="do">
        <!-- <input type="submit" name="submit" value="Export to XML Script"> -->
        <button class='btn btn-default btn-sm' type=submit>XML 파일 다운로드</button>
      </div>
      </center>

      <?php require_once("../include/set_post_key.php");?>
    </form>

    <br><br>
    <!--
    * from-to will working if empty IN <br>
    * if using IN,from-to will not working.<br>
    * IN can go with "," seperated problem_ids like [1000,1020]
    -->
    - 개별 문제 번호가 비어 있으면 연속된 문제 번호를 적용합니다.<br>
    - 연속된 문제 번호가 비어 있으면 개별 문제 번호를 적용합니다.
  </div>

</body>
</html>

