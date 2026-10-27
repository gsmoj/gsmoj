<?php 
  require_once("../include/db_info.inc.php");
  require_once("admin-header.php");
  if (!(isset($_SESSION[$OJ_NAME.'_'.'administrator']) || isset($_SESSION[$OJ_NAME.'_'.'contest_creator']) || isset($_SESSION[$OJ_NAME.'_'.'problem_editor']))) {
    echo "<a href='../loginpage.php'> $MSG_Login </a>";
    exit(1);
  }

  $problem_form_placeholders = array(
    "title" => isset($MSG_PROBLEM_TITLE_PLACEHOLDER) ? $MSG_PROBLEM_TITLE_PLACEHOLDER : "예: 두 수의 합",
    "description" => isset($MSG_PROBLEM_DESCRIPTION_PLACEHOLDER) ? $MSG_PROBLEM_DESCRIPTION_PLACEHOLDER : "예: 두 정수 A와 B가 주어집니다. 두 수의 합을 구하세요.",
    "input" => isset($MSG_PROBLEM_INPUT_PLACEHOLDER) ? $MSG_PROBLEM_INPUT_PLACEHOLDER : "예: 첫째 줄에 두 정수 A와 B가 공백으로 구분되어 주어집니다.",
    "output" => isset($MSG_PROBLEM_OUTPUT_PLACEHOLDER) ? $MSG_PROBLEM_OUTPUT_PLACEHOLDER : "예: 첫째 줄에 A와 B의 합을 출력합니다.",
    "sample_input" => isset($MSG_PROBLEM_SAMPLE_INPUT_PLACEHOLDER) ? $MSG_PROBLEM_SAMPLE_INPUT_PLACEHOLDER : "예:\n1 2",
    "sample_output" => isset($MSG_PROBLEM_SAMPLE_OUTPUT_PLACEHOLDER) ? $MSG_PROBLEM_SAMPLE_OUTPUT_PLACEHOLDER : "예:\n3",
    "hint" => isset($MSG_PROBLEM_HINT_PLACEHOLDER) ? $MSG_PROBLEM_HINT_PLACEHOLDER : "예: 문제 풀이에 필요한 추가 설명을 입력하세요.",
    "source" => isset($MSG_PROBLEM_SOURCE_PLACEHOLDER) ? $MSG_PROBLEM_SOURCE_PLACEHOLDER : "예: 교내 알고리즘 수업"
  );

  function problem_placeholder_attr($value) {
    return str_replace(array("\r", "\n"), array("", "&#10;"), htmlentities($value, ENT_QUOTES, "UTF-8"));
  }

?>
	  
<html>
<head>
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Cache-Control" content="no-cache">
  <meta http-equiv="Content-Language" content="ko">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title><?php echo htmlentities($MSG_PROBLEM." - ".$MSG_ADD, ENT_QUOTES, "UTF-8")?></title>
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
    #preview { box-sizing: border-box; background: #f8fafc; border-left: 1px solid #d8dee8; bottom: 0; overflow-y: scroll; overscroll-behavior: contain; position: fixed; right: 0; scrollbar-color: #94a3b8 #e2e8f0; scrollbar-gutter: stable; top: 0; width: 50%; z-index: 1000; }
    #preview::-webkit-scrollbar { width: 12px; }
    #preview::-webkit-scrollbar-track { background: #e2e8f0; }
    #preview::-webkit-scrollbar-thumb { background: #94a3b8; border: 3px solid #e2e8f0; border-radius: 999px; }
    #preview::-webkit-scrollbar-thumb:hover { background: #64748b; }
    .problem-preview-document { box-sizing: border-box; min-height: 100%; padding: 28px 24px; color: #1f2937; }
    .problem-preview-heading { margin-bottom: 24px; text-align: center; }
    .problem-preview-heading h1 { margin: 0 0 14px; font-size: 28px; overflow-wrap: anywhere; }
    .problem-preview-limits { display: flex; justify-content: center; flex-wrap: wrap; gap: 8px; }
    .problem-preview-label { padding: 7px 11px; border-radius: 5px; background: #e5e7eb; color: #374151; font-size: 13px; font-weight: 700; }
    .problem-preview-section { margin: 0 0 18px; }
    .problem-preview-section h4 { margin: 0; padding: 11px 14px; border: 1px solid #dbe2ea; border-radius: 8px 8px 0 0; background: #f1f5f9; font-size: 15px; }
    .problem-preview-section-heading { align-items: center; display: flex; justify-content: space-between; gap: 12px; }
    #preview .problem-preview-copy { flex: 0 0 auto; height: 30px; min-height: 30px; min-width: 58px; padding: 0 10px; color: #334155; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; font-size: 12px; font-weight: 700; line-height: 28px; }
    #preview .problem-preview-copy:hover:not(:disabled) { color: #1d4ed8; border-color: #93c5fd; background: #eff6ff; }
    #preview .problem-preview-copy:focus-visible { outline: 2px solid #2563eb; outline-offset: 2px; }
    #preview .problem-preview-copy:disabled { color: #94a3b8; cursor: not-allowed; opacity: .65; }
    .problem-preview-content { min-height: 48px; padding: 15px 16px; border: 1px solid #dbe2ea; border-top: 0; border-radius: 0 0 8px 8px; background: #fff; line-height: 1.7; overflow-wrap: anywhere; }
    .problem-preview-content pre { margin: 0; white-space: pre-wrap; word-break: break-word; }
    .problem-preview-placeholder { color: #94a3b8 !important; font-weight: 500 !important; }
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
  $page_heading = $MSG_PROBLEM." - ".$MSG_ADD;
  $source=pdo_query("select source from problem order by problem_id desc limit 1"); //默认续用最后一次的分类标签
  if(!empty($source)&&isset($source[0]))$source=$source[0][0];else $source="";
?>

<body>
  <div id="main">
    <header class="problem-page-title"><h3><?php echo htmlentities($page_heading, ENT_QUOTES, 'UTF-8')?></h3></header>
    <form method=POST action=problem_add.php>
      <input type=hidden name=problem_id value="New Problem">
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
        <h3><label for="title"><?php echo $MSG_TITLE?></label></h3>
        <input class="input input-large" style="width:100%;" type=text name='title' id='title' placeholder="<?php echo problem_placeholder_attr($problem_form_placeholders['title'])?>">
        <div class="problem-form-actions">
          <input class="btn btn-success" type=submit value='<?php echo $MSG_SAVE?>' name=submit>
          <input class='btn btn-primary' id='ai_bt' type=button value='AI 생성' onclick='ai_gen()'>
          <input class='btn btn-danger' type=reset value='<?php echo $MSG_RESET?>'>
        </div>
      </div>
        <section class="problem-form-section">
          <?php echo $MSG_Time_Limit?>
          <input class="input input-mini" type=number min="0.001" max="300" step="0.001" name=time_limit size=20 value=1> sec
          <?php echo $MSG_Memory_Limit?>
          <input class="input input-mini" type=number min="1" max="2048" step="1" name=memory_limit size=20 value=128> MiB<br><br>
          <?php if (isset($_SESSION[$OJ_NAME."_"."administrator"]) || isset($_SESSION[$OJ_NAME."_"."problem_editor"])) { ?>
          <?php echo "$MSG_COIN"?>
          <input class="input input-mini" type=number min="0" max="9999" step="1" name=coin size=20 value=1> <br><br>
          <?php } ?>
        </section>
        <section class="problem-form-section">
          <?php echo "<h4>".$MSG_Description."(<64kB)</h4>"?>
	  <textarea class="kindeditor" rows=33 name=description cols=80 placeholder="<?php echo problem_placeholder_attr($problem_form_placeholders['description'])?>" data-placeholder="<?php echo problem_placeholder_attr($problem_form_placeholders['description'])?>"></textarea><br>
        </section>
	<div class="full">
        <section class="problem-form-section">
          <?php echo "<h4>".$MSG_Input."(<64kB)</h4>"?>
          <textarea class="kindeditor" rows=13 name=input cols=80 placeholder="<?php echo problem_placeholder_attr($problem_form_placeholders['input'])?>" data-placeholder="<?php echo problem_placeholder_attr($problem_form_placeholders['input'])?>"></textarea><br>
        </section>
        <section class="problem-form-section">
          <?php echo "<h4>".$MSG_Output."(<64kB)</h4>"?>
          <textarea class="kindeditor" rows=13 name=output cols=80 placeholder="<?php echo problem_placeholder_attr($problem_form_placeholders['output'])?>" data-placeholder="<?php echo problem_placeholder_attr($problem_form_placeholders['output'])?>"></textarea><br>
        </section>
        <section class="problem-form-section">
          <?php echo "<h4>".$MSG_Sample_Input."(<64kB)</h4>"?>
          <textarea class="input input-large" style="width:100%;" rows=13 name=sample_input placeholder="<?php echo problem_placeholder_attr($problem_form_placeholders['sample_input'])?>"></textarea><br><br>
        </section>
        <section class="problem-form-section">
          <?php echo "<h4>".$MSG_Sample_Output."(<64kB)</h4>"?>
          <textarea class="input input-large" style="width:100%;" rows=13 name=sample_output placeholder="<?php echo problem_placeholder_attr($problem_form_placeholders['sample_output'])?>"></textarea><br><br>
        </section>
        <section class="problem-form-section">
          <?php echo "<h4>".$MSG_Test_Input."</h4>"?>
          <?php echo "(".$MSG_HELP_MORE_TESTDATA_LATER.")"?><br>
          <textarea class="input input-large" style="width:100%;" rows=13 name=test_input placeholder="<?php echo problem_placeholder_attr($problem_form_placeholders['sample_input'])?>"></textarea><br><br>
        </section>
        <section class="problem-form-section">
          <?php echo "<h4>".$MSG_Test_Output."</h4>"?>
          <?php echo "(".$MSG_HELP_MORE_TESTDATA_LATER.")"?><br>
          <textarea class="input input-large" style="width:100%;" rows=13 name=test_output placeholder="<?php echo problem_placeholder_attr($problem_form_placeholders['sample_output'])?>"></textarea><br><br>
        </section>
        <section class="problem-form-section">
          <?php echo "<h4>".$MSG_HINT."(<64kB)</h4>"?>
          <textarea class="kindeditor" rows=13 name=hint cols=80 placeholder="<?php echo problem_placeholder_attr($problem_form_placeholders['hint'])?>" data-placeholder="<?php echo problem_placeholder_attr($problem_form_placeholders['hint'])?>"></textarea><br>
        </section>
	</div>
        <section class="problem-form-section">
          <?php echo "<h4>".$MSG_SPJ."</h4>"?>
	  <input type=radio name=spj value='0' checked ><?php echo $MSG_NJ?> 추가 테스트 데이터는 문제 등록 후 입력할 수 있습니다.<br>
	  <input type=radio name=spj value='1' ><?php echo $MSG_SPJ?> <?php echo "(".$MSG_HELP_SPJ.")"?><br>
	  <input type=radio name=spj value='2' ><?php echo $MSG_RTJ?> (선택형·빈칸형 문제에 사용, <a target='_blank' href='http://hustoj.com'>사용법</a>)<br>
      <input type=radio name=spj value='3' ><?php echo $MSG_INTERACT?><br>
        </section>
        <section class="problem-form-section">
          <?php echo "<h4>".$MSG_SOURCE."</h4>"?>
          <textarea name=source style="width:100%;" rows=1 placeholder="<?php echo problem_placeholder_attr($problem_form_placeholders['source'])?>"><?php echo htmlentities($source,ENT_QUOTES,'UTF-8') ?></textarea><br><br>
        </section>
        <section class="problem-form-section"><?php echo "<h4>".$MSG_CONTEST."</h4>"?>
          <select name=contest_id>
            <?php
            $sql="SELECT `contest_id`,`title` FROM `contest` WHERE `start_time`>NOW() order by `contest_id`";
            $result=pdo_query($sql);
            echo "<option value=''>none</option>";
            if (count($result)==0) {
            }
            else {
              foreach ($result as $row) {
                echo "<option value='{$row['contest_id']}'>{$row['contest_id']} {$row['title']}</option>";
              }
            }?>
          </select>
        </section>

        <div align=center>
          <?php require_once("../include/set_post_key.php");?>
          <input type=submit value='<?php echo $MSG_SAVE?>' name=submit>
        </div>
     
    </form>
  </div>
  <template id="problem-preview-template">
    <div class="problem-preview-document">
      <header class="problem-preview-heading">
        <h1 id="preview-title" class="problem-preview-placeholder"><?php echo problem_placeholder_attr($problem_form_placeholders['title'])?></h1>
        <div class="problem-preview-limits">
          <span id="preview-time" class="problem-preview-label"></span>
          <span id="preview-memory" class="problem-preview-label"></span>
        </div>
      </header>
      <section class="problem-preview-section">
        <h4><?php echo htmlentities($MSG_Description, ENT_QUOTES, 'UTF-8')?></h4>
        <div id="description" class="problem-preview-content"></div>
      </section>
      <section class="problem-preview-section">
        <h4><?php echo htmlentities($MSG_Input, ENT_QUOTES, 'UTF-8')?></h4>
        <div id="input" class="problem-preview-content"></div>
      </section>
      <section class="problem-preview-section">
        <h4><?php echo htmlentities($MSG_Output, ENT_QUOTES, 'UTF-8')?></h4>
        <div id="output" class="problem-preview-content"></div>
      </section>
      <section class="problem-preview-section">
        <h4 class="problem-preview-section-heading">
          <span><?php echo htmlentities($MSG_Sample_Input, ENT_QUOTES, 'UTF-8')?></span>
          <button type="button" id="copy-sample-input" class="problem-preview-copy" aria-label="<?php echo htmlentities($MSG_Sample_Input." ".$MSG_COPY, ENT_QUOTES, 'UTF-8')?>" disabled><?php echo htmlentities($MSG_COPY, ENT_QUOTES, 'UTF-8')?></button>
        </h4>
        <div class="problem-preview-content"><pre><code id="sinput"></code></pre></div>
      </section>
      <section class="problem-preview-section">
        <h4><?php echo htmlentities($MSG_Sample_Output, ENT_QUOTES, 'UTF-8')?></h4>
        <div class="problem-preview-content"><pre><code id="soutput"></code></pre></div>
      </section>
      <section class="problem-preview-section">
        <h4><?php echo htmlentities($MSG_HINT, ENT_QUOTES, 'UTF-8')?></h4>
        <div id="hint" class="problem-preview-content"></div>
      </section>
    </div>
  </template>
<?php include_once("kindeditor.php"); ?>
<script src="../template/bs3/marked.min.js"></script>
<script>
  function transform(){
        let main=$("#main");
        if ($("#preview").length) return;
        let template=document.getElementById("problem-preview-template");
        if (!template) return;
        $("body").addClass("preview-active");
        main.removeClass("container").addClass("preview-open");
        let preview = $("<div>", { id: "preview" });
        preview.append(template.content.cloneNode(true));
        main.parent().append(preview);
	preview.on("click", "#copy-sample-input", copySampleInput);
	$("input, textarea").on("input.problemPreview change.problemPreview", sync);
        sync();
  }

  function sync(){
    let preview=$("#preview");
    if (!preview.length) return;
	let title=$("input[name=title]").val().trim();
	preview.find("#preview-title")
      .text(title || <?php echo json_encode($problem_form_placeholders['title'], JSON_UNESCAPED_UNICODE)?>)
      .toggleClass("problem-preview-placeholder", !title);
	let time=$("input[name=time_limit]").val();
	preview.find("#preview-time").text(<?php echo json_encode($MSG_Time_Limit, JSON_UNESCAPED_UNICODE)?>+": "+time+" sec");
	let memory=$("input[name=memory_limit]").val();
	preview.find("#preview-memory").text(<?php echo json_encode($MSG_Memory_Limit, JSON_UNESCAPED_UNICODE)?>+": "+memory+" MiB");
	
	let description=$("textarea[name='description']").val();
	renderMarkdownPreview(preview.find("#description"), description, true, <?php echo json_encode($problem_form_placeholders['description'], JSON_UNESCAPED_UNICODE)?>);
  
	let input=$("textarea[name='input']").val();
	renderMarkdownPreview(preview.find("#input"), input, false, <?php echo json_encode($problem_form_placeholders['input'], JSON_UNESCAPED_UNICODE)?>);
	let output=$("textarea[name='output']").val();
	renderMarkdownPreview(preview.find("#output"), output, false, <?php echo json_encode($problem_form_placeholders['output'], JSON_UNESCAPED_UNICODE)?>);

	let sinput=$("textarea[name='sample_input']").val();
	preview.find("#sinput")
      .text(sinput || <?php echo json_encode($problem_form_placeholders['sample_input'], JSON_UNESCAPED_UNICODE)?>)
      .toggleClass("problem-preview-placeholder", !sinput.trim());
	preview.find("#copy-sample-input").prop("disabled", !sinput.trim());
	let soutput=$("textarea[name='sample_output']").val();
	preview.find("#soutput")
      .text(soutput || <?php echo json_encode($problem_form_placeholders['sample_output'], JSON_UNESCAPED_UNICODE)?>)
      .toggleClass("problem-preview-placeholder", !soutput.trim());
	let hint=$("textarea[name='hint']").val();
	renderMarkdownPreview(preview.find("#hint"), hint, false, <?php echo json_encode($problem_form_placeholders['hint'], JSON_UNESCAPED_UNICODE)?>);
  }

  function renderMarkdownPreview(target, content, autoSelect, placeholder) {
    let source = content || "";
    let existing = $("<div>").html(source).find(".md").first();
    if (existing.length) source = existing.html();
    let isPlaceholder = !source.trim();
    if (isPlaceholder) source = placeholder || "\u00a0";
    let wrapper = $("<span>").addClass(autoSelect ? "md auto_select" : "md");
    wrapper.toggleClass("problem-preview-placeholder", isPlaceholder);
    if (isPlaceholder || typeof marked === "undefined")
      wrapper.text(source);
    else
      wrapper.html(marked.parse(source, { breaks: true, gfm: true }));
    target.empty().append(wrapper);
  }

  function copySampleInput(){
    let button=$(this);
    let sampleInput=$("textarea[name='sample_input']").val();
    if (!sampleInput.trim()) return;
    writePreviewClipboard(sampleInput).then(function(){
      showCopyResult(button, <?php echo json_encode($MSG_COPY." ".$MSG_SUCCESS."!", JSON_UNESCAPED_UNICODE)?>);
    }).catch(function(){
      showCopyResult(button, <?php echo json_encode($MSG_COPY." ".$MSG_FAIL."!", JSON_UNESCAPED_UNICODE)?>);
    });
  }

  function writePreviewClipboard(content){
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(content);
    }
    return new Promise(function(resolve, reject){
      let temporary=$("<textarea>")
        .attr("aria-hidden", "true")
        .css({ left: "-9999px", opacity: 0, position: "fixed", top: 0 })
        .val(content)
        .appendTo("body");
      temporary[0].focus();
      temporary[0].select();
      try {
        if (document.execCommand("copy")) resolve();
        else reject();
      } catch (error) {
        reject(error);
      } finally {
        temporary.remove();
      }
    });
  }

  function showCopyResult(button, message){
    button.text(message);
    window.setTimeout(function(){
      if ($.contains(document, button[0])) button.text(<?php echo json_encode($MSG_COPY, JSON_UNESCAPED_UNICODE)?>);
    }, 1500);
  }
 
   $(document).ready(function(){
            let previewToggle=$('#preview-toggle');
            previewToggle.change(function() {
                if(this.checked) {
                    transform();
                } else {
                    untransform();
                }
            });
            $('#full-toggle').change(function() {
		$(".full").toggle(this.checked);
                sync();
            });
            if(previewToggle.prop('checked')) transform();
        });
function untransform() {
    let main = $("#main");
    $("body").removeClass("preview-active");
    main.removeClass("preview-open");
    main.css("width", "");
    main.css("margin-left", "");

    $("#preview").remove();
    $("input, textarea").off(".problemPreview");
}
function fill_data( data ){
   data=data.replace(/<think>[\s\S]*?<\/think>/g, '');
   let title=$('#title').val();
	    console.log(title);
		if(title==""){
				$('#title').val(data);
		}else{
		    // 尝试以JSON格式解析AI输出内容
		    let parsedData = null;
		    
		    // 先检查是否是分隔符格式
		    if(data.indexOf('###TITLE###') !== -1){
			console.log("检测到分隔符格式");
			let sections = data.split('###');
			parsedData = {};
			
			for(let i = 0; i < sections.length; i++){
			    let section = sections[i].trim();
			    if(section.startsWith('TITLE###')){
				parsedData.title = section.replace('TITLE###', '').trim();
			    } else if(section.startsWith('DESCRIPTION###')){
				parsedData.description = section.replace('DESCRIPTION###', '').trim();
			    } else if(section.startsWith('INPUT###')){
				parsedData.input = section.replace('INPUT###', '').trim();
			    } else if(section.startsWith('OUTPUT###')){
				parsedData.output = section.replace('OUTPUT###', '').trim();
			    } else if(section.startsWith('SAMPLE_INPUT###')){
				parsedData.sample_input = section.replace('SAMPLE_INPUT###', '').trim();
			    } else if(section.startsWith('SAMPLE_OUTPUT###')){
				parsedData.sample_output = section.replace('SAMPLE_OUTPUT###', '').trim();
			    } else if(section.startsWith('HINT###')){
				parsedData.hint = section.replace('HINT###', '').trim();
			    }
			}
		    } else {
			// 尝试解析JSON
			try {
			    console.log("尝试解析JSON格式");
			    parsedData = JSON.parse(data);
			    console.log("JSON解析成功");
			} catch(e) {
			    console.log("JSON解析失败,使用旧格式");
			    // 如果解析失败,按旧格式处理
			    let description = data.trim();
			    $("textarea[name='description']").val(description);
			    parsedData = { description: description };
			}
		    }
		    
		    // 如果成功解析出结构化数据,填充表单
		    if(parsedData && typeof parsedData === 'object') {
			console.log("=== 解析后的数据 ===");
			console.log(parsedData);
			
			// 填充到对应的表单字段
			if(parsedData.title) {
			    $('#title').val(parsedData.title);
			    console.log("填充title:", parsedData.title);
			}
			
			// 处理description - 需要特殊处理kindeditor
			if(parsedData.description) {
			    let descContent = parsedData.description;
			    // 先尝试设置textarea的值
			    $("textarea[name='description']").val(descContent);
			    // 如果有kindeditor实例,也设置它的值
			    try {
				if(typeof KindEditor !== 'undefined') {
				    let editor = KindEditor.instances[0]; // 第一个编辑器是description
				    if(editor) {
					editor.html(descContent);
					console.log("通过KindEditor设置description成功");
				    }
				}
			    } catch(e) {
				console.log("KindEditor设置失败,已使用textarea方式:", e);
			    }
			    console.log("填充description");
			}
			
			// 处理input - 也是kindeditor
			if(parsedData.input) {
			    let inputContent = parsedData.input;
			    $("textarea[name='input']").val(inputContent);
			    try {
				if(typeof KindEditor !== 'undefined') {
				    let editor = KindEditor.instances[1]; // 第二个编辑器是input
				    if(editor) {
					editor.html(inputContent);
				    }
				}
			    } catch(e) {
				console.log("KindEditor input设置失败:", e);
			    }
			    console.log("填充input");
			}
			
			// 处理output - 也是kindeditor
			if(parsedData.output) {
			    let outputContent = parsedData.output;
			    $("textarea[name='output']").val(outputContent);
			    try {
				if(typeof KindEditor !== 'undefined') {
				    let editor = KindEditor.instances[2]; // 第三个编辑器是output
				    if(editor) {
					editor.html(outputContent);
				    }
				}
			    } catch(e) {
				console.log("KindEditor output设置失败:", e);
			    }
			    console.log("填充output");
			}
			
			if(parsedData.sample_input) {
			    $("textarea[name='sample_input']").val(parsedData.sample_input);
			    console.log("填充sample_input");
			}
			if(parsedData.sample_output) {
			    $("textarea[name='sample_output']").val(parsedData.sample_output);
			    console.log("填充sample_output");
			}
			
			// 处理hint - 也是kindeditor
			if(parsedData.hint) {
			    let hintContent = parsedData.hint;
			    $("textarea[name='hint']").val(hintContent);
			    try {
				if(typeof KindEditor !== 'undefined') {
				    let editor = KindEditor.instances[3]; // 第四个编辑器是hint
				    if(editor) {
					editor.html(hintContent);
				    }
				}
			    } catch(e) {
				console.log("KindEditor hint设置失败:", e);
			    }
			    console.log("填充hint");
			}
		     }

   	}
	window.setTimeout('sync()',1000);
	$('#ai_bt').prop('disabled', false);;
	$('#ai_bt').val('AI 생성');
}
function pull_result(id){
	console.log(id);
    $.ajax({
	url: '../aiapi/ajax.php', 
	type: 'GET',
	data: { id: id },
	success: function(data) {
		if(data=="waiting"){
			window.setTimeout('pull_result('+id+')',1000);
		}else{
			fill_data(data);
		}
	},
	error: function() {
	    $('#ai_bt').val('데이터를 가져오지 못했습니다.');
	$('#ai_bt').prop('disabled', false);
	}
    });
}
	function ai_gen(filename){
		    let oldval=$('#ai_bt').val();
		    $('#ai_bt').val('AI 생성 중... 잠시 기다려 주세요.');
		    $('#ai_bt').prop('disabled', true);;
		    let title=$('#title').val();
		    $.ajax({
		    	url: '../<?php echo $OJ_AI_API_URL?>', 
			type: 'GET',
			data: { title: title },
			success: function(data) {
				if(parseInt(data)>0)
					window.setTimeout('pull_result('+data+')',1000);
			},
			error: function() {
			    $('#ai_bt').val('데이터를 가져오지 못했습니다.');
		    	$('#ai_bt').prop('disabled', false);
			}
		    });
	}

</script>
</body>
</html>
