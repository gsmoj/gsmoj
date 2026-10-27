<?php
// 문제별 채점 데이터(입출력 파일) 관리 페이지.
// 기존 범용 파일 관리자(phpfm.php) 대신 .in/.out 쌍을 한눈에 보고 업로드·편집·삭제할 수 있게 한다.
// 같은 파일이 화면 출력과 JSON 작업 요청(action=...)을 함께 처리한다.
require_once("../include/db_info.inc.php");
require_once("../include/my_func.inc.php");
if (file_exists("../lang/$OJ_LANG.php")) require_once("../lang/$OJ_LANG.php");

const TD_EDIT_LIMIT = 2097152;        // 브라우저에서 바로 편집할 수 있는 최대 크기 (2MB)
const TD_ZIP_LIMIT = 1073741824;      // ZIP 하나에서 풀 수 있는 최대 총 크기 (1GB)

function td_fail($code, $message) {
    http_response_code($code);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(array("ok" => false, "error" => $message), JSON_UNESCAPED_UNICODE);
    exit;
}
function td_json($data) {
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(array_merge(array("ok" => true), $data), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

// 권한: phpfm.php와 같이 관리자 또는 해당 문제 권한이 있는 문제 편집자만 접근한다.
$td_is_admin = isset($_SESSION[$OJ_NAME.'_'.'administrator']);
if (!($td_is_admin || isset($_SESSION[$OJ_NAME.'_'.'problem_editor']) || isset($_SESSION[$OJ_NAME.'_'.'teacher']))) {
    echo "<a href='../loginpage.php'>".(isset($MSG_Login) ? $MSG_Login : "Please Login First!")."</a>";
    exit(1);
}
$pid = isset($_REQUEST['pid']) ? intval($_REQUEST['pid']) : 0;
$td_action = isset($_GET['action']) ? $_GET['action'] : "";
if ($pid <= 0) {
    if ($td_action !== "") td_fail(400, "문제 번호가 올바르지 않습니다.");
    header("Location: problem_list.php");
    exit;
}
if (!($td_is_admin || isset($_SESSION[$OJ_NAME.'_'."p".$pid]))) {
    if ($td_action !== "") td_fail(403, "이 문제의 채점 데이터를 관리할 권한이 없습니다.");
    echo "이 문제의 채점 데이터를 관리할 권한이 없습니다.";
    exit(1);
}
$td_problem = pdo_query("select title from problem where problem_id=?", $pid);
if (empty($td_problem)) {
    if ($td_action !== "") td_fail(404, "문제를 찾을 수 없습니다.");
    echo "문제를 찾을 수 없습니다.";
    exit(1);
}
$td_title = $td_problem[0][0];

$td_dir = rtrim($OJ_DATA, "/")."/".$pid;
if (!is_dir($td_dir)) @mkdir($td_dir, 0775, true);

// 쓰기 요청 위조를 막기 위한 페이지 토큰. 여러 번 연속 요청(업로드 등)을 보내므로 일회용 csrf 대신 세션 토큰을 쓴다.
if (empty($_SESSION[$OJ_NAME.'_'.'testdata_token'])) {
    $_SESSION[$OJ_NAME.'_'.'testdata_token'] = bin2hex(random_bytes(16));
}
$td_token = $_SESSION[$OJ_NAME.'_'.'testdata_token'];

// 파일 이름은 데이터 폴더 바로 아래의 평범한 이름만 허용한다. (하위 경로, 숨김 파일 차단)
function td_valid_name($name) {
    return is_string($name) && strlen($name) <= 128 && preg_match('/^[A-Za-z0-9][A-Za-z0-9._+-]*$/', $name);
}
function td_path($name) {
    global $td_dir;
    if (!td_valid_name($name)) td_fail(400, "허용되지 않는 파일 이름입니다: ".$name);
    return $td_dir."/".$name;
}
function td_existing_path($name) {
    $path = td_path($name);
    if (!is_file($path) || is_link($path)) td_fail(404, "파일을 찾을 수 없습니다: ".$name);
    return $path;
}
function td_preview($path, $size) {
    if ($size == 0) return array("text" => "", "binary" => false);
    $fp = @fopen($path, "rb");
    if (!$fp) return array("text" => "", "binary" => false);
    $head = fread($fp, 400);
    fclose($fp);
    if (strpos($head, "\0") !== false) return array("text" => "", "binary" => true);
    if (!mb_check_encoding($head, "UTF-8")) $head = mb_convert_encoding($head, "UTF-8", "UTF-8");
    return array("text" => $head, "binary" => false);
}
function td_list() {
    global $td_dir;
    $files = array();
    $dirs = array();
    foreach (scandir($td_dir) as $name) {
        if ($name === "." || $name === "..") continue;
        $path = $td_dir."/".$name;
        if (is_dir($path)) { $dirs[] = $name; continue; }
        if (!is_file($path) || is_link($path)) continue;
        $size = filesize($path);
        $preview = td_preview($path, $size);
        $files[] = array(
            "name" => $name,
            "size" => $size,
            "mtime" => filemtime($path),
            "preview" => $preview["text"],
            "binary" => $preview["binary"],
            "valid" => td_valid_name($name),
        );
    }
    return array("files" => $files, "dirs" => $dirs);
}

if ($td_action !== "") {
    $td_method = $_SERVER['REQUEST_METHOD'];
    if ($td_action === "download" || $td_action === "zip") {
        if ($td_action === "download") {
            $path = td_existing_path(isset($_GET['name']) ? $_GET['name'] : "");
            header("Content-Type: application/octet-stream");
            header("Content-Length: ".filesize($path));
            header("Content-Disposition: attachment; filename=\"".basename($path)."\"");
            readfile($path);
            exit;
        }
        if (!class_exists("ZipArchive")) td_fail(500, "서버에 ZIP 기능이 없습니다.");
        $tmp = tempnam(sys_get_temp_dir(), "td");
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) td_fail(500, "ZIP 파일을 만들 수 없습니다.");
        foreach (td_list()["files"] as $f) {
            if ($f["valid"]) $zip->addFile($td_dir."/".$f["name"], $f["name"]);
        }
        $zip->close();
        header("Content-Type: application/zip");
        header("Content-Length: ".filesize($tmp));
        header("Content-Disposition: attachment; filename=\"testdata_".$pid.".zip\"");
        readfile($tmp);
        @unlink($tmp);
        exit;
    }

    if ($td_action === "list") td_json(td_list());

    // 이하 상태를 바꾸는 작업은 POST와 페이지 토큰을 요구한다.
    if ($td_method !== "POST") td_fail(405, "POST 요청만 허용됩니다.");
    $sent_token = isset($_SERVER['HTTP_X_TESTDATA_TOKEN']) ? $_SERVER['HTTP_X_TESTDATA_TOKEN'] : "";
    if (!hash_equals($td_token, $sent_token)) td_fail(403, "요청이 만료되었습니다. 페이지를 새로고침해 주세요.");
    $body = array();
    if (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], "application/json") === 0) {
        $body = json_decode(file_get_contents("php://input"), true);
        if (!is_array($body)) td_fail(400, "요청 형식이 올바르지 않습니다.");
    }

    switch ($td_action) {
    case "read":
        $path = td_existing_path(isset($body['name']) ? $body['name'] : "");
        if (filesize($path) > TD_EDIT_LIMIT) td_fail(413, "2MB보다 큰 파일은 화면에서 편집할 수 없습니다. 다운로드해서 확인해 주세요.");
        $content = file_get_contents($path);
        if (!mb_check_encoding($content, "UTF-8")) td_fail(415, "UTF-8이 아닌 파일은 화면에서 편집할 수 없습니다. 다운로드해서 확인해 주세요.");
        // 브라우저 편집창은 줄바꿈을 \n으로 바꾸므로, 원래 \r\n 파일이면 저장할 때 되돌릴 수 있게 알려 준다.
        td_json(array("content" => $content, "crlf" => strpos($content, "\r\n") !== false));

    case "save":
        // files: [{name, content}] — 입력/출력 한 쌍을 함께 저장할 수 있다.
        $items = isset($body['files']) && is_array($body['files']) ? $body['files'] : array();
        if (count($items) == 0) td_fail(400, "저장할 파일이 없습니다.");
        foreach ($items as $item) {
            if (!isset($item['name'], $item['content']) || !is_string($item['content'])) td_fail(400, "요청 형식이 올바르지 않습니다.");
            td_path($item['name']);
        }
        foreach ($items as $item) {
            if (file_put_contents(td_path($item['name']), $item['content']) === false) td_fail(500, "저장하지 못했습니다: ".$item['name']);
        }
        td_json(array("saved" => count($items)));

    case "delete":
        $names = isset($body['names']) && is_array($body['names']) ? $body['names'] : array();
        if (count($names) == 0) td_fail(400, "삭제할 파일을 선택해 주세요.");
        foreach ($names as $name) td_existing_path($name);
        foreach ($names as $name) @unlink(td_path($name));
        td_json(array("deleted" => count($names)));

    case "rename":
        $from = td_existing_path(isset($body['from']) ? $body['from'] : "");
        $to = td_path(isset($body['to']) ? $body['to'] : "");
        if (file_exists($to)) td_fail(409, "같은 이름의 파일이 이미 있습니다.");
        if (!rename($from, $to)) td_fail(500, "이름을 바꾸지 못했습니다.");
        td_json(array());

    case "upload":
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) td_fail(400, "파일을 받지 못했습니다. (업로드 오류)");
        $overwrite = !empty($_POST['overwrite']);
        $orig = basename(str_replace("\\", "/", $_FILES['file']['name']));
        $tmp_path = $_FILES['file']['tmp_name'];
        $added = array(); $skipped = array(); $conflicts = array();
        if (preg_match('/\.zip$/i', $orig)) {
            if (!class_exists("ZipArchive")) td_fail(500, "서버에 ZIP 기능이 없습니다.");
            $zip = new ZipArchive();
            if ($zip->open($tmp_path) !== true) td_fail(400, "ZIP 파일을 열 수 없습니다.");
            $total = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $entry = str_replace("\\", "/", $stat['name']);
                if (substr($entry, -1) === "/" || strpos($entry, "__MACOSX/") !== false) continue;
                $name = basename($entry);
                if ($name === "" || $name[0] === ".") continue;
                if (!td_valid_name($name)) { $skipped[] = $name; continue; }
                $total += $stat['size'];
                if ($total > TD_ZIP_LIMIT) { $zip->close(); td_fail(413, "ZIP 안의 파일이 너무 큽니다. (최대 1GB)"); }
                $dest = $td_dir."/".$name;
                if (file_exists($dest) && !$overwrite) { $conflicts[] = $name; continue; }
                $in = $zip->getStream($stat['name']);
                $part = tempnam($td_dir, ".td");
                $out = fopen($part, "wb");
                stream_copy_to_stream($in, $out);
                fclose($in); fclose($out);
                @chmod($part, 0664);
                rename($part, $dest);
                $added[] = $name;
            }
            $zip->close();
        } else {
            if (!td_valid_name($orig)) td_fail(400, "허용되지 않는 파일 이름입니다: ".$orig." (영문, 숫자, . _ - + 만 사용)");
            $dest = $td_dir."/".$orig;
            if (file_exists($dest) && !$overwrite) {
                $conflicts[] = $orig;
            } else {
                if (!move_uploaded_file($tmp_path, $dest)) td_fail(500, "파일을 저장하지 못했습니다.");
                @chmod($dest, 0664);
                @chgrp($dest, filegroup($td_dir));  // 임시 폴더에서 옮겨 온 파일도 데이터 폴더와 같은 그룹을 쓴다.
                $added[] = $orig;
            }
        }
        td_json(array("added" => $added, "skipped" => $skipped, "conflicts" => $conflicts));

    case "ans2out":
        // install/ans2out 스크립트와 같이 *.ans 파일을 같은 이름의 *.out으로 바꾼다.
        $count = 0;
        foreach (td_list()["files"] as $f) {
            if ($f["valid"] && preg_match('/^(.*)\.ans$/', $f["name"], $m)) {
                if (rename($td_dir."/".$f["name"], $td_dir."/".$m[1].".out")) $count++;
            }
        }
        td_json(array("converted" => $count));

    case "generate":
        // phpfm.php의 출력 생성과 같다: 문제 번호를 음수로 한 채점 요청을 넣으면 채점기가 기준 코드로 .out을 만든다.
        // 채점기의 makeout.sh는 Gen.py(입력 생성)를 먼저 실행한 뒤 Main.c/Main.cc/Main.py(정답 코드)로 .out을 만든다.
        // 정답 코드가 없으면 출력이 하나도 만들어지지 않으므로 미리 막는다.
        $has_main = false;
        foreach (array("Main.c", "Main.cc", "Main.py") as $src) {
            if (file_exists($td_dir."/".$src) && filesize($td_dir."/".$src) > 0) $has_main = true;
        }
        if (!$has_main) td_fail(400, "정답 코드가 없습니다. 기타 파일의 Main.cc(C++), Main.c(C) 또는 Main.py(Python)에 정답 코드를 작성해 주세요. Gen.py는 입력(.in)을 만드는 스크립트라 정답 코드로 쓰이지 않습니다.");
        $user_id = $_SESSION[$OJ_NAME.'_'.'user_id'];
        $nick = isset($_SESSION[$OJ_NAME.'_'.'nick']) ? $_SESSION[$OJ_NAME.'_'.'nick'] : $user_id;
        $sql = "INSERT INTO solution(problem_id,user_id,nick,in_date,language,ip,code_length,result) VALUES(?,?,?,NOW(),?,?,?,1)";
        $solution_id = pdo_query($sql, -$pid, $user_id, $nick, 6, $_SERVER['REMOTE_ADDR'], 0);
        trigger_judge($solution_id);
        $_SESSION[$OJ_NAME.'_'.'testdata_gen'][intval($solution_id)] = time();
        td_json(array("solution_id" => intval($solution_id)));

    case "generate_status":
        $sid = isset($body['solution_id']) ? intval($body['solution_id']) : 0;
        $row = pdo_query("select result from solution where solution_id=? and problem_id=?", $sid, -$pid);
        if (empty($row)) td_fail(404, "출력 생성 요청을 찾을 수 없습니다.");
        $done = intval($row[0][0]) >= 4;
        $made = 0; $empty = 0; $inputs = 0;
        if ($done) {
            // 요청 이후에 새로 쓰인 .out 수를 세어, 실제로 출력이 만들어졌는지 알려 준다.
            $since = isset($_SESSION[$OJ_NAME.'_'.'testdata_gen'][$sid]) ? $_SESSION[$OJ_NAME.'_'.'testdata_gen'][$sid] - 1 : 0;
            clearstatcache();
            foreach (td_list()["files"] as $f) {
                if (preg_match('/\.in$/', $f["name"])) $inputs++;
                if (preg_match('/\.out$/', $f["name"]) && $f["mtime"] >= $since) {
                    $made++;
                    if ($f["size"] == 0) $empty++;
                }
            }
        }
        td_json(array("result" => intval($row[0][0]), "done" => $done, "made" => $made, "empty" => $empty, "inputs" => $inputs));
    }
    td_fail(400, "알 수 없는 작업입니다.");
}

require("admin-header.php");
?>
<title><?php echo $MSG_TESTDATA." - ".$pid?></title>
<style>
  .td-page { max-width: 1240px; }
  .td-heading-meta { margin: 5px 0 0; color: var(--admin-muted); }
  .td-heading-meta a { font-weight: 600; }
  .td-summary { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 16px; }
  .td-drop { display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 6px; margin: 0 0 18px; padding: 22px 16px; border: 2px dashed #c7d2e0; border-radius: 12px; background: #fff; color: var(--admin-muted); text-align: center; cursor: pointer; transition: border-color .15s, background-color .15s; }
  .td-drop:hover, .td-drop:focus-visible { border-color: var(--admin-primary); outline: 0; }
  .td-drop.is-over { border-color: var(--admin-primary); background: #eff6ff; color: var(--admin-primary-dark); }
  .td-drop strong { color: var(--admin-text); font-size: 15px; }
  .td-progress { margin: -8px 0 18px; padding: 0; list-style: none; font-size: 13px; }
  .td-progress li { padding: 3px 0; color: var(--admin-muted); }
  .td-progress li.is-error { color: #b91c1c; }
  .td-progress li.is-done { color: #15803d; }
  .td-section { margin: 0 0 22px; border: 1px solid var(--admin-border); border-radius: 12px; background: #fff; }
  .td-section-head { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; padding: 12px 16px; border-bottom: 1px solid var(--admin-border); }
  .td-section-head h4 { margin: 0; color: var(--admin-text); font-size: 15px; font-weight: 700; }
  .td-tools { display: flex; flex-wrap: wrap; gap: 6px; }
  .td-table { width: 100%; margin: 0; table-layout: fixed; }
  .td-table th { padding: 9px 12px !important; border-top: 0 !important; color: var(--admin-muted); font-size: 12px; font-weight: 700; background: #f8fafc; }
  .td-table td { padding: 9px 12px !important; vertical-align: top !important; }
  .td-table tr.is-selected td { background: #eff6ff; }
  .td-col-check { width: 40px; }
  .td-col-name { width: 150px; }
  .td-col-actions { width: 150px; text-align: right; }
  .td-name { font-weight: 700; color: var(--admin-text); word-break: break-all; }
  .td-drop input[type="file"] { display: none !important; }
  /* 미리보기는 button 대신 div로 만들어 admin.css의 표 안 버튼 스타일(최소 너비 등)이 섞이지 않게 한다. */
  .td-cell { display: block; width: 100%; cursor: pointer; outline: 0; }
  .td-cell:focus-visible .td-pre { border-color: var(--admin-primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, .12); }
  .td-cell:hover .td-pre { border-color: var(--admin-primary); }
  .td-file-meta { display: flex; gap: 8px; margin-bottom: 4px; color: var(--admin-muted); font-size: 12px; }
  .td-file-meta b { color: var(--admin-text); font-weight: 600; }
  .td-pre { display: block; max-height: 62px; margin: 0; padding: 6px 8px; overflow: hidden; border: 1px solid #e5e7eb; border-radius: 6px; background: #f9fafb; color: #374151; font: 12px/1.45 ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; white-space: pre-wrap; word-break: break-all; }
  .td-pre.is-empty { color: #9ca3af; font-style: italic; }
  .td-missing { display: flex; align-items: center; gap: 8px; }
  .td-actions { display: flex; justify-content: flex-end; flex-wrap: nowrap; gap: 4px; }
  .td-actions .btn { display: inline-block; width: auto; min-width: 0 !important; height: 28px; min-height: 28px; margin: 0; padding: 0 9px; font-size: 12px; line-height: 26px; }
  .td-col-actions.is-wide { width: 220px; }
  .td-empty { padding: 28px 16px; color: var(--admin-muted); text-align: center; }
  .td-note { margin: 0; padding: 10px 16px; border-top: 1px solid var(--admin-border); color: var(--admin-muted); font-size: 13px; }
  .td-modal { position: fixed; inset: 0; z-index: 2000; display: none; align-items: flex-start; justify-content: center; padding: 40px 16px; overflow-y: auto; background: rgba(15, 23, 42, .45); }
  .td-modal.is-open { display: flex; }
  .td-dialog { width: 100%; max-width: 1100px; border-radius: 12px; background: #fff; box-shadow: 0 20px 50px rgba(15, 23, 42, .25); }
  .td-dialog-head, .td-dialog-foot { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 14px 18px; }
  .td-dialog-head { border-bottom: 1px solid var(--admin-border); }
  .td-dialog-head h4 { margin: 0; font-size: 16px; font-weight: 700; }
  .td-dialog-foot { border-top: 1px solid var(--admin-border); justify-content: flex-end; }
  .td-dialog-body { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 14px; padding: 16px 18px; }
  .td-pane label { display: flex; justify-content: space-between; margin: 0 0 6px; font-size: 13px; }
  .td-pane label span { color: var(--admin-muted); font-weight: 400; }
  .td-pane textarea { width: 100%; min-height: 360px; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font: 13px/1.5 ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; resize: vertical; white-space: pre; tab-size: 4; }
  .td-pane textarea:focus { border-color: var(--admin-primary); outline: 0; box-shadow: 0 0 0 3px rgba(37, 99, 235, .12); }
  .td-base { display: flex; align-items: center; gap: 8px; }
  .td-base input { width: 140px; }
  .td-toast { position: fixed; right: 20px; bottom: 20px; z-index: 2100; max-width: 420px; padding: 11px 16px; border-radius: 10px; background: #1f2937; color: #fff; font-size: 14px; box-shadow: 0 10px 25px rgba(15, 23, 42, .25); opacity: 0; transform: translateY(8px); transition: opacity .2s, transform .2s; pointer-events: none; }
  .td-toast.is-show { opacity: 1; transform: none; }
  .td-toast.is-error { background: #b91c1c; }
  @media (max-width: 767px) {
    .td-col-name { width: 90px; }
    .td-table { min-width: 680px; }
    .td-table .td-hide-sm { display: none; }
  }
</style>
<div class="padding admin-content-page td-page">
  <div class="admin-page-heading">
    <div>
      <h3><?php echo $MSG_TESTDATA?></h3>
      <p class="td-heading-meta"><a href="../problem.php?id=<?php echo $pid?>" target="_blank">#<?php echo $pid?> <?php echo htmlentities($td_title, ENT_QUOTES, 'UTF-8')?></a></p>
    </div>
    <div class="admin-heading-actions">
      <a class="btn btn-default" href="problem_edit.php?id=<?php echo $pid?>&getkey=<?php echo isset($_SESSION[$OJ_NAME.'_'.'getkey']) ? htmlentities($_SESSION[$OJ_NAME.'_'.'getkey'], ENT_QUOTES, 'UTF-8') : ""?>">문제 수정</a>
      <a class="btn btn-default" href="testdata.php?pid=<?php echo $pid?>&action=zip">전체 ZIP 다운로드</a>
    </div>
  </div>

  <div class="td-summary" id="td-summary"></div>

  <div class="td-drop" id="td-drop" tabindex="0" role="button" aria-label="채점 데이터 파일 업로드">
    <strong>파일이나 ZIP을 여기에 끌어다 놓거나 클릭해서 선택하세요</strong>
    <span>.in / .out 파일, ZIP(자동으로 압축 해제), Main.cc · Gen.py 같은 기준 코드를 올릴 수 있습니다. 같은 이름의 파일이 있으면 덮어쓸지 묻습니다.</span>
    <input type="file" id="td-file" multiple hidden>
  </div>
  <ul class="td-progress" id="td-progress"></ul>

  <section class="td-section">
    <div class="td-section-head">
      <h4>입출력 데이터</h4>
      <div class="td-tools">
        <button type="button" class="btn btn-primary btn-sm" id="td-new-pair">+ 새 입출력 쌍</button>
        <button type="button" class="btn btn-danger btn-sm" id="td-delete-selected" disabled>선택 삭제</button>
        <button type="button" class="btn btn-default btn-sm" id="td-generate" title="Main.cc / Main.c / Gen.py 기준 코드로 .in에 맞는 .out을 만듭니다">출력 자동 생성</button>
        <button type="button" class="btn btn-default btn-sm" id="td-ans2out" hidden>.ans → .out 변환</button>
      </div>
    </div>
    <div class="admin-table-wrap">
      <table class="table td-table">
        <thead>
          <tr>
            <th class="td-col-check"><input type="checkbox" id="td-check-all" aria-label="전체 선택"></th>
            <th class="td-col-name">이름</th>
            <th>입력 (.in)</th>
            <th>출력 (.out)</th>
            <th class="td-col-actions">작업</th>
          </tr>
        </thead>
        <tbody id="td-pairs"></tbody>
      </table>
    </div>
    <p class="td-note"><b>출력 자동 생성</b>: 기타 파일의 Main.cc(C++), Main.c(C) 또는 Main.py(Python)에 정답 코드를 작성하면, 모든 .in 파일로 실행해 같은 이름의 .out을 만듭니다. Gen.py가 있으면 그보다 먼저 실행해 .in을 만듭니다.</p>
  </section>

  <section class="td-section">
    <div class="td-section-head">
      <h4>기타 파일</h4>
      <div class="td-tools">
        <button type="button" class="btn btn-default btn-sm" id="td-new-file">+ 새 파일</button>
      </div>
    </div>
    <div class="admin-table-wrap">
      <table class="table td-table">
        <thead>
          <tr>
            <th class="td-col-name">이름</th>
            <th>내용</th>
            <th class="td-col-name td-hide-sm">수정 시각</th>
            <th class="td-col-actions is-wide">작업</th>
          </tr>
        </thead>
        <tbody id="td-others"></tbody>
      </table>
    </div>
    <p class="td-note">입출력 쌍이 아닌 파일입니다. <b>Main.cc / Main.c / Main.py</b>는 출력 자동 생성에 쓰는 정답 코드(C++/C/Python, 여러 개면 C → C++ → Python 순서로 하나만 사용), <b>Gen.py</b>는 입력(.in)을 만드는 Python 스크립트, <b>spj.cc</b>는 스페셜 저지입니다.</p>
  </section>
</div>

<div class="td-modal" id="td-modal" aria-hidden="true">
  <div class="td-dialog" role="dialog" aria-modal="true" aria-labelledby="td-modal-title">
    <div class="td-dialog-head">
      <h4 id="td-modal-title"></h4>
      <div class="td-base" id="td-base-wrap">
        <label for="td-base" class="admin-field-help" style="margin:0">이름</label>
        <input type="text" class="form-control input-sm" id="td-base" autocomplete="off">
      </div>
    </div>
    <div class="td-dialog-body" id="td-panes"></div>
    <div class="td-dialog-foot">
      <button type="button" class="btn btn-default" id="td-cancel">취소</button>
      <button type="button" class="btn btn-primary" id="td-save"><?php echo $MSG_SAVE?></button>
    </div>
  </div>
</div>
<div class="td-toast" id="td-toast" role="status" aria-live="polite"></div>

<script>
(function () {
  var PID = <?php echo $pid?>;
  var TOKEN = <?php echo json_encode($td_token)?>;
  var EDIT_LIMIT = <?php echo TD_EDIT_LIMIT?>;
  var API = 'testdata.php?pid=' + PID + '&action=';
  var MSG = <?php echo json_encode(array("download" => $MSG_DOWNLOAD, "delete" => $MSG_DELETE), JSON_UNESCAPED_UNICODE)?>;
  var MAIN_SOURCES = ['Main.c', 'Main.cc', 'Main.py'];
  var ROLES = { 'Main.cc': '정답 코드', 'Main.c': '정답 코드', 'Main.py': '정답 코드', 'Gen.py': '입력 생성', 'spj.cc': '스페셜 저지', 'spj.c': '스페셜 저지', 'interactor.cc': '인터랙터' };
  var MAIN_TEMPLATE = '#include <bits/stdc++.h>\nusing namespace std;\n\nint main() {\n    \n    return 0;\n}\n';
  var state = { files: [], dirs: [], selected: {}, editing: null };

  function $(id) { return document.getElementById(id); }
  function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function fmtSize(n) {
    if (n < 1024) return n + 'B';
    if (n < 1048576) return (n / 1024).toFixed(1) + 'KB';
    return (n / 1048576).toFixed(1) + 'MB';
  }
  function fmtTime(t) {
    var d = new Date(t * 1000), p = function (x) { return (x < 10 ? '0' : '') + x; };
    return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()) + ' ' + p(d.getHours()) + ':' + p(d.getMinutes());
  }
  function natural(a, b) { return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' }); }
  function validName(n) { return /^[A-Za-z0-9][A-Za-z0-9._+-]*$/.test(n) && n.length <= 128; }

  var toastTimer;
  function toast(msg, isError) {
    var t = $('td-toast');
    t.textContent = msg;
    t.className = 'td-toast is-show' + (isError ? ' is-error' : '');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { t.className = 'td-toast'; }, isError ? 5000 : 2600);
  }

  function request(action, body, form) {
    var opts = { method: 'POST', headers: { 'X-Testdata-Token': TOKEN }, credentials: 'same-origin' };
    if (form) opts.body = form;
    else { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body || {}); }
    return fetch(API + action, opts).then(function (r) {
      return r.json().catch(function () { throw new Error('서버 응답을 읽을 수 없습니다. (HTTP ' + r.status + ')'); });
    }).then(function (data) {
      if (!data.ok) throw new Error(data.error || '요청에 실패했습니다.');
      return data;
    });
  }

  function load() {
    return fetch(API + 'list', { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (data) {
      if (!data.ok) throw new Error(data.error);
      state.files = data.files;
      state.dirs = data.dirs;
      render();
    }).catch(function (e) { toast(e.message, true); });
  }

  function groups() {
    var byName = {}, pairs = {}, others = [];
    state.files.forEach(function (f) { byName[f.name] = f; });
    state.files.forEach(function (f) {
      var m = f.name.match(/^(.*)\.(in|out)$/);
      if (m && m[1] !== '') {
        pairs[m[1]] = pairs[m[1]] || { base: m[1], input: null, output: null };
        pairs[m[1]][m[2] === 'in' ? 'input' : 'output'] = f;
      } else {
        others.push(f);
      }
    });
    var list = Object.keys(pairs).sort(natural).map(function (k) { return pairs[k]; });
    others.sort(function (a, b) { return natural(a.name, b.name); });
    return { pairs: list, others: others, byName: byName };
  }

  function fileCell(f, base, ext) {
    if (!f) {
      return '<div class="td-missing"><span class="admin-badge admin-badge--warn">없음</span>' +
        '<button type="button" class="btn btn-link btn-xs" data-edit-pair="' + esc(base) + '">작성</button></div>';
    }
    var body = f.binary ? '<span class="td-pre is-empty">바이너리 파일</span>' :
      (f.size === 0 ? '<span class="td-pre is-empty">(빈 파일)</span>' : '<span class="td-pre">' + esc(f.preview) + '</span>');
    return '<div class="td-file-meta"><b>' + esc(f.name) + '</b><span>' + fmtSize(f.size) + '</span>' +
      '<a href="' + API + 'download&name=' + encodeURIComponent(f.name) + '">' + esc(MSG.download) + '</a></div>' +
      '<div class="td-cell" role="button" tabindex="0" data-edit-pair="' + esc(base) + '" title="' + esc(f.name) + ' 편집">' + body + '</div>';
  }

  function render() {
    var g = groups();
    var missing = g.pairs.filter(function (p) { return !p.input || !p.output; }).length;
    var total = state.files.reduce(function (s, f) { return s + f.size; }, 0);
    var ans = state.files.filter(function (f) { return /\.ans$/.test(f.name); }).length;
    var hasGen = MAIN_SOURCES.some(function (n) { return g.byName[n] && g.byName[n].size > 0; });

    $('td-summary').innerHTML =
      '<span class="admin-badge admin-badge--info">입출력 ' + g.pairs.length + '쌍</span>' +
      (missing ? '<span class="admin-badge admin-badge--warn">짝이 없는 데이터 ' + missing + '개</span>' : (g.pairs.length ? '<span class="admin-badge admin-badge--on">모든 입력에 출력이 있음</span>' : '')) +
      '<span class="admin-badge admin-badge--off">기타 파일 ' + g.others.length + '개</span>' +
      '<span class="admin-badge admin-badge--off">총 ' + fmtSize(total) + '</span>' +
      (state.dirs.length ? '<span class="admin-badge admin-badge--warn">하위 폴더 ' + state.dirs.length + '개는 표시하지 않음</span>' : '');

    $('td-ans2out').hidden = ans === 0;
    $('td-ans2out').textContent = '.ans → .out 변환 (' + ans + ')';
    $('td-generate').textContent = hasGen ? '출력 자동 생성' : '정답 코드 작성 (Main.cc)';
    $('td-generate').title = hasGen ? '정답 코드(Main.cc / Main.c / Main.py)로 모든 .in의 .out을 만듭니다' : '출력 자동 생성에 쓸 정답 코드를 먼저 작성합니다';
    $('td-generate').setAttribute('data-has-main', hasGen ? '1' : '');

    var selectable = {};
    $('td-pairs').innerHTML = g.pairs.length ? g.pairs.map(function (p) {
      var names = [p.input, p.output].filter(Boolean).map(function (f) { return f.name; });
      names.forEach(function (n) { selectable[n] = true; });
      var checked = names.length && names.every(function (n) { return state.selected[n]; });
      return '<tr' + (checked ? ' class="is-selected"' : '') + '>' +
        '<td class="td-col-check"><input type="checkbox" data-select="' + esc(names.join('\n')) + '"' + (checked ? ' checked' : '') + ' aria-label="' + esc(p.base) + ' 선택"></td>' +
        '<td><span class="td-name">' + esc(p.base) + '</span></td>' +
        '<td>' + fileCell(p.input, p.base, 'in') + '</td>' +
        '<td>' + fileCell(p.output, p.base, 'out') + '</td>' +
        '<td class="td-col-actions"><div class="td-actions">' +
          '<button type="button" class="btn btn-default" data-edit-pair="' + esc(p.base) + '">편집</button>' +
          '<button type="button" class="btn btn-default" data-rename-pair="' + esc(p.base) + '" title="이름 변경">이름</button>' +
          '<button type="button" class="btn btn-danger" data-delete="' + esc(names.join('\n')) + '">' + esc(MSG.delete) + '</button>' +
        '</div></td></tr>';
    }).join('') : '<tr><td colspan="5" class="td-empty">아직 입출력 데이터가 없습니다. 위에서 파일을 올리거나 "+ 새 입출력 쌍"을 눌러 만드세요.</td></tr>';

    $('td-others').innerHTML = g.others.length ? g.others.map(function (f) {
      var body = f.binary ? '<span class="td-pre is-empty">바이너리 파일</span>' :
        (f.size === 0 ? '<span class="td-pre is-empty">(빈 파일)</span>' : '<span class="td-pre">' + esc(f.preview) + '</span>');
      return '<tr>' +
        '<td><span class="td-name">' + esc(f.name) + '</span><div class="admin-field-help">' + fmtSize(f.size) +
          (ROLES[f.name] ? ' · ' + ROLES[f.name] : '') + '</div></td>' +
        '<td><div class="td-cell" role="button" tabindex="0" data-edit-file="' + esc(f.name) + '" title="' + esc(f.name) + ' 편집">' + body + '</div></td>' +
        '<td class="td-hide-sm admin-field-help">' + fmtTime(f.mtime) + '</td>' +
        '<td class="td-col-actions"><div class="td-actions">' +
          '<button type="button" class="btn btn-default" data-edit-file="' + esc(f.name) + '">편집</button>' +
          '<a class="btn btn-default" href="' + API + 'download&name=' + encodeURIComponent(f.name) + '">' + esc(MSG.download) + '</a>' +
          '<button type="button" class="btn btn-default" data-rename-file="' + esc(f.name) + '" title="이름 변경">이름</button>' +
          '<button type="button" class="btn btn-danger" data-delete="' + esc(f.name) + '">' + esc(MSG.delete) + '</button>' +
        '</div></td></tr>';
    }).join('') : '<tr><td colspan="4" class="td-empty">기타 파일이 없습니다.</td></tr>';

    Object.keys(state.selected).forEach(function (n) { if (!selectable[n]) delete state.selected[n]; });
    var count = Object.keys(state.selected).length;
    $('td-delete-selected').disabled = count === 0;
    $('td-delete-selected').textContent = count ? '선택 삭제 (' + count + '개 파일)' : '선택 삭제';
    var boxes = document.querySelectorAll('#td-pairs input[data-select]');
    $('td-check-all').checked = boxes.length > 0 && Array.prototype.every.call(boxes, function (b) { return b.checked; });
  }

  // ---------- 편집 창 ----------
  function openEditor(opts) {
    // opts: { title, base (pair) | name (file), panes: [{name, label, content, readonly, note}] }
    state.editing = opts;
    $('td-modal-title').textContent = opts.title;
    $('td-base-wrap').style.display = opts.pair ? '' : 'none';
    $('td-base').value = opts.pair ? opts.base : '';
    $('td-base').readOnly = !opts.isNew;
    $('td-panes').innerHTML = opts.panes.map(function (p, i) {
      return '<div class="td-pane"><label for="td-pane-' + i + '"><b>' + esc(p.label) + '</b><span id="td-pane-meta-' + i + '"></span></label>' +
        (p.note ? '<p class="admin-field-help">' + esc(p.note) + '</p>' : '') +
        '<textarea id="td-pane-' + i + '" spellcheck="false"' + (p.readonly ? ' readonly' : '') + '></textarea></div>';
    }).join('');
    opts.panes.forEach(function (p, i) {
      var ta = $('td-pane-' + i);
      ta.value = p.content;
      var meta = function () {
        var lines = ta.value === '' ? 0 : ta.value.replace(/\n$/, '').split('\n').length;
        $('td-pane-meta-' + i).textContent = lines + '줄 · ' + fmtSize(new Blob([ta.value]).size);
      };
      ta.addEventListener('input', meta);
      meta();
    });
    $('td-modal').classList.add('is-open');
    $('td-modal').setAttribute('aria-hidden', 'false');
    var first = opts.isNew ? $('td-base') : $('td-pane-0');
    if (first) first.focus();
  }
  function closeEditor() {
    $('td-modal').classList.remove('is-open');
    $('td-modal').setAttribute('aria-hidden', 'true');
    state.editing = null;
  }
  function readFile(f) {
    if (!f) return Promise.resolve({ content: '', exists: false });
    if (f.binary) return Promise.resolve({ content: '', exists: true, readonly: true, note: '바이너리 파일은 화면에서 편집할 수 없습니다. 다운로드해서 확인하세요.' });
    if (f.size > EDIT_LIMIT) return Promise.resolve({ content: '', exists: true, readonly: true, note: '2MB보다 큰 파일은 화면에서 편집할 수 없습니다. 다운로드해서 확인하세요.' });
    return request('read', { name: f.name }).then(function (d) { return { content: d.content, exists: true, crlf: d.crlf }; })
      .catch(function (e) { return { content: '', exists: true, readonly: true, note: e.message }; });
  }
  function editPair(base, isNew) {
    var g = groups(), pair = g.pairs.filter(function (p) { return p.base === base; })[0] || { input: null, output: null };
    Promise.all([readFile(pair.input), readFile(pair.output)]).then(function (r) {
      openEditor({
        title: isNew ? '새 입출력 쌍' : base + ' 입출력 편집', pair: true, base: base, isNew: isNew,
        panes: [
          { label: '입력 (.in)', content: r[0].content, readonly: r[0].readonly, note: r[0].note, crlf: r[0].crlf, ext: 'in' },
          { label: '출력 (.out)', content: r[1].content, readonly: r[1].readonly, note: r[1].note, crlf: r[1].crlf, ext: 'out' }
        ]
      });
    }).catch(function (e) { toast(e.message, true); });
  }
  function editFile(name, isNew) {
    var f = groups().byName[name];
    readFile(f).then(function (r) {
      openEditor({ title: isNew ? '새 파일: ' + name : name + ' 편집', pair: false, name: name,
        panes: [{ label: name, content: r.content, readonly: r.readonly, note: r.note, crlf: r.crlf }] });
    }).catch(function (e) { toast(e.message, true); });
  }
  function paneValue(p, i) {
    var v = $('td-pane-' + i).value;
    return p.crlf ? v.replace(/\r?\n/g, '\r\n') : v;
  }
  function save() {
    var ed = state.editing;
    if (!ed) return;
    var files = [];
    if (ed.pair) {
      var base = $('td-base').value.trim();
      if (!validName(base + '.in')) { toast('이름은 영문, 숫자, . _ - + 만 쓸 수 있습니다.', true); return; }
      if (ed.isNew && groups().pairs.some(function (p) { return p.base === base; })) { toast('같은 이름의 입출력 쌍이 이미 있습니다.', true); return; }
      ed.panes.forEach(function (p, i) { if (!p.readonly) files.push({ name: base + '.' + p.ext, content: paneValue(p, i) }); });
    } else {
      if (!ed.panes[0].readonly) files.push({ name: ed.name, content: paneValue(ed.panes[0], 0) });
    }
    if (!files.length) { closeEditor(); return; }
    $('td-save').disabled = true;
    request('save', { files: files }).then(function () {
      toast('저장했습니다.');
      closeEditor();
      return load();
    }).catch(function (e) { toast(e.message, true); }).then(function () { $('td-save').disabled = false; });
  }

  // ---------- 업로드 ----------
  function uploadOne(file, overwrite) {
    var fd = new FormData();
    fd.append('file', file);
    if (overwrite) fd.append('overwrite', '1');
    return request('upload', null, fd);
  }
  function uploadFiles(list) {
    var files = Array.prototype.slice.call(list);
    if (!files.length) return;
    var progress = $('td-progress');
    progress.innerHTML = '';
    var chain = Promise.resolve();
    files.forEach(function (file) {
      chain = chain.then(function () {
        var li = document.createElement('li');
        li.textContent = file.name + ' 올리는 중…';
        progress.appendChild(li);
        return uploadOne(file, false).then(function (r) {
          if (r.conflicts.length && confirm(file.name + ': 이미 있는 파일 ' + r.conflicts.length + '개를 덮어쓸까요?\n\n' + r.conflicts.slice(0, 15).join(', ') + (r.conflicts.length > 15 ? ' …' : ''))) {
            return uploadOne(file, true).then(function (r2) { return { added: r.added.concat(r2.added), skipped: r.skipped.concat(r2.skipped), kept: 0 }; });
          }
          return { added: r.added, skipped: r.skipped, kept: r.conflicts.length };
        }).then(function (r) {
          li.className = r.skipped.length ? 'is-error' : 'is-done';
          li.textContent = file.name + ': ' + r.added.length + '개 저장' +
            (r.kept ? ', ' + r.kept + '개는 기존 파일 유지' : '') +
            (r.skipped.length ? ', 이름이 허용되지 않아 건너뜀: ' + r.skipped.join(', ') : '');
        }).catch(function (e) {
          li.className = 'is-error';
          li.textContent = file.name + ': ' + e.message;
        });
      });
    });
    chain.then(load);
  }

  // ---------- 이벤트 ----------
  var drop = $('td-drop'), input = $('td-file');
  drop.addEventListener('click', function () { input.click(); });
  drop.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); } });
  input.addEventListener('change', function () { uploadFiles(input.files); input.value = ''; });
  ['dragenter', 'dragover'].forEach(function (t) { drop.addEventListener(t, function (e) { e.preventDefault(); drop.classList.add('is-over'); }); });
  ['dragleave', 'drop'].forEach(function (t) { drop.addEventListener(t, function (e) { e.preventDefault(); drop.classList.remove('is-over'); }); });
  drop.addEventListener('drop', function (e) { uploadFiles(e.dataTransfer.files); });
  window.addEventListener('dragover', function (e) { e.preventDefault(); });
  window.addEventListener('drop', function (e) { e.preventDefault(); });

  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-edit-pair],[data-edit-file],[data-delete],[data-rename-pair],[data-rename-file]');
    if (!t) return;
    if (t.hasAttribute('data-edit-pair')) editPair(t.getAttribute('data-edit-pair'), false);
    else if (t.hasAttribute('data-edit-file')) editFile(t.getAttribute('data-edit-file'), false);
    else if (t.hasAttribute('data-delete')) remove(t.getAttribute('data-delete').split('\n'));
    else if (t.hasAttribute('data-rename-pair')) renamePair(t.getAttribute('data-rename-pair'));
    else if (t.hasAttribute('data-rename-file')) renameFile(t.getAttribute('data-rename-file'));
  });
  document.addEventListener('keydown', function (e) {
    if ((e.key === 'Enter' || e.key === ' ') && e.target.classList && e.target.classList.contains('td-cell')) { e.preventDefault(); e.target.click(); }
  });
  $('td-pairs').addEventListener('change', function (e) {
    if (!e.target.hasAttribute('data-select')) return;
    e.target.getAttribute('data-select').split('\n').forEach(function (n) {
      if (e.target.checked) state.selected[n] = true; else delete state.selected[n];
    });
    render();
  });
  $('td-check-all').addEventListener('change', function () {
    var on = this.checked;
    document.querySelectorAll('#td-pairs input[data-select]').forEach(function (b) {
      b.getAttribute('data-select').split('\n').forEach(function (n) { if (on) state.selected[n] = true; else delete state.selected[n]; });
    });
    render();
  });

  function remove(names) {
    if (!confirm('다음 파일을 삭제할까요? 되돌릴 수 없습니다.\n\n' + names.slice(0, 20).join('\n') + (names.length > 20 ? '\n… 외 ' + (names.length - 20) + '개' : ''))) return;
    request('delete', { names: names }).then(function (r) {
      names.forEach(function (n) { delete state.selected[n]; });
      toast(r.deleted + '개 파일을 삭제했습니다.');
      return load();
    }).catch(function (e) { toast(e.message, true); });
  }
  function renameFile(name) {
    var to = prompt('새 파일 이름', name);
    if (to === null || (to = to.trim()) === '' || to === name) return;
    if (!validName(to)) { toast('파일 이름은 영문, 숫자, . _ - + 만 쓸 수 있습니다.', true); return; }
    request('rename', { from: name, to: to }).then(function () { toast('이름을 바꿨습니다.'); return load(); }).catch(function (e) { toast(e.message, true); });
  }
  function renamePair(base) {
    var to = prompt('새 이름 (.in / .out 앞부분)', base);
    if (to === null || (to = to.trim()) === '' || to === base) return;
    if (!validName(to + '.in')) { toast('이름은 영문, 숫자, . _ - + 만 쓸 수 있습니다.', true); return; }
    var byName = groups().byName, jobs = [];
    ['in', 'out'].forEach(function (ext) { if (byName[base + '.' + ext]) jobs.push({ from: base + '.' + ext, to: to + '.' + ext }); });
    jobs.reduce(function (p, j) { return p.then(function () { return request('rename', j); }); }, Promise.resolve())
      .then(function () { toast('이름을 바꿨습니다.'); }).catch(function (e) { toast(e.message, true); }).then(load);
  }

  $('td-new-pair').addEventListener('click', function () {
    var nums = groups().pairs.map(function (p) { return /^\d+$/.test(p.base) ? parseInt(p.base, 10) : 0; });
    editPair(String((nums.length ? Math.max.apply(null, nums) : 0) + 1), true);
  });
  $('td-new-file').addEventListener('click', function () {
    var name = prompt('새 파일 이름 (예: Main.cc, Main.py, Gen.py, spj.cc)', 'Main.cc');
    if (name === null || (name = name.trim()) === '') return;
    if (!validName(name)) { toast('파일 이름은 영문, 숫자, . _ - + 만 쓸 수 있습니다.', true); return; }
    if (groups().byName[name]) { editFile(name, false); return; }
    editFile(name, true);
  });
  $('td-delete-selected').addEventListener('click', function () { remove(Object.keys(state.selected).sort(natural)); });
  $('td-ans2out').addEventListener('click', function () {
    if (!confirm('.ans 파일을 같은 이름의 .out 파일로 바꿉니다. 같은 이름의 .out이 있으면 덮어씁니다. 계속할까요?')) return;
    request('ans2out').then(function (r) { toast(r.converted + '개 파일을 변환했습니다.'); return load(); }).catch(function (e) { toast(e.message, true); });
  });
  $('td-generate').addEventListener('click', function () {
    if (!this.getAttribute('data-has-main')) {
      // 정답 코드가 없으면 Main.cc 편집 창을 C++ 기본 틀과 함께 연다.
      openEditor({ title: '정답 코드: Main.cc (C++)', pair: false, name: 'Main.cc',
        panes: [{ label: 'Main.cc', content: MAIN_TEMPLATE, note: '이 문제를 맞게 푸는 C++ 코드를 작성하세요. 저장한 뒤 "출력 자동 생성"을 누르면 모든 .in의 .out이 만들어집니다. Python으로 쓰려면 기타 파일의 "+ 새 파일"에서 Main.py를 만드세요.' }] });
      return;
    }
    if (!confirm('정답 코드로 모든 .in 파일의 .out을 다시 만듭니다. 기존 .out 파일은 덮어씁니다. 계속할까요?')) return;
    var btn = this;
    btn.disabled = true;
    btn.textContent = '출력 생성 중…';
    request('generate').then(function (r) {
      var tries = 0;
      return new Promise(function (resolve, reject) {
        (function poll() {
          request('generate_status', { solution_id: r.solution_id }).then(function (s) {
            if (s.done) return resolve(s);
            if (++tries > 90) return reject(new Error('출력 생성이 오래 걸리고 있습니다. 잠시 후 새로고침해 확인하세요.'));
            setTimeout(poll, 2000);
          }).catch(reject);
        })();
      });
    }).then(function (st) {
      if (st.made === 0) toast('출력 파일이 만들어지지 않았습니다. 정답 코드에 문법 오류가 없는지, 파일 이름과 언어가 맞는지(Main.cc는 C++, Main.py는 Python) 확인하세요.', true);
      else if (st.empty > 0) toast(st.made + '개 출력을 만들었지만 ' + st.empty + '개가 빈 파일입니다. 정답 코드가 입력을 제대로 읽는지 확인하세요.', true);
      else toast('.in ' + st.inputs + '개 중 ' + st.made + '개의 출력을 만들었습니다.');
    }).catch(function (e) { toast(e.message, true); })
      .then(function () { btn.textContent = '출력 자동 생성'; return load(); });
  });

  $('td-save').addEventListener('click', save);
  $('td-cancel').addEventListener('click', closeEditor);
  $('td-modal').addEventListener('click', function (e) { if (e.target === this) closeEditor(); });
  document.addEventListener('keydown', function (e) {
    if (!state.editing) return;
    if (e.key === 'Escape') closeEditor();
    if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) { e.preventDefault(); save(); }
  });

  load();
})();
</script>
