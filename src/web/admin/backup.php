<?php
require_once("admin-header.php");

if (!isset($_SESSION[$OJ_NAME.'_administrator'])){
    exit(1);
}
?>

<title><?php echo $MSG_BACKUP_DATABASE?></title>
<script>document.body.classList.add('admin-form-page');</script>
<div class="admin-form-shell">
<header class="admin-form-title">
    <h3><?php echo htmlentities($MSG_SYSTEM." - ".$MSG_BACKUP_DATABASE, ENT_QUOTES, 'UTF-8')?></h3>
    <p><?php echo $MSG_HELP_BACKUP_DATABASE?></p>
</header>

<?php
if(isset($_POST['do'])){

require_once(dirname(__FILE__)."/../include/backup.php");

// 파일명에 넣을 백업 시각이다. 윈도우/리눅스 경로에서 문제 없도록 안전한 문자열로 만든다.
$time_str = date('Y-m-d_H-i-s');
$target = $OJ_DATA."/0/".$DB_NAME."_".$time_str.".sql";
$db_file = basename($target);
$dirpath = dirname($target);

// 백업 파일을 저장할 데이터 디렉터리가 없으면 생성한다.
if (!file_exists($dirpath)) {
    mkdir($dirpath, 0755, true);
}

$config = [
    'host' => $DB_HOST,
    'port' => 3306,
    'user' => $DB_USER,
    'password' => $DB_PASS,
    'database' => $DB_NAME,
    'charset' => 'utf8mb4',
    'target' => $target
];

$bak = new DatabaseTool($config);
if (!$bak->backup()) {
    echo "<div class='alert alert-danger' role='alert'>데이터베이스 백업 실패: ".htmlentities($bak->getError(), ENT_QUOTES, 'UTF-8')."</div>";
    exit;
}

/**
 * 지정한 디렉터리의 파일을 ZIP 백업에 재귀적으로 추가한다.
 * data/0은 방금 만든 백업 파일이 있는 위치라 중복 압축을 피하기 위해 건너뛴다.
 */
function addDirToZip($path, $zip) {
    global $OJ_DATA;
    if ($path == "data/0") return;
    $handler = opendir($path);
    if(!$handler) return;

    while (($filename = readdir($handler)) !== false) {
        if ($filename != "." && $filename != "..") {
            $fullpath = realpath(rtrim($path,'/')."/".$filename);
                if(file_exists($fullpath)){
                    if (is_dir($fullpath)) {
                        addDirToZip($fullpath, $zip);
                    } else {
                        $zip->addFile($fullpath, $fullpath); // 원래 경로 구조를 유지해 복구 시 위치를 알 수 있게 한다.
                    }
                }
        }
    }
    closedir($handler);
}

// SQL 백업과 채점 데이터/업로드 파일을 하나의 ZIP 파일로 묶는다.
$ztar = dirname($target)."/backup_".$time_str.".zip";
$zip = new ZipArchive();

if ($zip->open($ztar, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    chdir(dirname($OJ_DATA));
    if (is_dir("data"))
        addDirToZip("data", $zip); // 채점 테스트 데이터
    chdir(realpath(dirname(dirname(__FILE__))));
    if ($OJ_SaaS_ENABLE && $DOMAIN != $domain) {
        if (is_dir("upload/$domain"))
            addDirToZip(realpath("upload/$domain"), $zip); // SaaS 서브도메인별 문제 이미지
    } else {
        if (is_dir("upload/"))
            addDirToZip(realpath("upload"), $zip); // 문제 이미지
    }
    chdir($OJ_DATA."/0");
    if (file_exists($db_file)) {
        $zip->addFile($db_file);
    }

    if (!$zip->close()) {
        echo "<div class='alert alert-danger' role='alert'>ZIP 파일 생성 실패. 대상 디렉터리 권한을 확인하세요: ".htmlentities($ztar, ENT_QUOTES, 'UTF-8')."</div>";
        exit;
    }
} else {
    echo "<div class='alert alert-danger' role='alert'>ZIP 파일을 만들 수 없습니다: ".htmlentities($ztar, ENT_QUOTES, 'UTF-8')."</div>";
    exit;
}

?>
<div class="alert alert-success" role="status">백업을 완료했습니다: <?php echo htmlentities(basename($ztar), ENT_QUOTES, 'UTF-8')?></div>

<?php
}else{
?>
<p class="admin-field-help">백업에는 시간이 걸릴 수 있습니다. 완료될 때까지 페이지를 닫지 마세요.</p>
<form method="post" action="backup.php" class="admin-card">
    <div class="admin-form-actions">
        <input type="submit" class="btn btn-primary" name="do" value="<?php echo $MSG_BACKUP_DATABASE ?>">
    </div>
</form>

<?php
}
?>

<div class="admin-form-actions">
    <button type="button" class="btn btn-default" onclick="phpfm(0)">백업 파일 보기</button>
</div>
<script src='../template/bs3/jquery.min.js'></script>
<script>
// 백업 저장 위치(data/0)를 내장 파일 관리자 화면으로 연다.
function phpfm(pid){
    $.post("phpfm.php",{'frame':3,'pid':pid,'pass':''},function(data,status){
        if(status=="success"){
            document.location.href="phpfm.php?frame=3&pid="+pid;
        }
    });
}
</script>
</div>
