<?php
require_once("../include/db_info.inc.php");
require_once("admin-header.php");
require_once("../include/my_func.inc.php");

if (!(isset($_SESSION[$OJ_NAME.'_'.'administrator']) || isset($_SESSION[$OJ_NAME.'_'.'problem_editor']))) {
  exit(1);
}

function problem_editor_content($content) {
  if (preg_match('/^\s*<span\s+class=(["\'])[^"\']*\bmd\b[^"\']*\1\s*>([\s\S]*)<\/span>\s*$/i', (string)$content, $matches)) {
    return $matches[2];
  }
  return (string)$content;
}
?>
  <html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title><?php echo htmlentities($MSG_PROBLEM." - 수정", ENT_QUOTES, "UTF-8")?></title>
  <style>
    html, body { margin: 0; min-height: 100%; width: 100%; }
    body { background: #fff !important; opacity: 1 !important; }
    body.preview-active { overflow: hidden; }
    #main { box-sizing: border-box; width: 100%; padding: 0 24px 24px; }
    #main.preview-open { bottom: 0; left: 0; margin: 0 !important; overflow-y: auto; position: fixed; top: 0; width: 50%; }
    .problem-page-title { background: #fff; border-bottom: 1px solid #e5e7eb; margin: 0 -24px 20px; padding: 16px 24px 14px; position: sticky; top: 0; z-index: 20; }
    .problem-page-title h3 { margin: 0; }
    #main .ke-container { box-sizing: border-box; width: 100% !important; }
    .problem-form-top, .problem-form-section { margin: 0 0 24px; }
    .problem-form-options, .problem-form-actions { align-items: center; display: flex; flex-wrap: wrap; gap: 8px; }
    .problem-form-options { margin-bottom: 16px; }
    .problem-form-actions { margin-top: 4px; }
    .problem-form-actions input { margin-right: 0 !important; }
    .problem-form-id { color: #64748b; font-size: 14px; font-weight: 600; }
    .problem-form-remote { display: flex; flex-wrap: wrap; gap: 8px; }
    .problem-form-remote input { flex: 1 1 220px; }
    #preview { box-sizing: border-box; background: #fff; border-left: 1px solid #d8dee8; bottom: 0; position: fixed; right: 0; top: 0; width: 50%; z-index: 1000; }
    #previewFrame { border: 0; display: block; height: 100%; width: 100%; }
    @media (max-width: 1000px) {
      body.preview-active { overflow: auto; }
      #main.preview-open { position: static; width: 100%; }
      #preview { border-left: 0; border-top: 1px solid #d8dee8; height: 720px; position: static; width: 100%; }
    }
    @media (max-width: 760px) {
      .problem-form-actions { align-items: stretch; flex-direction: column; }
    }
  </style>
</head>
  <?php
$page_heading = $MSG_PROBLEM." - 수정";
include_once("kindeditor.php") ;
?>


<body>
  <div id="main">
    <header class="problem-page-title"><h3><?php echo htmlentities($page_heading, ENT_QUOTES, 'UTF-8')?></h3></header>
    <?php
    if (isset($_GET['id'])) {
      ;//require_once("../include/check_get_key.php");
        $pid=intval($_GET['id']);
        if(! (isset($_SESSION[$OJ_NAME.'_'.'administrator']) || isset($_SESSION[$OJ_NAME.'_'."p".$pid])) ){
                echo "<div class='alert alert-danger' role='alert'>권한이 없습니다.</div>";
                exit(0);
        }
    ?>

    <form method=POST action=problem_edit.php>
      <?php
      $sql = "SELECT * FROM `problem` WHERE `problem_id`=?";
      $result = pdo_query($sql,intval($_GET['id']));
      $row = $result[0];
      ?>

      <input type=hidden name=problem_id value='<?php echo $row['problem_id']?>'>
      <div class="problem-form-top">
        <div class="problem-form-options">
          <div class="ui toggle checkbox">
            <input type="checkbox" id="preview-toggle" checked>
            <label for="preview-toggle">문제 미리보기</label>
          </div>
          <div class="ui toggle checkbox">
            <input type="checkbox" id="full-toggle" checked>
            <label for="full-toggle">전체 항목 표시</label>
          </div>
        </div>
        <h3><label for="title"><?php echo $MSG_TITLE?></label> <span class="problem-form-id">#<?php echo $row['problem_id']?></span></h3>
        <input class="input input-large" style="width:100%;" type=text name=title id='title' value='<?php echo htmlentities($row['title'],ENT_QUOTES,"UTF-8")?>'>
      </div>
        <section class="problem-form-section">
          <?php echo $MSG_Time_Limit; ?>
          <input class="input input-mini" type=number min="0.001" max="300" step="0.001" name=time_limit size=20 value="<?php echo $row['time_limit']?>"> sec
          <?php echo $MSG_Memory_Limit; ?>
          <input class="input input-mini" type=number min="1" max="1024" step="1" name=memory_limit size=20 value="<?php echo $row['memory_limit']?>"> MiB
          <?php echo $MSG_COIN; ?>
          <input class="input input-mini" type=number min="0" max="9999" step="1" name=coin size=20 value="<?php echo intval($row['coin'] ?? 1)?>">
        </section>
        <section class="problem-form-section">
          <?php echo "<h4>".$MSG_Description."</h4>"?>
          <textarea class="kindeditor" rows=33 name=description cols=80><?php echo htmlentities(problem_editor_content($row['description']),ENT_QUOTES,"UTF-8")?></textarea>
        </section>
	<div class="full">
        <section class="problem-form-section">
          <?php echo "<h4>".$MSG_Input."</h4>"?>
          <textarea class="kindeditor" rows=13 name=input cols=80><?php echo htmlentities(problem_editor_content($row['input']),ENT_QUOTES,"UTF-8")?></textarea>
        </section>
        <section class="problem-form-section">
          <?php echo "<h4>".$MSG_Output."</h4>"?>
          <textarea  class="kindeditor" rows=13 name=output cols=80><?php echo htmlentities(problem_editor_content($row['output']),ENT_QUOTES,"UTF-8")?></textarea>
        </section>
        <section class="problem-form-section">
          <?php echo "<h4>".$MSG_Sample_Input."</h4>"?>
          <textarea  class="input input-large" style="width:100%;" rows=13 name=sample_input><?php echo htmlentities($row['sample_input'],ENT_QUOTES,"UTF-8")?></textarea>
        </section>
        <section class="problem-form-section">
          <?php echo "<h4>".$MSG_Sample_Output."</h4>"?>
          <textarea  class="input input-large" style="width:100%;" rows=13 name=sample_output><?php echo htmlentities($row['sample_output'],ENT_QUOTES,"UTF-8")?></textarea>
        </section>
        <section class="problem-form-section">
          <?php echo "<h4>".$MSG_HINT."</h4>"?>
          <textarea class="kindeditor" rows=13 name=hint cols=80><?php echo htmlentities(problem_editor_content($row['hint']),ENT_QUOTES,"UTF-8")?></textarea>
        </section>
	</div>
        <section class="problem-form-section">
          <?php echo "<h4>".$MSG_SPJ."</h4>"?>
          <?php echo "(".$MSG_HELP_SPJ.")"?><br>
          <input type=radio name=spj value='0' <?php echo $row['spj']=="0"?"checked":""?> title='Normal Judger'><?php echo $MSG_NJ?><br>
          <input type=radio name=spj value='1' <?php echo $row['spj']=="1"?"checked":""?> title='Special Judger'><?php echo $MSG_SPJ?><br>
          <input type=radio name=spj value='2' <?php echo $row['spj']=="2"?"checked":""?> title='Raw Text Judger' ><?php echo $MSG_RTJ?><br>
          <input type=radio name=spj value='3' <?php echo $row['spj']=="3"?"checked":""?> title='Interactive Judger' ><?php echo $MSG_INTERACT?><br>
        </section>
        <section class="problem-form-section">
          <?php echo "<h4>".$MSG_SOURCE."</h4>"?>
          <textarea name=source style="width:100%;" rows=1><?php echo htmlentities($row['source'],ENT_QUOTES,"UTF-8")?></textarea>
        </section>
        <section class="problem-form-section">
          <?php echo "<h4>".$MSG_REMOTE_OJ."</h4>"?>
          <div class="problem-form-remote">
            <input class="form-control" name=remote_oj value='<?php echo htmlentities((string)$row['remote_oj'],ENT_QUOTES,"UTF-8")?>' placeholder='<?php echo $MSG_HELP_LOCAL_EMPTY ?>' >
            <input class="form-control" name=remote_id value='<?php echo htmlentities((string)$row['remote_id'],ENT_QUOTES,"UTF-8")?>' placeholder='<?php echo $MSG_HELP_LOCAL_EMPTY ?>' >
          </div>
        </section>

      <div class="problem-form-actions">
        <?php require_once("../include/set_post_key.php");?>
        <input class="btn btn-success" type=submit value='<?php echo $MSG_SAVE?>' name=submit>
        <input class='btn btn-danger' type=reset value='<?php echo $MSG_RESET?>'>
      </div>
    </form>

  </div>

<script src="<?php echo $OJ_CDN_URL."/template/bs3/"?>marked.min.js"></script>
<script>
function untransform() {
    let main = $("#main");
    $("body").removeClass("preview-active");
    main.removeClass("preview-open");
    main.css("width", "");
    main.css("margin-left", "");

    // 미리보기 iframe 제거
    $("#preview").remove();

  
    // 미리보기 동기화 이벤트 제거
    $("input").off('keyup', sync);
    $("textarea").off('keyup', sync);
}
  function transform(){
	let submitURL="../problem.php?id=<?php echo $pid ?>";
        let main=$("#main");
        if ($("#preview").length) return;
        $("body").addClass("preview-active");
        main.removeClass("container").addClass("preview-open");
        main.parent().append("<div id='preview'></div>");
        $("#preview").html("<iframe id='previewFrame' src='"+submitURL+"&spa'></iframe>");
        $("#submit").remove();
        setTimeout('hide()',1500);	
	$("input").keyup(sync);
	$("textarea").keyup(sync);
  }
  function hide(){
	let preview=$("#previewFrame").contents();
	preview.find(".ui.buttons").hide();
	preview.find("span.ui.label").eq(2).hide();
	preview.find("span.ui.label").eq(3).hide();
	preview.find("span.ui.label").eq(4).hide();
	preview.find("span.ui.label").eq(5).hide();
	preview.find("#show_tag_div").parent().hide();
	sync();
//	preview.find("h1:first").parent().parent().hide();
  }
  function sync(){
	console.log("sync...");
	let preview=$("#previewFrame").contents();
	let title=$("input[name=title]").val();
	preview.find("h1:first").html(title);
	let time=$("input[name=time_limit]").val();
	preview.find("span.ui.label").eq(0).html("<?php echo $MSG_Time_Limit ?>："+time);
	let memory=$("input[name=memory_limit]").val();
	preview.find("span.ui.label").eq(1).html("<?php echo $MSG_Memory_Limit ?>："+memory);
	
	let description=$("textarea[name='description']").val();
	renderMarkdownPreview(preview.find("#description"), description, true);
  
	let input=$("textarea[name='input']").val();
	renderMarkdownPreview(preview.find("#input"), input, false);
	let output=$("textarea[name='output']").val();
	renderMarkdownPreview(preview.find("#output"), output, false);

	let sinput=$("textarea[name='sample_input']").val();
	preview.find("#sinput").html(sinput);
	let soutput=$("textarea[name='sample_output']").val();
	preview.find("#soutput").html(soutput);
	let hint=$("textarea[name='hint']").val();
	renderMarkdownPreview(preview.find("#hint"), hint, false);
	if($("#previewFrame")[0] != undefined) $("#previewFrame")[0].contentWindow.MathJax.typeset();
  }
  function renderMarkdownPreview(target, content, autoSelect) {
    let source = content || "";
    let existing = $("<div>").html(source).find(".md").first();
    if (existing.length) source = existing.html();
    if (!source.trim()) source = "\u00a0";
    let wrapper = $("<span>").addClass(autoSelect ? "md auto_select" : "md");
    wrapper.html(marked.parse(source, { breaks: true, gfm: true }));
    target.empty().append(wrapper);
  }
  $(document).ready(function(){
  
            // 기본적으로 미리보기 활성화
           <?php if (!(isset($OJ_OLD_FASHINED) && $OJ_OLD_FASHINED )) echo " transform();" ?>
            
            // 미리보기 전환 처리
            $('#preview-toggle').change(function() {
                if(this.checked) {
                    transform();
                } else {
                    untransform();
                }
            });
            $('#full-toggle').change(function() {
		$(".full").toggle();
                sync();
            });
  }); 
</script>
    <?php
    }
    else {
      require_once("../include/check_post_key.php");
      $id = intval($_POST['problem_id']);
      if(! (isset($_SESSION[$OJ_NAME.'_'.'administrator']) || isset($_SESSION[$OJ_NAME.'_'."p".$id])) ){
                echo "<div class='alert alert-danger' role='alert'>권한이 없습니다.</div>";
                exit(0)    ;
      }
      if (!(isset($_SESSION[$OJ_NAME.'_'."p$id"]) || isset($_SESSION[$OJ_NAME.'_'.'administrator']) || isset($_SESSION[$OJ_NAME.'_'.'problem_editor']) )) exit();

      $title = $_POST['title'];
      $title = str_replace(",", "&#44;", $title);

      $time_limit = $_POST['time_limit'];

      $memory_limit = $_POST['memory_limit'];

      $description = $_POST['description'];
     // $description = str_replace("<p>", "", $description);
     // $description = str_replace("</p>", "<br />", $description);
    //  $description = str_replace(",", "&#44;", $description);

      $input = $_POST['input'];
     // $input = str_replace("<p>", "", $input);
     // $input = str_replace("</p>", "<br />", $input);
    //  $input = str_replace(",", "&#44;", $input);

      $output = $_POST['output'];
     // $output = str_replace("<p>", "", $output);
     // $output = str_replace("</p>", "<br />", $output);
     // $output = str_replace(",", "&#44;", $output);

      $sample_input = $_POST['sample_input'];
      $sample_output = $_POST['sample_output'];
      //if ($sample_input=="") $sample_input="\n";
      //if ($sample_output=="") $sample_output="\n";

      $hint = $_POST['hint'];
     // $hint = str_replace("<p>", "", $hint);
    //  $hint = str_replace("</p>", "<br />", $hint);
    //  $hint = str_replace(",", "&#44;", $hint);

      $source = $_POST['source'];
      $remote_oj= $_POST['remote_oj'];
      $remote_id = $_POST['remote_id'];
      $spj = $_POST['spj'];

      if (false) {
        $title = stripslashes($title);
        $time_limit = stripslashes($time_limit);
        $memory_limit = stripslashes($memory_limit);
        $description = stripslashes($description);
        $input = stripslashes($input);
        $output = stripslashes($output);
        $sample_input = stripslashes($sample_input);
        $sample_output = stripslashes($sample_output);
        //$test_input = stripslashes($test_input);
        //$test_output = stripslashes($test_output);
        $hint = stripslashes($hint);
        $source = stripslashes($source);
        $spj = stripslashes($spj);
      }

      $title = ($title);
      $description = ($description);
      $input = ($input);
      $output = ($output);
      $hint = ($hint);
      $basedir = $OJ_DATA."/$id";

      echo "<div class='alert alert-success' role='status'>";

      if ($sample_input && file_exists($basedir."/sample.in")) {
        //mkdir($basedir);
        $fp = @fopen($basedir."/sample.in","w");
        if($fp){
            fputs($fp,preg_replace("(\r\n)","\n",$sample_input));
            fclose($fp);
        }

        $fp = @fopen($basedir."/sample.out","w");
        if($fp){
            fputs($fp,preg_replace("(\r\n)","\n",$sample_output));
            fclose($fp);
        }
      }

      $spj = intval($spj);

      $sql = "UPDATE `problem` SET `title`=?,`time_limit`=?,`memory_limit`=?, `description`=?,`input`=?,`output`=?,`sample_input`=?,`sample_output`=?,`hint`=?,`source`=?,`spj`=?,remote_oj=?,remote_id=?,`in_date`=NOW(),`coin`=? WHERE `problem_id`=?";

      //echo "SQL: " . $sql . "<br>";
      //echo "Params: remote_oj=[$remote_oj] (" . strlen($remote_oj) . "), remote_id=[$remote_id] (" . strlen($remote_id) . ")<br>"; 
      $coin = intval($_POST['coin'] ?? 1);
      @pdo_query($sql,$title,$time_limit,$memory_limit,$description,$input,$output,$sample_input,$sample_output,$hint,$source,$spj,$remote_oj,$remote_id,$coin,$id);
  
      echo "문제를 수정했습니다. ";
      echo "<a href='../problem.php?id=$id'>문제 보기</a>";
      echo "</div>";
    }
    ?>
</body>
</html>

