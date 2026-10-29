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
  $page_heading = $MSG_PROBLEM." - ".$MSG_IMPORT;
?>

<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title><?php echo htmlentities($page_heading, ENT_QUOTES, 'UTF-8')?></title>
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
<body>
  <script>document.body.classList.add('admin-form-page');</script>

  <div id="upload-p-window">
      <h4 id="p-title" style="margin-top:0">파일을 업로드하고 있습니다...</h4>
      <div class="progress">
          <div id="p-bar" class="progress-bar-success" role="progressbar" style="width: 0%;">0%</div>
      </div>
      <div id="p-status" style="font-size:12px; color:#666; text-align:center;">데이터를 준비하고 있습니다...</div>
  </div>

  <div class="admin-form-shell">
    <header class="admin-form-title">
      <h3><?php echo htmlentities($page_heading, ENT_QUOTES, 'UTF-8')?></h3>
      <p>다른 온라인 저지에서 내보낸 문제 파일을 형식에 맞게 업로드합니다.</p>
    </header>
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
    
    <section class="admin-import-card">
    <h4>FPS (.xml / .xml을 담은 .zip)</h4>
    <form class='form-inline aj-up' action='problem_import_xml.php' method=post enctype="multipart/form-data">
      <div class='form-group'>
        <input class='form-control' type=file name=fps required>
        <label class='admin-field-label' for='import-user-id' style='margin:0'>편집자</label><input class='form-control' id='import-user-id' type=text name='user_id' value='<?php echo htmlentities($_SESSION[$OJ_NAME.'_user_id'])?>' >
        <button class='btn btn-primary' type=submit>업로드</button>
      </div>
      <?php require("../include/set_post_key.php");?>
    </form>
    </section>

    <section class="admin-import-card">
    <h4>QDUOJ (json .zip)</h4>
    <form class='form-inline aj-up' action='problem_import_qduoj.php' method=post enctype="multipart/form-data">
      <div class='form-group'>
        <input class='form-control' type=file name=fps required>
        <button class='btn btn-primary' type=submit>업로드</button>
      </div>
      <?php require("../include/set_post_key.php");?>
    </form>
    </section>

    <section class="admin-import-card">
    <h4>SYZOJ (.zip)</h4>
    <form class='form-inline aj-up' action='problem_import_syzoj.php' method=post enctype="multipart/form-data">
      <div class='form-group'>
        <input class='form-control' type=file name=fps required>
        <button class='btn btn-primary' type=submit>업로드</button>
      </div>
      <?php require("../include/set_post_key.php");?>
    </form>
    </section>

    <section class="admin-import-card">
    <h4>HydroOJ (.zip)</h4>
    <form class='form-inline aj-up' action='problem_import_hydro.php' method=post enctype="multipart/form-data">
      <div class='form-group'>
        <input class='form-control' type=file name=fps required>
        <button class='btn btn-primary' type=submit>업로드</button>
      </div>
      <?php require("../include/set_post_key.php");?>
    </form>
    </section>

    <section class="admin-import-card">
    <h4>HOJ (.zip)</h4>
    <form class='form-inline aj-up' action='problem_import_hoj.php' method=post enctype="multipart/form-data">
      <div class='form-group'>
        <input class='form-control' type=file name=fps required>
        <button class='btn btn-primary' type=submit>업로드</button>
      </div>
      <?php require("../include/set_post_key.php");?>
    </form>
    </section>

    <section class="admin-import-card">
    <h4>TYVJ (.zip)</h4>
    <form class='form-inline aj-up' action='problem_import_tyvj.php' method=post enctype="multipart/form-data">
      <div class='form-group'>
        <input class='form-control' type=file name=fps required>
        <button class='btn btn-primary' type=submit>업로드</button>
      </div>
      <?php require("../include/set_post_key.php");?>
    </form>
    </section>

    <section class="admin-import-card">
    <h4>Markdown (.zip)</h4>
    <form class='form-inline aj-up' action='problem_import_md.php' method=post enctype="multipart/form-data">
      <div class='form-group'>
        <input class='form-control' type=file name=fps required>
        <button class='btn btn-primary' type=submit>업로드</button>
      </div>
      <?php require("../include/set_post_key.php");?>
    </form>
    </section>

    <?php } ?>

    <?php if ($OJ_LANG == "cn") { ?>
    <p class="admin-field-help">
    무료 문제는 <a href="https://github.com/zhblue/freeproblemset/tree/master/fps-examples" target="_blank">다운로드</a>할 수 있습니다.<br>
    더 많은 문제는 <a href="http://tk.hustoj.com/problemset.php?search=free" target="_blank">TK 문제 은행 무료 영역</a>에서 확인하세요.
    </p>
    <?php } ?>
    <ul class="admin-field-help admin-help-list">
      <li>FPS 데이터를 가져오려면 파일 크기가 [<?php echo $maxfile?>]보다 작아야 합니다.</li>
      <li>PHP 설정 파일은 <code>find /etc -name php.ini</code> 명령으로 찾을 수 있습니다.</li>
    </ul>
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
