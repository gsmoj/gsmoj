<?php 
  require_once("../include/db_info.inc.php");
  require_once("admin-header.php");

  // 실제 업로드를 처리하는 problem_import_*.php가 관리자와 problem_importer만 허용하므로 화면도 같은 권한으로 맞춘다.
  if (!(isset($_SESSION[$OJ_NAME.'_'.'administrator']) || isset($_SESSION[$OJ_NAME.'_problem_importer']))) {
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
</head>
<body>
  <script>document.body.classList.add('admin-form-page');</script>

  <div id="upload-p-window" class="pm-upload-backdrop" role="dialog" aria-modal="true" aria-labelledby="p-title">
    <div class="pm-upload-window">
      <h4 id="p-title">파일을 업로드하고 있습니다...</h4>
      <div class="pm-progress"><div id="p-bar" class="pm-progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div></div>
      <div class="pm-upload-meta"><span id="p-status">데이터를 준비하고 있습니다...</span><b id="p-percent">0%</b></div>
    </div>
  </div>

  <div class="admin-form-shell pm-page">
    <header class="admin-form-title">
      <div>
        <h3>문제 가져오기</h3>
        <p>문제 파일을 올려 여러 문제를 한 번에 등록합니다. 가져온 문제는 비공개 상태로 만들어집니다.</p>
      </div>
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

    <div class="pm-note">
      <span>업로드할 수 있는 최대 파일 크기: <b><?php echo htmlentities($maxfile, ENT_QUOTES, 'UTF-8')?></b></span>
      <span>더 큰 파일은 서버의 PHP 설정(<code>find /etc -name php.ini</code>)에서 늘릴 수 있습니다.</span>
    </div>

    <section class="pm-section">
      <h4>GSMOJ·HUSTOJ 형식</h4>
      <p class="pm-section-desc">이 사이트나 다른 HUSTOJ에서 내보낸 파일, 직접 작성한 Markdown 문제를 가져옵니다.</p>
      <div class="pm-grid">
      <section class="pm-card">
        <div class="pm-card-head"><h4>FPS</h4><span class="pm-tag">.xml / .zip</span></div>
        <div class="pm-card-body">
          <p>문제 내보내기로 받은 FPS XML 파일 또는 XML 여러 개를 묶은 zip 파일입니다.</p>
          <form class='pm-inline aj-up' action='problem_import_xml.php' method=post enctype="multipart/form-data">
            <div class='pm-inline-row'><label class='pm-inline-label' for='import-user-id'>편집자</label><input class='form-control' id='import-user-id' type=text name='user_id' value='<?php echo htmlentities($_SESSION[$OJ_NAME.'_user_id'])?>'></div>
            <input class='form-control' type=file name=fps required aria-label='FPS 파일 선택'>
            <button class='btn btn-primary' type=submit>업로드</button>
            <?php require("../include/set_post_key.php");?>
          </form>
        </div>
      </section>
      <section class="pm-card">
        <div class="pm-card-head"><h4>Markdown</h4><span class="pm-tag">.zip</span></div>
        <div class="pm-card-body">
          <p>문제마다 <code>이름.md</code>(첫 줄 <code># 제목</code>), 선택으로 <code>이름.cpp</code>(정답)와 <code>이름.zip</code>(채점 데이터)을 묶은 zip 파일입니다.</p>
          <form class='pm-inline aj-up' action='problem_import_md.php' method=post enctype="multipart/form-data">
            <input class='form-control' type=file name=fps required aria-label='Markdown 파일 선택'>
            <button class='btn btn-primary' type=submit>업로드</button>
            <?php require("../include/set_post_key.php");?>
          </form>
        </div>
      </section>
      </div>
    </section>

    <section class="pm-section">
      <h4>다른 온라인 저지 형식</h4>
      <p class="pm-section-desc">다른 온라인 저지에서 내보낸 문제 파일을 그대로 가져옵니다.</p>
      <div class="pm-grid">
      <section class="pm-card">
        <div class="pm-card-head"><h4>QDUOJ</h4><span class="pm-tag">json .zip</span></div>
        <div class="pm-card-body">
          <p>QDUOJ에서 내보낸 json 문제 zip 파일입니다.</p>
          <form class='pm-inline aj-up' action='problem_import_qduoj.php' method=post enctype="multipart/form-data">
            <input class='form-control' type=file name=fps required aria-label='QDUOJ 파일 선택'>
            <button class='btn btn-primary' type=submit>업로드</button>
            <?php require("../include/set_post_key.php");?>
          </form>
        </div>
      </section>
      <section class="pm-card">
        <div class="pm-card-head"><h4>SYZOJ</h4><span class="pm-tag">.zip</span></div>
        <div class="pm-card-body">
          <p>SYZOJ에서 내보낸 문제 zip 파일입니다.</p>
          <form class='pm-inline aj-up' action='problem_import_syzoj.php' method=post enctype="multipart/form-data">
            <input class='form-control' type=file name=fps required aria-label='SYZOJ 파일 선택'>
            <button class='btn btn-primary' type=submit>업로드</button>
            <?php require("../include/set_post_key.php");?>
          </form>
        </div>
      </section>
      <section class="pm-card">
        <div class="pm-card-head"><h4>HydroOJ</h4><span class="pm-tag">.zip</span></div>
        <div class="pm-card-body">
          <p>HydroOJ에서 내보낸 문제 zip 파일입니다.</p>
          <form class='pm-inline aj-up' action='problem_import_hydro.php' method=post enctype="multipart/form-data">
            <input class='form-control' type=file name=fps required aria-label='HydroOJ 파일 선택'>
            <button class='btn btn-primary' type=submit>업로드</button>
            <?php require("../include/set_post_key.php");?>
          </form>
        </div>
      </section>
      <section class="pm-card">
        <div class="pm-card-head"><h4>HOJ</h4><span class="pm-tag">.zip</span></div>
        <div class="pm-card-body">
          <p>HOJ에서 내보낸 문제 zip 파일입니다.</p>
          <form class='pm-inline aj-up' action='problem_import_hoj.php' method=post enctype="multipart/form-data">
            <input class='form-control' type=file name=fps required aria-label='HOJ 파일 선택'>
            <button class='btn btn-primary' type=submit>업로드</button>
            <?php require("../include/set_post_key.php");?>
          </form>
        </div>
      </section>
      <section class="pm-card">
        <div class="pm-card-head"><h4>TYVJ</h4><span class="pm-tag">.zip</span></div>
        <div class="pm-card-body">
          <p>TYVJ에서 내보낸 문제 zip 파일입니다.</p>
          <form class='pm-inline aj-up' action='problem_import_tyvj.php' method=post enctype="multipart/form-data">
            <input class='form-control' type=file name=fps required aria-label='TYVJ 파일 선택'>
            <button class='btn btn-primary' type=submit>업로드</button>
            <?php require("../include/set_post_key.php");?>
          </form>
        </div>
      </section>
      </div>
    </section>

    <?php } ?>

    <?php if ($OJ_LANG == "cn") { ?>
    <p class="admin-field-help">
    무료 문제는 <a href="https://github.com/zhblue/freeproblemset/tree/master/fps-examples" target="_blank">다운로드</a>할 수 있습니다.<br>
    더 많은 문제는 <a href="http://tk.hustoj.com/problemset.php?search=free" target="_blank">TK 문제 은행 무료 영역</a>에서 확인하세요.
    </p>
    <?php } ?>
  </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // aj-up 클래스가 붙은 모든 업로드 폼을 진행률 표시와 함께 전송한다.
    document.querySelectorAll('form.aj-up').forEach(form => {
        form.onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const xhr = new XMLHttpRequest();
            const win = document.getElementById('upload-p-window');
            const bar = document.getElementById('p-bar');
            const stat = document.getElementById('p-status');

            const pct = document.getElementById('p-percent');
            win.classList.add('is-open');
            
            xhr.upload.onprogress = function(ev) {
                if (ev.lengthComputable) {
                    let percent = Math.round((ev.loaded / ev.total) * 100);
                    bar.style.width = percent + '%';
                    bar.setAttribute('aria-valuenow', percent);
                    pct.innerText = percent + '%';
                    stat.innerText = percent < 100 ? '파일을 전송하고 있습니다...' : '전송이 완료되었습니다. 압축을 풀고 데이터베이스에 가져오는 중이니 잠시만 기다려 주세요...';
                }
            };

            xhr.onload = function() {
                if (xhr.status === 200) {
                    // 가져오기가 끝나면 결과 화면으로 바꾼다.
                    document.body.innerHTML = xhr.responseText;
                } else {
                    alert('가져오기 중 오류가 발생했습니다. 상태 코드: ' + xhr.status);
                    win.classList.remove('is-open');
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
