
<?php $show_title="$MSG_SUBMIT - $OJ_NAME"; ?>
<?php include("template/$OJ_TEMPLATE/header.php");?>

  <style>
html, body { min-width:480px; min-height:100%; background:#f8fafc; }
body { margin:0 !important; overflow:auto !important; }
#frmSolution { box-sizing:border-box; width:100%; min-height:100vh; padding:1rem; text-align:left; }
#frmSolution > span:first-of-type { display:inline-flex; align-items:center; min-height:38px; margin-right:.75rem; color:#334155 !important; font-weight:600; }
#language_span { display:inline-flex; align-items:center; flex-wrap:wrap; gap:.5rem; color:#334155; }
#language { min-width:9rem; padding:.45rem .6rem; border:1px solid #d8dee8; border-radius:.4rem; background:#fff; }
#Submit { margin-left:.25rem; }
#source { box-sizing:border-box; width:100% !important; border:1px solid #d8dee8; border-radius:.5rem; background:#fff !important; box-shadow:0 1px 3px rgba(15,23,42,.08); }
#reinfo { width:100%; }
#source {
    width: 80%;
    height: 600px;
    background-color:rgb(255,225,225,0.5);
}

.ace-chrome .ace_marker-layer .ace_active-line{   /* 현재 줄 */
   background-color: rgba(0,0,199,0.3);
}
            .button, input, optgroup, select, textarea {  /* 선택형 문제 번호 크기 */
    font-family: sans-serif;
    font-size: 150%;
    line-height: 1.2;

}
<?php if (time() < strtotime('2025-3-31')) { ?>
body{
        background: url("http://m.hustoj.com:8090/bg/nz.gif") 0% 0% / 100% no-repeat;
}
<?php } ?>
 /* 오류가 발생한 줄을 표시하는 스타일 */
  .ace_error_marker {
    position: absolute;
    background-color: rgba(255, 0, 0, 0.3); /* 빨간색 반투명 배경 */
  }
  </style>
    
<center>

<script src="<?php echo $OJ_CDN_URL?>include/checksource.js"></script>
<form id=frmSolution action="submit.php<?php if (isset($_GET['spa'])) echo "?spa" ?>" method="post" onsubmit='do_submit()' enctype="multipart/form-data" >
<?php if (!isset($_GET['spa']) || $solution_name ) {?>
        <input type='file' name='answer' aria-label='답안 파일 업로드' >
<?php } ?>

<?php if (isset($id)){?>
<span>문제 <b><?php echo $id?></b></span>
<input id=problem_id type='hidden' value='<?php echo $id?>' name="id" >
<?php }else{
//$PID="ABCDEFGHIJKLMNOPQRSTUVWXYZ";
//if ($pid>25) $pid=25;
?>
대회 <span class=blue><b><?php echo $cid?></b></span>의 문제 <span class=blue><b><?php echo chr($pid+ord('A'))?></b></span>
<input id="cid" type='hidden' value='<?php echo $cid?>' name="cid">
<input id="pid" type='hidden' value='<?php echo $pid?>' name="pid">
<?php }?>
<span id="language_span">언어:
<select id="language" name="language" onChange="reloadtemplate($(this).val());" >
<?php
$lang_count=count($language_ext);
if(isset($_GET['langmask']))
	$langmask=$_GET['langmask'];
else
	$langmask=$OJ_LANGMASK;
$langmask|=$OJ_LANGMASK;

$lang=(~((int)$langmask))&((1<<($lang_count))-1);
//$lastlang=$_COOKIE['lastlang'];
//if($lastlang=="undefined") $lastlang=1;
for($i=0;$i<$lang_count;$i++){
if($lang&(1<<$i))
echo"<option value=$i ".( $lastlang==$i?"selected":"").">
".$language_name[$i]."
</option>";
}
?>
</select>
<?php if($OJ_VCODE){?>
<?php echo $MSG_VCODE?>:
<input name="vcode" size=4 type=text autocomplete=off ><img id="vcode" alt="click to change" src="vcode.php" onclick="this.src='vcode.php?'+Math.random()">
<?php }?>
<button  id="Submit" type="button" class="ui primary icon button"  onclick="do_submit();"><?php echo $MSG_SUBMIT?></button> 
<label id="countDown" ></label>
<?php if (isset($OJ_ENCODE_SUBMIT)&&$OJ_ENCODE_SUBMIT){?>
<input id="Encoded" class="btn btn-success" title="WAF gives you reset ? try this." type=button value="Encoded <?php echo $MSG_SUBMIT?>" >
<input type=hidden id="encoded_submit_mark" name="reverse2" value="reverse"/>
<?php }?>
<?php if (isset($_SESSION[$OJ_NAME.'_administrator'])){?>
<input class="btn btn-danger" title="AI로 코드 생성" type=button value="AI 생성" onclick="ai_gen('Main.'+$('#language option:selected').text().trim());" id='ai_bt'>
<?php }?>
<!-- 선택형 문제 상태 -->
<?php if ($spj>1 || !$OJ_TEST_RUN ){?>
<span class="btn" id=result><?php echo $MSG_STATUS?></span>	
<?php }?>
</span>
<?php if($spj <= 1 &&  !$solution_name){ ?>
    <button onclick="toggleTheme(event)" style="background-color: bisque; position: absolute; top: 5px; right:70px;" v-if="false">
        <i>🌗</i>
    </button>
    <button onclick="increaseFontSize(event)" style="background-color: bisque; position: absolute; top: 5px; right:40px;" v-if="false">
        <i>➕</i>
    </button>
    <button onclick="decreaseFontSize(event)" style="background-color: bisque; position: absolute; top: 5px; right:10px;" v-if="false">
        <i>➖</i>
    </button>
<?php } ?>

<?php 
        if(!$solution_name){
                if($OJ_ACE_EDITOR){
                        if (isset($OJ_TEST_RUN)&&$OJ_TEST_RUN)
                                $height="400px";
                        else
                                $height="500px";
                ?>
                <pre style="width:99%;height:<?php echo $height?>" cols=180 rows=16 id="source"><?php echo htmlentities($view_src,ENT_QUOTES,"UTF-8")?></pre>
                <input type=hidden id="hide_source" name="source" value=""/>

        <?php }else{ ?>
                <textarea style="width:80%;height:600" cols=180 rows=30 id="source" name="source"><?php echo htmlentities($view_src,ENT_QUOTES,"UTF-8")?></textarea>
        <?php }

        }else{
                echo "<br><h2>지정된 제출 파일: $solution_name</h2>";

        }

	?>
<style>
            .button, input, optgroup, select, textarea {
    font-family: sans-serif;
    font-size: 150%;
    line-height: 1.15;
    margin: 0;
    background: border-box;
}
        #reinfo {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
            gap: 8px;
            margin-top: 10px;
        }

        #reinfo div {
            border-radius: 4px;
            padding: 1px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            transition: all 0.15s;
            cursor: default;
	    opacity: 0.95;
        }
        </style>
         <div class="row">
            <div class="column" style="display: flex;">
<?php if ( isset($OJ_TEST_RUN) && $OJ_TEST_RUN && $spj!=2 && !$solution_name  ){?>
<div style="
   
     margin-left: 60px;
    width: 40%;
     padding: 14px;
    flex-direction: column;">
        <div style="  
        display: flex;
    border-radius: 8px;
    background-color: rgb(255,255,255,0.4);" id="language_span"><?php echo $MSG_Input?></div>
         <textarea style="width:100%" cols=40 rows=5 id="input_text" name="input_text" ><?php echo $view_sample_input?></textarea>
    </div>
    <div style="
   
    width: 40%;
    flex-direction: column;
    ">
         <div style="    display: flex;
   
    border-radius: 8px;
    background-color: rgb(255,255,255,0.4);justify-content: space-between;" id="language_span"><?php echo $MSG_Output ?>
    
   <span class="btn" id=result><?php echo $MSG_STATUS?></span>	
    
    </div>

	<span id='reinfo' style="display:none"></span>
          <textarea style="
          width:100%;background-color: white;
          " cols=30 rows=5 id="out" name="out" disabled="true" placeholder='<?php echo htmlentities($view_sample_output,ENT_QUOTES,'UTF-8')?>' ></textarea>    
     </div>
<?php }else{  ?>
   <div style="margin-left: 5px; width: 95%; padding: 14px; flex-direction: column;">
	<span id='reinfo' style="display:none"></span>
   </div>
<?php }	 ?>
<?php if (isset($OJ_TEST_RUN)&&$OJ_TEST_RUN && $spj!=2 && !$solution_name  ){?>
        <!-- 실행 버튼 -->
            <input style="
             margin-top: 30px;
            margin-left: 15px ;
            width: 7%;background-color: #22ba46a3;border-color: #00fff470;height: 130px;
            " id="TestRun" class="btn btn-info" type=button value="<?php echo $MSG_TR?>" onclick=do_test_run();>
            
            <?php }?>
            
        </div>
 </div>
        </div>
         <input type="hidden" value="0" id="problem_id" name="problem_id"/>
    </form>
<?php if (isset($OJ_BLOCKLY)&&$OJ_BLOCKLY){?>
	<input id="blockly_loader" type=button class="btn" onclick="openBlockly()" value="<?php echo $MSG_BLOCKLY_OPEN?>" style="color:white;background-color:rgb(169,91,128)">
	<input id="transrun" type=button  class="btn" onclick="loadFromBlockly() " value="<?php echo $MSG_BLOCKLY_TEST?>" style="display:none;color:white;background-color:rgb(90,164,139)">
<div id="blockly" class="center">Blockly</div>
<?php }?> 
</form>
</center>
<script type="module" src="<?php echo $OJ_CDN_URL?>sqlite/sqlite3.js"></script>
<script>
var sid=0;
var i=0;
var using_blockly=false;
var judge_result=[<?php
foreach($judge_result as $result){
    echo "'$result',";
}
?>''];
function removeContentBeforeSeparator(text) {
    // 채점 서버가 붙인 결과 헤더 위치를 찾는다.
    const separator = "filename|size|result|memory|time";
    const separatorIndex = text.indexOf(separator);

    // 헤더가 있으면 그 앞의 불필요한 내용을 제거한다.
    if (separatorIndex !== -1) {
        // 헤더 시작 위치부터 결과 표로 취급한다.
        const separatorEndIndex = separatorIndex ;

        // 헤더 앞의 내용을 제거하고 결과 표만 반환한다.
        return text.substring(separatorEndIndex);
    }

    // 헤더가 없으면 원문을 그대로 반환한다.
    return text;
}
function parseToCompactCards(data) {
	//console.log(data);
	data=removeContentBeforeSeparator(data);
	const lines = data.trim().split('\n');
	const headers = lines[0].split('|');
	const dataLines = lines.slice(2);
	
	const resultContainer = $('#reinfo');
	resultContainer.empty();
	resultContainer.show();
	
	// 테스트 케이스별 실행 결과를 집계한다.
	let acCount = 0, waCount = 0, reCount = 0;
	
	dataLines.forEach(line => {
	    if (line.trim() === '') return;
	    
	    const values = line.split('|');
	    const item = {};
	    
	    headers.forEach((header, index) => {
		item[header] = values[index] || '';
	    });
	    
	    // 결과 상태에 맞는 카드 색상을 결정한다.
	    const resultType = item.result.split('/')[0];
	    let resultClass = 'warning'; // 기본값
	    
	    if (resultType === 'AC') {
		resultClass = 'success';
	    } else if (resultType === 'PE') {
		resultClass = 'info';
		reCount++;
	    } else if (resultType === 'WA') {
		resultClass = 'danger';
		reCount++;
	    } else {
		acCount++;
	    }
	    
	    // 한 테스트 케이스 결과를 작은 카드 HTML로 만든다.
	    const $card = $(`
		<div class="label label-${resultClass}" title="${item.filename}">
		    <span class="filename">${item.filename}</span>
		    <span class="size">${item.size} B</span><br>
		    <span class="result">${item.result}</span><br>
			<span class="stat-item">
			    <span class="stat-label">메모리:</span>
			    <span class="stat-value">${item.memory}</span>
			</span><br>
			<span class="stat-item">
			    <span class="stat-label">시간:</span>
			    <span class="stat-value">${item.time}</span>
			</span>
		</div>
	    `);
	    
	    resultContainer.append($card);
	});
	
	// 상단 요약 통계를 갱신한다.
	const totalCount = acCount + waCount + reCount;
	const passRate = totalCount > 0 ? ((acCount / totalCount) * 100).toFixed(1) : 0;
	
	$('#statsSummary').html(`
	    <div class="stats-item">전체: <span class="stats-value">${totalCount}</span></div>
	    <div class="stats-item">통과율: <span class="stats-value">${passRate}%</span></div>
	    <div class="stats-item">AC: <span class="stats-value ac-count">${acCount}</span></div>
	    <div class="stats-item">WA: <span class="stats-value wa-count">${waCount}</span></div>
	    <div class="stats-item">RE: <span class="stats-value re-count">${reCount}</span></div>
	`);
}
// AJAX 제출 후 채점 결과를 가져와 실행 결과 영역에 표시한다.
function print_result(solution_id) {
var sid = solution_id;

$.ajax({
url: "status-ajax.php",
type: "GET",
data: { tr: 1, solution_id: solution_id },
success: function(data) {
    $("#out").val(data);
     console.log(jresult);
    var resultVariable = data;
     if (jresult== 11) {
	$("#out").show();
	var errorLines = [];

	var regex = /(\w+\.<?php echo $language_ext[$lastlang]?>):(\d+):\d+:/g;
	var match;
	while ((match = regex.exec(resultVariable)) !== null) {
	    var lineNumber = parseInt(match[2], 10);
	    errorLines.push(lineNumber);
	}
	
	errorLines.forEach(function(line) {
	    var Range = ace.require("ace/range").Range;
	    var range = new Range(line - 1, 0, line - 1, Infinity);
	    editor.getSession().addMarker(range, "ace_error_marker", "fullLine", false);
	});
     }else if(jresult==13){
	$("#reinfo").hide();
	$("#out").show();
     }else{
	$("#out").hide();
	$("#reinfo").show();
	parseToCompactCards(data);	
     }
     
},
error: function(xhr, status, error) {
    console.error("Error fetching data: ", error);
}
});
}
// 결과 테이블의 채점 상태 셀을 클릭 가능한 상세 링크로 바꾼다.
function fancy(td){
$("body",parent.document).append("<div id='bannerFancy' style='position:absolute;top:0px;left:0px;width:100%;z-index:3' class='ui main container'></div><audio autoplay=\"autoplay\" preload=\"auto\" src=\"<?php echo $OJ_FANCY_MP3 ?>\"> </audio>");
window.setTimeout("$(\"#bannerFancy\",parent.document).html(\"<iframe border=0 src='fancy.php' width='100%' height='800px'></iframe>\");",500);
window.setTimeout("$(\"#bannerFancy\",parent.document).remove();",10000);
}
// 제출 직후 일정 시간마다 결과를 다시 조회해 화면을 갱신한다.
function fresh_result(solution_id)
{
var tb=window.document.getElementById('result');
if(solution_id==undefined){
	tb.innerHTML="Vcode Error!";		
	if($("#vcode")!=null) $("#vcode").click();
	return ;
}

sid=parseInt(solution_id);
if(sid<=0){
	tb.innerHTML="<?php echo  str_replace("10",$OJ_SUBMIT_COOLDOWN_TIME,$MSG_BREAK_TIME) ?>";
	if($("#vcode")!=null) $("#vcode").click();
	return ;
}

var xmlhttp;
if (window.XMLHttpRequest)
{// code for IE7+, Firefox, Chrome, Opera, Safari
xmlhttp=new XMLHttpRequest();
}
else
{// code for IE6, IE5
xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
}
xmlhttp.onreadystatechange=function()
	{
		if (xmlhttp.readyState==4 && xmlhttp.status==200)
		{
			var r=xmlhttp.responseText;
			if(r=="<?php echo $OJ_NOIP_KEYWORD?>") {
				alert("<?php echo $MSG_SUBMIT.$MSG_SUCCESS.$contest_locks[4]?>");
				tb.innerHTML="<?php echo $contest_locks[4]?>";
				return;
			}
			var ra=r.split(",");
			// alert(r);
			// alert(judge_result[r]);
			var loader="<img width=18 src=image/loader.gif>";
			var tag="span";
			window.jresult = ra[0];
			if(ra[0]<4) tag="span disabled=true";
			else tag="a";
			{
				if(ra[0]==11||ra[0]>15)
					tb.innerHTML="<"+tag+" href='ceinfo.php?sid="+solution_id+"' class='badge badge-info' target=_blank>"+judge_result[ra[0]]+"</"+tag+">";
				else
					tb.innerHTML="<"+tag+" href='reinfo.php?sid="+solution_id+"' class='badge badge-info' target=_blank>"+judge_result[ra[0]]+"AC:"+ra[4]+"</"+tag+">";
			}
			if(ra[0]<4||ra[0]>15)
				tb.innerHTML+=loader;
			tb.innerHTML+="Memory:"+ra[1]+"&nbsp;&nbsp;";
			tb.innerHTML+="Time:"+ra[2]+"";
			if(ra[0]<4||ra[0]>15)
				window.setTimeout("fresh_result("+solution_id+")",2000);
			else{
				window.setTimeout("print_result("+solution_id+")",2000);
				count=1;
			}
			<?php if ( $OJ_FANCY_RESULT ) {?>
				if(ra[0]==4) fancy(tb);
			<?php } ?>
		}
	}
	xmlhttp.open("GET","status-ajax.php?solution_id="+solution_id,true);
	xmlhttp.send();
}
// 현재 URL에서 solution_id 성격의 쿼리 값을 가져올 때 사용하는 보조 함수.
function getSID(){
var ofrm1 = document.getElementById("testRun").document;
var ret="0";
if (ofrm1==undefined)
{
ofrm1 = document.getElementById("testRun").contentWindow.document;
var ff = ofrm1;
ret=ff.innerHTML;
}
else
{
var ie = document.frames["frame1"].document;
ret=ie.innerText;
}
return ret+"";
}
var count=0;
 
// 에디터 내용을 base64로 인코딩한 뒤 AJAX 제출한다.
function encoded_submit(){

      var mark="<?php echo isset($id)?'problem_id':'cid';?>";
        var problem_id=document.getElementById(mark);

	if(typeof(editor) != "undefined")
		$("#hide_source").val(editor.getValue());
        if(mark=='problem_id')
                problem_id.value='<?php if(isset($id)) echo $id?>';
        else
                problem_id.value='<?php if(isset($cid))echo $cid?>';

        document.getElementById("frmSolution").target="_self";
        document.getElementById("encoded_submit_mark").name="encoded_submit";
        var source=$("#source").val();
	if(typeof(editor) != "undefined") {
		source=editor.getValue();
        	$("#hide_source").val(encode64(utf16to8(source)));
	}else{
        	$("#source").val(encode64(utf16to8(source)));
	}
//      source.value=source.value.split("").reverse().join("");
//      alert(source.value);
<?php if(isset($_GET['spa'])){?>
	<?php if($solution_name) { ?>document.getElementById("frmSolution").submit(); <?php } ?>  // 지정 파일명이 필요한 문제는 일반 form POST 방식으로 강제 제출한다.
        $.post("submit.php?ajax",$("#frmSolution").serialize(),function(data){fresh_result(data);});
        $("#Encoded").prop('disabled', true);
        $("#TestRun").prop('disabled', true);
        count=<?php echo $OJ_SUBMIT_COOLDOWN_TIME?> * 2 ;
        handler_interval= window.setTimeout("resume();",1000);
	 <?php if(isset($OJ_REMOTE_JUDGE)&&$OJ_REMOTE_JUDGE) {?>$("#sk").attr("src","remote.php"); <?php } ?>
<?php }else{?>
        document.getElementById("frmSolution").submit();
<?php }?>
}

// 일반 제출 버튼이 호출하는 제출 진입점.
function do_submit(){
	 $("#Submit").attr("disabled","true");   // mouse has a bad key1
	if(using_blockly) 
		 translate();
	if(typeof(editor) != "undefined"){ 
		$("#hide_source").val(editor.getValue());
	}
	var mark="<?php echo isset($id)?'problem_id':'cid';?>";
	var problem_id=document.getElementById(mark);
	if(mark=='problem_id')
	problem_id.value='<?php if (isset($id))echo $id?>';
	else
	problem_id.value='<?php if (isset($cid))echo $cid?>';
	document.getElementById("frmSolution").target="_self";
	
<?php if(isset($_GET['spa'])){?>
	<?php if($solution_name) { ?>document.getElementById("frmSolution").submit(); <?php } ?>  // 지정 파일명이 필요한 문제는 일반 form POST 방식으로 강제 제출한다.
        $.post("submit.php?ajax",$("#frmSolution").serialize(),function(data){fresh_result(data);});
        $("#Submit").prop('disabled', true);
        $("#TestRun").prop('disabled', true);
        count=<?php echo $OJ_SUBMIT_COOLDOWN_TIME?> * 2 ;
        handler_interval= window.setTimeout("resume();",1000);
	 <?php if(isset($OJ_REMOTE_JUDGE)&&$OJ_REMOTE_JUDGE) {?>$("#sk").attr("src","remote.php"); <?php } ?>
<?php }else{?>
        document.getElementById("frmSolution").submit();
<?php }?>

}
<?php

        $appendsource="";
		if(isset($id)){
                $append_file = "$OJ_DATA/$id/append.sql";
                if (isset($OJ_APPENDCODE) && $OJ_APPENDCODE && file_exists($append_file)) {
                  $appendsource .= "\n".file_get_contents($append_file);
                }
        }
        echo "var appendSQL=".json_encode($appendsource).";";

?>

// SQL 문제에서 sqlite3 워커를 초기화하고 기본 데이터와 사용자 SQL을 순서대로 실행한다.
function startSQL (sqlite3,input_sql,user_sql){
 // log('Running SQLite3 version', sqlite3.version.libVersion);
  const db = new sqlite3.oo1.DB('/mydb.sqlite3', 'ct');
 // log('Created transient database', db.filename);

  try {
   // log('Creating a table...');
    try{
            db.exec(input_sql);
    }finally{};
   // log('Query data with exec()...');
    db.exec({
      sql: user_sql+appendSQL,
      callback: (row) => {
        $("#out").val($("#out").val()+(row.join("|")+"\n"));
        console.log(row);
      },
    });
  } finally {
    db.close();
  }
};

// SQL 테스트 실행 요청을 준비해 브라우저 안의 sqlite 실행기로 넘긴다.
function runSQL(input,sql){
//      log('Loading and initializing SQLite3 module...');
        sqlite3InitModule().then((sqlite3) => {
//        log('Done initializing. Running demo...');
          try {
            $("#out").val("");
            startSQL(sqlite3,input,sql);
          } catch (err) {
            console.log(err.name, err.message);
          }
        });

}


var handler_interval;
// 제출 전 샘플 입력으로 테스트 실행을 요청한다.
function do_test_run(){
	if( handler_interval) window.clearInterval( handler_interval);
	var loader="<img width=18 src=image/loader.gif>";
	var tb=window.document.getElementById('result');
        var source=$("#source").val();
	if(typeof(editor) != "undefined") {
		source=editor.getValue();
        	$("#hide_source").val(source);
	}
        let lang=$("#language").val();
        if(lang==18){
                console.log("Using Wasm SQLite");
                runSQL($("#input_text").val(),source);
                return;
        }
	if(source.length<10) return alert("too short!");
	if(tb!=null)tb.innerHTML=loader;

	var mark="<?php echo isset($id)?'problem_id':'cid';?>";
	var problem_id=document.getElementById(mark);
	problem_id.value=-problem_id.value;
	document.getElementById("frmSolution").target="testRun";
	//$("#hide_source").val(editor.getValue());
	//document.getElementById("frmSolution").submit();
	$.post("submit.php?ajax",$("#frmSolution").serialize(),function(data){fresh_result(data);});
  	$("#Submit").prop('disabled', true);
  	$("#TestRun").prop('disabled', true);
	problem_id.value=-problem_id.value;
	count=<?php echo isset($OJ_SUBMIT_COOLDOWN_TIME)?$OJ_SUBMIT_COOLDOWN_TIME:5  ?> * 2 ;
	handler_interval= window.setTimeout("resume();",1000);
}
// 임시 저장된 코드를 에디터로 복구한다.
function resume(){
	count--;
	var s=$("#Submit")[0];
	var t=$("#TestRun")[0];
	if(count<0){
		 $("#Submit").attr("disabled",false);
		 $("#Encoded").attr("disabled",false);
		 $("#Submit").val("<?php echo $MSG_SUBMIT?>");
		if(t!=null) $("#TestRun").attr("disabled",false);
		if(t!=null) $("#TestRun").val("<?php echo $MSG_TR?>");
		if( handler_interval) window.clearInterval( handler_interval);
		if($("#vcode")!=null) $("#vcode").click();
	}else{
		 $("#Submit").val("<?php echo $MSG_SUBMIT?>("+count+")");
		if(t!=null)t.value="<?php echo $MSG_TR?>("+count+")";
		window.setTimeout("resume();",1000);
	}
}
// 언어 선택을 바꿨을 때 에디터 모드와 관련 설정을 갱신한다.
function switchLang(lang){
   var langnames=new Array("c_cpp","c_cpp","pascal","java","ruby","sh","python","php","perl","csharp","objectivec","vbscript","scheme","c_cpp","c_cpp","lua","javascript","golang","sql","fortran","matlab","cobol","r","c_cpp","python");
   editor.getSession().setMode("ace/mode/"+langnames[lang]);

}
// 선택 언어에 맞는 기본 코드 템플릿을 다시 불러온다.
function reloadtemplate(lang){
   if(lang==undefined){
        switchLang(1);
           return;
   }
   console.log("reload:<?php echo $lastlang?> -->"+lang);
   document.cookie="lastlang="+lang;
   var url=window.location.href;
   var i=url.indexOf("sid=");
   switchLang(lang);
   if(lang!=<?php echo $lastlang?>)
        document.location.href=url;
}

// Blockly 편집기를 별도 창으로 연다.
function openBlockly(){
   $("#source").hide();
   $("#TestRun").hide();
   $("#language")[0].scrollIntoView();
   $("#language").val(6).hide();
   //$("#language_span").hide();
   $("#EditAreaArroundInfos_source").hide();
   $('#blockly').html('<iframe name=\'frmBlockly\' width=90% height=580 src=\'blockly/demos/code/index.html\'></iframe>'); 
  $("#blockly_loader").hide();
  $("#transrun").show();
  //$("#Submit").prop('disabled', true);
  using_blockly=true;
  
}
// Blockly에서 생성한 코드를 현재 제출 에디터로 가져온다.
function translate(){
  var blockly=$(window.frames['frmBlockly'].document);
  var tb=blockly.find('td[id=tab_python]');
  var python=blockly.find('pre[id=content_python]');
  tb.click();
  blockly.find('td[id=tab_blocks]').click();
  if(typeof(editor) != "undefined") editor.setValue(python.text());
  else $("#source").val(python.text());
  $("#language").val(6);
 
}
// Blockly 창에서 저장한 코드를 읽어 에디터에 반영한다.
function loadFromBlockly(){
 translate();
 do_test_run();
  $("#frame_source").hide();
//  $("#Submit").prop('disabled', false);
}
</script>
<script language="Javascript" type="text/javascript" src="<?php echo $OJ_CDN_URL?>include/base64.js"></script>
<?php if (!empty($remote_oj)){
                echo "<iframe src=remote.php height=0px width=0px ></iframe>";
      }
?>
<?php if($OJ_ACE_EDITOR){ ?>
<script src="<?php echo $OJ_CDN_URL?>ace/ace.js"></script>
<script src="<?php echo $OJ_CDN_URL?>ace/ext-language_tools.js"></script>
<script>
    ace.require("ace/ext/language_tools");
    var editor = ace.edit("source");
    editor.setTheme("ace/theme/xcode");
    switchLang(<?php echo $lastlang ?>);
    editor.setOptions({
        enableBasicAutocompletion: true,
        enableSnippets: true,
        enableLiveAutocompletion: true,  // true면 자동완성을 켜고, false면 끈다.
        // fontFamily: "Consolas",  // MacOS missing align
	// theme: "ace/theme/ambiance",   // Black theme
        fontSize: "18px"
    });
    
editor.getSession().on("change", function() {
   
    var markers = editor.getSession().getMarkers(false);
    for (var id in markers) {
        if (markers[id].clazz === "ace_error_marker") {
            editor.getSession().removeMarker(id);
        }
    }
});


   reloadtemplate($("#language").val()); 
   function autoSave(){
        var mark="<?php echo isset($id)?'problem_id':'cid';?>";
        var problem_id=$("#"+mark).val();
	if(!!localStorage){
		 let key="<?php echo htmlentities($_SESSION[$OJ_NAME.'_user_id'])?>source:"+location.href;
		if(typeof(editor) != "undefined")
			$("#hide_source").val(editor.getValue());
		localStorage.setItem(key,$("#hide_source").val());
		//console.log("autosaving "+key+"..."+new Date());
	}
   }
   $(document).ready(function(){
   	$("#source").css("height",window.innerHeight-180);  
	if($("#vcode")!=undefined) $("#vcode").click();
	$("#Encoded").bind("click",encoded_submit);
	if(!!localStorage){
		let key="<?php echo htmlentities($_SESSION[$OJ_NAME.'_user_id'])?>source:"+location.href;
		let saved=localStorage.getItem(key);
		   if(saved!=null&&saved!=""&&saved.length>editor.getValue().length){
                        //let load=confirm("자동 저장된 소스가 있습니다. 불러오겠습니까? 이 기회는 한 번만 제공됩니다.");
                        //if(load){
                                console.log("loading "+saved.length);
                                if(typeof(editor) != "undefined")
                                        editor.setValue(saved);
                        //}
                }

	}
	if(typeof(editor) != "undefined") editor.resize();
	window.setInterval('autoSave();',5000);
	$("body").dblclick(function(){
                 if (event.ctrlKey==1) formatCode();
        }).attr("title","Ctrl+두 번 클릭: 들여쓰기 자동 정리");
   });
</script>
<script>
    function increaseFontSize(event) {
        event.preventDefault();
        var currentSize = parseInt(editor.getFontSize());
        editor.setFontSize(currentSize + 3);
    }

    function decreaseFontSize(event) {
        event.preventDefault();
        var currentSize = parseInt(editor.getFontSize());
        editor.setFontSize(currentSize - 3);
    }
   function toggleTheme(event) {
    event.preventDefault();
    
    if (editor.getTheme() === "ace/theme/xcode") {
        editor.setTheme("ace/theme/monokai");
    } else {
        editor.setTheme("ace/theme/xcode");
    }
}

	var level = 0;
var LOOP_SIZE = 100;
function finishTabifier(code) {
    code = code.replace(/\n\s*\n/g, '\n');  //blank lines
    code = code.replace(/^[\s\n]*/, ''); //leading space
    code = code.replace(/[\s\n]*$/, ''); //trailing space
    level = 0;
    var session = editor.getSession();
    session.setValue(code);
    return code;
}

function cleanCStyle(code) {
    var i = 0;
    code = code.replace(/\)\n[\s]*/g,')\n    '); //single line if while for
    function cleanAsync() {
        var iStart = i;
        for (; i < code.length && i < iStart + LOOP_SIZE; i++) {
            c = code.charAt(i);

            if (incomment) {
                if ('//' == incomment && '\n' == c) {
                    incomment = false;
                } else if ('/*' == incomment && '*/' == code.substr(i, 2)) {
                    incomment = false;
                    c = '*/\n';
                    i++;
                }
                if (!incomment) {
                    while (code.charAt(++i).match(/\s/)); ; i--;
                    c += tabs();
                }
                out += c;
            } else if (instring) {
                if (instring == c && // this string closes at the next matching quote
                // unless it was escaped, or the escape is escaped
          ('\\' != code.charAt(i - 1) || '\\' == code.charAt(i - 2))
        ) {
                    instring = false;
                }
                out += c;
            } else if (infor && '(' == c) {
                infor++;
                out += c;
            } else if (infor && ')' == c) {
                infor--;
                out += c;
            } else if ('else' == code.substr(i, 4)) {
                out = out.replace(/\s*$/, '') + ' e';
            } else if (code.substr(i).match(/^for\s*\(/)) {
                infor = 1;
                out += 'for (';
                while ('(' != code.charAt(++i)); ;
            } else if ('//' == code.substr(i, 2)) {
                incomment = '//';
                out += '//';
                i++;
            } else if ('/*' == code.substr(i, 2)) {
                incomment = '/*';
                out += '\n' + tabs() + '/*';
                i++;
            } else if ('"' == c || "'" == c) {
                if (instring && c == instring) {
                    instring = false;
                } else {
                    instring = c;
                }
                out += c;
            } else if ('{' == c) {
                level++;
                out = out.replace(/\s*$/, '') + ' {\n' + tabs();
                while (code.charAt(++i).match(/\s/)); ; i--;
            } else if ('}' == c) {
                out = out.replace(/\s*$/, '');
                level--;
                out += '\n' + tabs() + '}\n' + tabs();
                while (code.charAt(++i).match(/\s/)); ; i--;
            } else if (';' == c && !infor) {
                out += ';\n' + tabs();
                while (code.charAt(++i).match(/\s/)); ; i--;
            } else if ('\n' == c) {
                out += '\n' + tabs();
            } else {
                out += c;
            }
        }

        if (i < code.length) {
            setTimeout(cleanAsync, 0);
        } else {
            level = li;
            out = out.replace(/[\s\n]*$/, '');
            finishTabifier(out);
        }
    }

    code = code.replace(/^[\s\n]*/, ''); //leading space
    code = code.replace(/[\s\n]*$/, ''); //trailing space
    code = code.replace(/[\n\r]+/g, '\n'); //collapse newlines

    var out = tabs(), li = level, c = '';
    var infor = false, forcount = 0, instring = false, incomment = false;
    cleanAsync();
}
function tabs() {
    var s = '';
    for (var j = 0; j < level; j++) s += '\t';
    return s;
}
// Functions
function formatCode() {
  var session = editor.getSession();
  cleanCStyle(session.getValue());
}

// PHP 설정에서 들여쓰기 검사가 켜져 있을 때만 change 이벤트를 연결한다.
<?php if (isset($OJ_INDENT) && $OJ_INDENT == true): ?>
editor.getSession().on('change', function() {
    // 잦은 입력으로 브라우저가 버벅이지 않도록 짧은 디바운스를 둔다.
    if (this.indentTimer) clearTimeout(this.indentTimer);
    this.indentTimer = setTimeout(checkAndMarkIndentationInstant, 300);
});
<?php endif; ?>

// 아래 함수는 실제 들여쓰기 검사 로직이다. 이벤트가 연결되지 않으면 실행되지 않아 성능 부담이 없다.
function checkAndMarkIndentationInstant() {
    let session = editor.getSession();
    
    // 1. C/C++/Java처럼 중괄호를 쓰는 언어만 검사한다.
    let mode = session.getMode().$id || "";
    if (mode.indexOf("c_cpp") === -1 && mode.indexOf("java") === -1) {
        return; 
    }

    let lines = session.getDocument().getAllLines();
    let expectedIndentLevel = 0;      // 기본 기대 들여쓰기 단계
    let expectExtraIndent = false;    // 중괄호 없는 if/for 다음 줄 보정
    let formatWarnings = [];

    for (let i = 0; i < lines.length; i++) {
        let line = lines[i];
        let trimmed = line.trim();

        // Ace Token 정보를 활용해 주석 줄을 검사 대상에서 제외한다.
        let tokens = session.getTokens(i);
        // 현재 줄이 주석 위주인지, 또는 여러 줄 주석 안에 있는지 판단한다.
        let isCommentLine = tokens.length > 0 && tokens.every(t => t.type.indexOf("comment") !== -1);
        let hasBlockComment = tokens.some(t => t.type.indexOf("comment.block") !== -1);

        // 순수 주석 줄이나 블록 주석 줄은 들여쓰기 검사와 중괄호 계산에서 제외한다.
        if (isCommentLine || hasBlockComment || trimmed.length === 0 || trimmed.startsWith("#")) {
            // 한 줄 주석이면 다음 줄을 위해 현재 expectedIndentLevel은 유지하되 중괄호 카운트는 갱신하지 않는다.
            continue; 
        }

        // 2. 현재 줄에 기대되는 들여쓰기 단계를 계산한다.
        let currentLineExpected = expectedIndentLevel;
        if (expectExtraIndent) {
            currentLineExpected += 1;
        }

        // 현재 줄이 }로 시작하면 한 단계 줄여 맞춘다.
        if (trimmed.startsWith("}")) {
            currentLineExpected = Math.max(0, expectedIndentLevel - 1);
        }

        // 3. 실제 공백 수를 계산한다. 탭 1개는 공백 4개로 본다.
        let leadingWhitespace = line.match(/^[\s\t]*/)[0];
        let actualSpaces = 0;
        for (let j = 0; j < leadingWhitespace.length; j++) {
            actualSpaces += (leadingWhitespace[j] === '\t') ? 4 : 1;
        }

        // 4. 기대값과 실제값을 비교해 경고를 기록한다.
        let expectedSpaces = currentLineExpected * 4;
        if (actualSpaces !== expectedSpaces) {
            formatWarnings.push({
                row: i,
                column: 0,
                text: `들여쓰기 오류: 공백 ${expectedSpaces}개가 필요하지만 현재 ${actualSpaces}개입니다.`,
                type: "warning" 
            });
        }

        // 5. 다음 줄의 기본 들여쓰기 단계를 갱신한다.
        // 문자열 안의 괄호는 제외하고 실제 코드의 괄호만 센다.
        let openBraces = 0;
        let closeBraces = 0;
        tokens.forEach(t => {
            if (t.type.indexOf("comment") === -1 && t.type.indexOf("string") === -1) {
                openBraces += (t.value.match(/\{/g) || []).length;
                closeBraces += (t.value.match(/\}/g) || []).length;
            }
        });

        expectedIndentLevel += (openBraces - closeBraces);
        expectedIndentLevel = Math.max(0, expectedIndentLevel); 

        // 6. 중괄호 없는 if/for/while/else 다음 줄을 보정한다.
        if (openBraces === 0 && closeBraces === 0) {
            if ((trimmed.startsWith("if") || trimmed.startsWith("for") || trimmed.startsWith("while")) && trimmed.endsWith(")")) {
                expectExtraIndent = true;
            } else if (trimmed === "else") {
                expectExtraIndent = true;
            } else {
                expectExtraIndent = false; 
            }
        } else {
            expectExtraIndent = false; 
        }
    }

    // 7. 시스템 오류 표시는 유지하면서 들여쓰기 경고만 렌더링한다.
    let currentAnnotations = session.getAnnotations();
    let existingErrors = currentAnnotations.filter(a => a.type === "error");
    session.setAnnotations(existingErrors.concat(formatWarnings));
}

</script>
<?php }?>
<script>
function auto_submit(){
        if(countDown>0){
                countDown--;
                $("#countDown").text("<?php echo $MSG_LeftTime?>"+countDown+"<?php echo $MSG_SECONDS?>");
        }

}
<?php if(isset($cid)&&$cid>0){
        $sql="select unix_timestamp(end_time)-unix_timestamp(now()) from contest where contest_id=?";
        $countStart=pdo_query($sql,$cid);
        if(!empty($countStart)){
                $countStart=intval($countStart[0][0])??0;
                if($countStart<0) $countStart=0;
                echo "var countDown=".$countStart.";";
                echo "if(countDown>0) setInterval('auto_submit()',1000);";
        }
      }
?>

function removeCodeBlockMarkers(str) {
    // 문자열이 비어 있으면 그대로 반환한다.
    if (!str || typeof str !== 'string') {
        return str || '';
    }
    // 문자열을 줄 단위로 나눈다.
    const lines = str.split('\n');
    const resultLines = [];
    // 각 줄을 순회한다.
    for (let i = 0; i < lines.length; i++) {
        const line = lines[i];
        const trimmedLine = line.trim();
        // 첫 줄이 ```Python 같은 코드 블록 시작이면 결과에 넣지 않는다.
        if (i === 0 && (trimmedLine === '```Python' || trimmedLine.startsWith('```'))) {
            continue; // 첫 줄 마커 건너뛰기
        }
        // 마지막 줄이 ``` 코드 블록 종료이면 결과에 넣지 않는다.
        if (i === lines.length - 1 && trimmedLine === '```') {
            continue; // 끝 마커 건너뛰기
        }
        // 나머지 줄은 결과에 추가한다.
        resultLines.push(line);
    }
    // 줄 배열을 다시 문자열로 합친다.
    const result = resultLines.join('\n');
    // 마커 제거 후 내용이 없으면 빈 문자열을 반환한다.
    return result;
}
	function ai_gen(filename){
		    let oldval=$('#ai_bt').val();
		    $('#ai_bt').val('AI 생성 중... 잠시 기다려 주세요.');
		    $('#ai_bt').prop('disabled', true);
		    $.ajax({
		    	url: '<?php echo $OJ_AI_API_URL?>', 
				type: 'GET',
				data: { pid: '<?php echo $id?>', filename: filename },
				success: function(data) {
					if(parseInt(data)>0)
						window.setTimeout('pull_result('+data+')',1000);
					else{
						fill_data(data);		
					}
				},
				error: function() {
				    $('#ai_bt').val('데이터를 가져오지 못했습니다.');
					$('#ai_bt').prop('disabled', false);
				}
		    });
	}
// AI 생성 결과나 외부 도구 결과를 문제 입력/코드 입력 영역에 채운다.
function fill_data(data){
        data=data.replace(/<think>[\s\S]*?<\/think>/g, '');
        if(typeof(editor) != "undefined")
                editor.setValue(removeCodeBlockMarkers(data.trim())); // 코드 블록 마커를 제거한 뒤 에디터에 반영한다.
        else
                $("#source").val(data.trim());
        $('#ai_bt').prop('disabled', false);
        $('#ai_bt').val("다시 생성");
}
function pull_result(id){
	console.log(id);
    $.ajax({
	url: '../aiapi/ajax.php', 
	type: 'GET',
	data: { id: id },
	success: function(data) {
		if(data=='waiting'){
			window.setTimeout('pull_result('+id+')',1000);
		}else{
			fill_data(data);
		    $('#ai_bt').val('다시 생성');
		    $('#ai_bt').prop('disabled', false);
		}
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
<?php //include("template/$OJ_TEMPLATE/footer.php");?>
