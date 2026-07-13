<?php 
  require_once("../include/db_info.inc.php");
  require_once("admin-header.php");
  if (!(isset($_SESSION[$OJ_NAME.'_'.'administrator']) || isset($_SESSION[$OJ_NAME.'_'.'contest_creator']) || isset($_SESSION[$OJ_NAME.'_'.'problem_editor']))) {
    echo "<a href='../loginpage.php'> $MSG_Login </a>";
    exit(1);
  }
?>
	  
<html>
<head>
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Cache-Control" content="no-cache">
  <meta http-equiv="Content-Language" content="ko">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>Problem Add</title>
  <style>
    html, body { margin: 0; min-height: 100%; width: 100%; }
    body { background: #fff !important; opacity: 1 !important; }
    body.preview-active { overflow: hidden; }
    #main { box-sizing: border-box; width: 100%; padding: 0 24px 24px; }
    #main.preview-open { bottom: 0; left: 0; margin: 0 !important; overflow-y: auto; position: fixed; top: 0; width: 50%; }
    .problem-page-title { background: #fff; border-bottom: 1px solid #e5e7eb; margin: 0 -24px 20px; padding: 16px 24px 14px; position: sticky; top: 0; z-index: 20; }
    .problem-page-title h3 { margin: 0; }
    #main .ke-container { box-sizing: border-box; width: 100% !important; }
    #preview { box-sizing: border-box; background: #fff; border-left: 1px solid #d8dee8; bottom: 0; position: fixed; right: 0; top: 0; width: 50%; z-index: 1000; }
    #previewFrame { border: 0; display: block; height: 100%; width: 100%; }
    @media (max-width: 1000px) {
      body.preview-active { overflow: auto; }
      #main.preview-open { position: static; width: 100%; }
      #preview { border-left: 0; border-top: 1px solid #d8dee8; height: 720px; position: static; width: 100%; }
    }
  </style>
</head>	  
	  <?php
  $page_heading = $MSG_PROBLEM." - ".$MSG_ADD;
  include_once("kindeditor.php") ;
  $source=pdo_query("select source from problem order by problem_id desc limit 1"); //默认续用最后一次的分类标签
  if(!empty($source)&&isset($source[0]))$source=$source[0][0];else $source="";
?>

<body>
  <div id="main">
    <header class="problem-page-title"><h3><?php echo htmlentities($page_heading, ENT_QUOTES, 'UTF-8')?></h3></header>
    <form method=POST action=problem_add.php>
      <input type=hidden name=problem_id value="New Problem">
        <p align=left>
  <div class="ui toggle checkbox">
        <input type="checkbox" id="preview-toggle" checked>
        <label for="preview-toggle">문제 미리보기</label>
  </div>
	&nbsp;&nbsp;
  <div class="ui toggle checkbox">
        <input type="checkbox" id="full-toggle" checked>
        <label for="full-toggle">전체 항목 표시</label>
  </div>
          <?php echo "<h3>".$MSG_TITLE."</h3>"?>
          <input class="input input-large" style="width:100%;" type=text name='title' id='title' > 
		<input class="btn btn-success" type=submit value='<?php echo $MSG_SAVE?>' name=submit> 
		<input class='btn btn-primary' id='ai_bt' type=button value='AI 생성' onclick='ai_gen()' >
		<input class='btn btn-danger'  type=reset value='<?php echo $MSG_RESET?>' >
	</p>
        <p align=left>
          <?php echo $MSG_Time_Limit?>
          <input class="input input-mini" type=number min="0.001" max="300" step="0.001" name=time_limit size=20 value=1> sec
          <?php echo $MSG_Memory_Limit?>
          <input class="input input-mini" type=number min="1" max="2048" step="1" name=memory_limit size=20 value=128> MiB<br><br>
          <?php if (isset($_SESSION[$OJ_NAME."_"."administrator"]) || isset($_SESSION[$OJ_NAME."_"."problem_editor"])) { ?>
          <?php echo "$MSG_COIN"?>
          <input class="input input-mini" type=number min="0" max="9999" step="1" name=coin size=20 value=1> <br><br>
          <?php } ?>
        </p>
        <p align=left>
          <?php echo "<h4>".$MSG_Description."(<64kB)</h4>"?>
	  <textarea class="kindeditor" rows=33 name=description cols=80></textarea><br>
        </p>
	<span class="full" >
        <p align=left>
          <?php echo "<h4>".$MSG_Input."(<64kB)</h4>"?>
          <textarea class="kindeditor" rows=13 name=input cols=80></textarea><br>
        </p>
        <p align=left>
          <?php echo "<h4>".$MSG_Output."(<64kB)</h4>"?>
          <textarea class="kindeditor" rows=13 name=output cols=80></textarea><br>
        </p>
        <p align=left>
          <?php echo "<h4>".$MSG_Sample_Input."(<64kB)</h4>"?>
          <textarea  class="input input-large" style="width:100%;" rows=13 name=sample_input></textarea><br><br>
        </p>
        <p align=left>
          <?php echo "<h4>".$MSG_Sample_Output."(<64kB)</h4>"?>
          <textarea  class="input input-large" style="width:100%;" rows=13 name=sample_output></textarea><br><br>
        </p>
        <p align=left>
          <?php echo "<h4>".$MSG_Test_Input."</h4>"?>
          <?php echo "(".$MSG_HELP_MORE_TESTDATA_LATER.")"?><br>
          <textarea class="input input-large" style="width:100%;" rows=13 name=test_input></textarea><br><br>
        </p>
        <p align=left>
          <?php echo "<h4>".$MSG_Test_Output."</h4>"?>
          <?php echo "(".$MSG_HELP_MORE_TESTDATA_LATER.")"?><br>
          <textarea class="input input-large" style="width:100%;" rows=13 name=test_output></textarea><br><br>
        </p>
        <p align=left>
          <?php echo "<h4>".$MSG_HINT."(<64kB)</h4>"?>
          <textarea class="kindeditor" rows=13 name=hint cols=80></textarea><br>
        </p>
	</span>
        <p>
          <?php echo "<h4>".$MSG_SPJ."</h4>"?>
	  <input type=radio name=spj value='0' checked ><?php echo $MSG_NJ?> 추가 테스트 데이터는 문제 등록 후 입력할 수 있습니다.<br>
	  <input type=radio name=spj value='1' ><?php echo $MSG_SPJ?> <?php echo "(".$MSG_HELP_SPJ.")"?><br>
	  <input type=radio name=spj value='2' ><?php echo $MSG_RTJ?> (선택형·빈칸형 문제에 사용, <a target='_blank' href='http://hustoj.com'>사용법</a>)<br>
      <input type=radio name=spj value='3' ><?php echo $MSG_INTERACT?><br>
        </p>
        <p align=left>
          <?php echo "<h4>".$MSG_SOURCE."</h4>"?>
          <textarea name=source style="width:100%;" rows=1><?php echo htmlentities($source,ENT_QUOTES,'UTF-8') ?></textarea><br><br>
        </p>
        <p align=left><?php echo "<h4>".$MSG_CONTEST."</h4>"?>
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
        </p>

        <div align=center>
          <?php require_once("../include/set_post_key.php");?>
          <input type=submit value='<?php echo $MSG_SAVE?>' name=submit>
        </div>
     
    </form>
  </div>
<script src="<?php echo $OJ_CDN_URL."/template/bs3/"?>marked.min.js"></script>
<script>
  function transform(){
        let submitURL="../problem.php?id=1000";
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
	let preFrame=$("#previewFrame").contents();
	let title=$("input[name=title]").val();
	preFrame.find("h1:first").html(title);
	let time=$("input[name=time_limit]").val();
	preFrame.find("span.ui.label").eq(0).html("<?php echo $MSG_Time_Limit ?>："+time);
	let memory=$("input[name=memory_limit]").val();
	preFrame.find("span.ui.label").eq(1).html("<?php echo $MSG_Memory_Limit ?>："+memory);
	
	let description=$("textarea[name='description']").val();
	console.log("description:"+description);
	renderMarkdownPreview(preFrame.find("#description"), description, true);
  
	let input=$("textarea[name='input']").val();
	renderMarkdownPreview(preFrame.find("#input"), input, false);
	let output=$("textarea[name='output']").val();
	renderMarkdownPreview(preFrame.find("#output"), output, false);

	let sinput=$("textarea[name='sample_input']").val();
	preFrame.find("#sinput").html(sinput);
	let soutput=$("textarea[name='sample_output']").val();
	preFrame.find("#soutput").html(soutput);
	let hint=$("textarea[name='hint']").val();
	renderMarkdownPreview(preFrame.find("#hint"), hint, false);
	if($("#previewFrame")[0] != undefined && $("#previewFrame")[0].contentWindow.MathJax != undefined ) $("#previewFrame")[0].contentWindow.MathJax.typeset();
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
            // 默认开启预览功能
           <?php if (!(isset($OJ_OLD_FASHINED) && $OJ_OLD_FASHINED )) echo " transform();" ?>
            
            // 监听checkbox的点击事件
            $('#preview-toggle').change(function() {
                if(this.checked) {
                    transform();
                } else {
                    // 假设这里是关闭预览的函数
                    untransform();
                }
            });
            $('#full-toggle').change(function() {
		$(".full").toggle();
                sync();
            });
        });
function untransform() {
    let main = $("#main");
    $("body").removeClass("preview-active");
    main.removeClass("preview-open");
    main.css("width", "");
    main.css("margin-left", "");

    // 移除预览的 iframe
    $("#preview").remove();

  
    // 移除同步事件
    $("input").off('keyup', sync);
    $("textarea").off('keyup', sync);
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
