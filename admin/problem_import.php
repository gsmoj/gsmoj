<?php 
  require_once("../include/db_info.inc.php");
  require_once("admin-header.php");

  if (!(isset($_SESSION[$OJ_NAME.'_'.'administrator']) || isset($_SESSION[$OJ_NAME.'_'.'contest_creator']) || isset($_SESSION[$OJ_NAME.'_problem_importer']))) {
    exit(1);
  }

  function writable($path) {
    $ret = false;
    $fp = fopen($path."/testifwritable.tst","w");
    $ret = !($fp===false);

    if($fp!=false) {
        fclose($fp);
        unlink($path."/testifwritable.tst");
    }
    return $ret;
  }

  $maxfile = min(ini_get("upload_max_filesize"), ini_get("post_max_size"));
  echo "<center><h3>".$MSG_PROBLEM."-".$MSG_IMPORT."</h3></center>";
?>

<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>Problem Import</title>
  <style>
    /* 进度条浮窗样式 */
    #upload-p-window {
        display: none; position: fixed; top: 30%; left: 50%; transform: translateX(-50%);
        width: 450px; background: #fff; padding: 20px; border: 1px solid #ddd;
        border-radius: 8px; box-shadow: 0 5px 20px rgba(0,0,0,0.3); z-index: 10000;
    }
    .progress { height: 20px; box-shadow: inset 0 1px 2px rgba(0,0,0,.1); background-color: #f5f5f5; border-radius: 4px; overflow: hidden; }
    .progress-bar-success { background-color: #5cb85c; color: #fff; text-align: center; transition: width .6s ease; }
  </style>
</head>
<body leftmargin="30">

  <div id="upload-p-window">
      <h4 id="p-title" style="margin-top:0">파일을 업로드하고 있습니다...</h4>
      <div class="progress">
          <div id="p-bar" class="progress-bar-success" role="progressbar" style="width: 0%;">0%</div>
      </div>
      <div id="p-status" style="font-size:12px; color:#666; text-align:center;">데이터를 준비하고 있습니다...</div>
  </div>

  <div class="container">
    <?php 
    $show_form = true;
    if (!writable($OJ_DATA)) {
      echo "<div class='alert alert-danger'>권한 설정에 문제가 있습니다. 다음 명령을 실행하세요:<br><b>chmod 775 -R $OJ_DATA && chgrp -R ".get_current_user()." $OJ_DATA</b></div>";
      $show_form = false;
    }
    if (!file_exists("../upload")) mkdir("../upload");
    if (!writable("../upload")) {
      echo "<div class='alert alert-danger'>`../upload` 디렉터리에 쓸 수 없습니다. 다음 명령을 실행하세요: <b>chmod 770 ../upload</b></div>";
      $show_form = false;
    }

    if ($show_form) { ?>
    
    - 문제 가져오기 <b>FPS(.xml)/ZIP(.xml 포함)</b> <br><br>
    <form class='form-inline aj-up' action='problem_import_xml.php' method=post enctype="multipart/form-data">
      <div class='form-group'>
        <input class='form-control' type=file name=fps required>
        편집자:<input class='form-control' type=text name='user_id' value='<?php echo htmlentities($_SESSION[$OJ_NAME.'_user_id'])?>' >
        <button class='btn btn-success btn-sm' type=submit>HUSTOJ에 업로드</button>
      </div>
      <?php require("../include/set_post_key.php");?>
    </form> <hr>

    - QDUOJ - json - zip<br>
    <form class='form-inline aj-up' action='problem_import_qduoj.php' method=post enctype="multipart/form-data">
      <div class='form-group'>
        <input class='form-control' type=file name=fps required>
        <button class='btn btn-info btn-sm' type=submit>HUSTOJ에 업로드</button>
      </div>
      <?php require("../include/set_post_key.php");?>
    </form> <hr>

    - SYZOJ - zip<br><br>
    <form class='form-inline aj-up' action='problem_import_syzoj.php' method=post enctype="multipart/form-data">
      <div class='form-group'>
        <input class='form-control' type=file name=fps required>
        <button class='btn btn-warning btn-sm' type=submit>HUSTOJ에 업로드</button>
      </div>
      <?php require("../include/set_post_key.php");?>
    </form> <hr>

    - HydroOJ - zip<br><br>
    <form class='form-inline aj-up' action='problem_import_hydro.php' method=post enctype="multipart/form-data">
      <div class='form-group'>
        <input class='form-control' type=file name=fps required>
        <button class='btn btn-danger btn-sm' type=submit>HUSTOJ에 업로드</button>
      </div>
      <?php require("../include/set_post_key.php");?>
    </form> <hr>

    - HOJ - zip<br><br>
    <form class='form-inline aj-up' action='problem_import_hoj.php' method=post enctype="multipart/form-data">
      <div class='form-group'>
        <input class='form-control' type=file name=fps required>
        <button class='btn btn-info btn-sm' type=submit>HUSTOJ에 업로드</button>
      </div>
      <?php require("../include/set_post_key.php");?>
    </form> <hr>

    - TYVJ - zip<br><br>
    <form class='form-inline aj-up' action='problem_import_tyvj.php' method=post enctype="multipart/form-data">
      <div class='form-group'>
        <input class='form-control' type=file name=fps required>
        <button class='btn btn-primary btn-sm' type=submit>HUSTOJ에 업로드</button>
      </div>
      <?php require("../include/set_post_key.php");?>
    </form> <hr>

    - Markdown - zip<br>
    <form class='form-inline aj-up' action='problem_import_md.php' method=post enctype="multipart/form-data">
      <div class='form-group'>
        <input class='form-control' type=file name=fps required>
        <button class='btn btn-warning btn-sm' type=submit>HUSTOJ에 업로드</button>
      </div>
      <?php require("../include/set_post_key.php");?>
    </form>

    <?php } ?>

    <br><br>
    <?php if ($OJ_LANG == "cn") { ?>
    무료 문제는 <a href="https://github.com/zhblue/freeproblemset/tree/master/fps-examples" target="_blank">다운로드</a>할 수 있습니다.<br>
    더 많은 문제는 <a href="http://tk.hustoj.com/problemset.php?search=free" target="_blank">TK 문제 은행 무료 영역</a>에서 확인하세요.
    <?php } ?>
    <br><br>
    - FPS 데이터를 가져오려면 파일 크기가 [<?php echo $maxfile?>]보다 작아야 합니다.<br>
    - PHP 설정 파일은 <span style='color:blue'> find /etc -name php.ini </span> 명령으로 찾을 수 있습니다.
  </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 监听所有带有 aj-up 类的表单
    document.querySelectorAll('form.aj-up').forEach(form => {
        form.onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const xhr = new XMLHttpRequest();
            const win = document.getElementById('upload-p-window');
            const bar = document.getElementById('p-bar');
            const stat = document.getElementById('p-status');

            win.style.display = 'block';
            
            xhr.upload.onprogress = function(ev) {
                if (ev.lengthComputable) {
                    let percent = Math.round((ev.loaded / ev.total) * 100);
                    bar.style.width = percent + '%';
                    bar.innerText = percent + '%';
                    stat.innerText = percent < 100 ? '파일을 전송하고 있습니다...' : '전송이 완료되었습니다. 압축을 풀고 데이터베이스에 가져오는 중이니 잠시만 기다려 주세요...';
                }
            };

            xhr.onload = function() {
                if (xhr.status === 200) {
                    // 导入完成，直接显示结果
                    document.body.innerHTML = xhr.responseText;
                } else {
                    alert('가져오기 중 오류가 발생했습니다. 상태 코드: ' + xhr.status);
                    win.style.display = 'none';
                }
            };

            xhr.open('POST', this.action, true);
            xhr.send(formData);
        };
    });
});
</script>
</body>
</html>
