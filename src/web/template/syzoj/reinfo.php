<?php $show_title=$id." - $MSG_ERROR_INFO - $OJ_NAME"; ?>
<?php include("template/$OJ_TEMPLATE/header.php");?>

<script src="<?php echo $OJ_CDN_URL."template/bs3/"?>marked.min.js"></script>
<script src="<?php echo $OJ_CDN_URL?>template/<?php echo $OJ_TEMPLATE?>/js/textFit.min.js"></script>
<style>
.single-subtask {
    box-shadow: none !important;
}

.single-subtask > .title {
    display: none;
}

.single-subtask > .content {
    padding: 0 !important;
}

.accordion > .content > .accordion {
    margin-top: 0;
    margin-bottom: 0;
}

.accordion > .content > .accordion > .content {
    margin-top: 0;
    margin-bottom: 14px;
}

.accordion > .content > .accordion > .content > :last-child {
    margin-bottom: -10px !important;
}
td > code {
    color:red;
    white-space:nowrap;
}

/* 채점기가 만든 출력 비교(마크다운 표)를 테스트별 카드로 보여 준다. */
.ui.segment.gs-diff-wrap { padding: 0; border: 0; box-shadow: none; background: transparent; }
#errtxt.gs-diff { margin: 0; padding: 0; background: transparent; border: 0; white-space: normal; font-family: inherit; font-size: 14px; }
#errtxt.gs-diff .gs-case { margin: 0 0 16px; padding: 16px 18px; border: 1px solid #e5e7eb; border-radius: 12px; background: #fff; }
#errtxt.gs-diff .gs-case:last-child { margin-bottom: 0; }
#errtxt.gs-diff h2 { margin: 0 0 12px; font-size: 15px; font-weight: 600; color: #111827; }
#errtxt.gs-diff h2 a { color: #2563eb; }
#errtxt.gs-diff .gs-table-scroll { overflow-x: auto; }
#errtxt.gs-diff table { width: 100%; max-width: 760px; margin: 0; border-collapse: separate; border-spacing: 0; table-layout: fixed; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }
#errtxt.gs-diff th { padding: 8px 12px; background: #f9fafb; color: #4b5563; font-size: 13px; font-weight: 600; text-align: left; border-bottom: 1px solid #e5e7eb; }
#errtxt.gs-diff td { padding: 7px 12px; color: #111827; font-family: Consolas, "D2Coding", "Courier New", monospace; font-size: 13px; line-height: 1.5; white-space: pre-wrap; word-break: break-all; vertical-align: top; border-top: 1px solid #f1f3f5; }
#errtxt.gs-diff th + th, #errtxt.gs-diff td + td { border-left: 1px solid #e5e7eb; }
#errtxt.gs-diff tbody tr:first-child td { border-top: 0; }
#errtxt.gs-diff td > code { padding: 1px 6px; border-radius: 4px; background: #fee2e2; color: #b91c1c; font-family: inherit; font-size: 13px; white-space: pre-wrap; }
#errtxt.gs-diff .gs-muted { color: #9ca3af; }
#errtxt.gs-diff .gs-eof { display: inline-block; padding: 0 8px; border-radius: 999px; background: #f3f4f6; color: #6b7280; font-family: inherit; font-size: 12px; line-height: 20px; }
#errtxt.gs-diff pre { margin: 0; padding: 12px; border-radius: 8px; background: #f9fafb; white-space: pre-wrap; }
#errtxt.gs-diff p { margin: 0 0 12px; color: #4b5563; font-family: inherit; font-size: 14px; line-height: 1.6; white-space: normal; }
#errtxt.gs-diff .gs-result { display: inline-block; padding: 0 10px; border-radius: 999px; font-family: inherit; font-size: 12px; font-weight: 600; line-height: 22px; }
#errtxt.gs-diff .gs-result.is-ac { background: #dcfce7; color: #15803d; }
#errtxt.gs-diff .gs-result.is-wa { background: #fee2e2; color: #b91c1c; }
#errtxt.gs-diff .gs-result.is-tle { background: #fef3c7; color: #b45309; }
#errtxt.gs-diff .gs-result.is-re { background: #ede9fe; color: #6d28d9; }
@media (max-width: 767px) {
    #errtxt.gs-diff .gs-case { padding: 12px; }
    #errtxt.gs-diff table { min-width: 420px; }
}

</style>
<div class="padding">
    <?php echo gs_resubmit_button(isset($gs_resubmit_url) ? $gs_resubmit_url : ''); ?>
    <div style="margin-top: 0px; margin-bottom: 14px; " v-if="content != null && content !== ''">
    <p class="transition visible">
           <strong ><?php echo "$MSG_ERROR_INFO";?> </strong>
        </p>
        <div class="ui existing segment">
          <pre v-if="escape" style="margin-top: 0; margin-bottom: 0; " id="errtxt"><?php echo $view_reinfo?></pre>
        </div>
    </div>

    <div style="margin-top: 0px; margin-bottom: 14px; " v-if="content != null && content !== ''">
    <p class="transition visible">
           <strong ><?php echo "$MSG_INFO_EXPLAINATION";?></strong>
        </p>
        <div class="ui existing segment">
          <pre v-if="escape" style="margin-top: 0; margin-bottom: 0; "><code><div id='errexp'></div></code></pre>
        </div>
        </div>
    </div>
<script>
    var pats=new Array();
    var exps=new Array();
    pats[0]=/A Not allowed system call/;
    exps[0]="<?php echo $MSG_A_NOT_ALLOWED_SYSTEM_CALL ?>";
    pats[1]=/Segmentation fault/;
    exps[1]="<?php echo $MSG_SEGMETATION_FAULT ?>";
    pats[2]=/Floating point exception/;
    exps[2]="<?php echo $MSG_FLOATING_POINT_EXCEPTION ?>";
    pats[3]=/buffer overflow detected/;
    exps[3]="<?php echo $MSG_BUFFER_OVERFLOW_DETECTED ?>";
    pats[4]=/Killed/;
    exps[4]="<?php echo $MSG_PROCESS_KILLED ?>";
    pats[5]=/Alarm clock/;
    exps[5]="<?php echo $MSG_ALARM_CLOCK ?>";
    pats[6]=/CALLID:20/;
    exps[6]="<?php echo $MSG_CALLID_20 ?>";
    pats[7]=/ArrayIndexOutOfBoundsException/;
    exps[7]="<?php echo $MSG_ARRAY_INDEX_OUT_OF_BOUNDS_EXCEPTION ?>";
    pats[8]=/StringIndexOutOfBoundsException/;
    exps[8]="<?php echo $MSG_STRING_INDEX_OUT_OF_BOUNDS_EXCEPTION ?>";
    pats[9]=/Binary files/;
    exps[9]="<?php echo $MSG_WRONG_OUTPUT_TYPE_EXCEPTION ?>";
    pats[10]=/non-zero return/;
    exps[10]="<?php echo $MSG_NON_ZERO_RETURN ?>";

  MathJax = {
    startup : { typeset: false  } ,
    tex: {inlineMath: [['$', '$'], ['\\(', '\\)']]}
  };
function fill_data(data){
    $("#errexp").html(data);    
    $("#errexp").html(marked.parse($("#errexp").text()));    
    console.log("Mathjax");
    MathJax.typeset(); 
    const target = document.getElementById('errexp');
	MathJax.typesetPromise([target]).then(() => {
	  console.log('局部渲染完成！');
	}).catch((err) => console.log('渲染出错:', err));
}
function pull_result(id){
	console.log(id);
    $.ajax({
	url: '../aiapi/ajax.php', 
	type: 'GET',
	data: { id: id },
	success: function(data) {
		if(data=='waiting'){
			window.setTimeout('pull_result('+id+')',2000);
		}else{
			fill_data(data);
		    $('#ai_bt').val('다시 시도');
		    $('#ai_bt').prop('disabled', false);
		}
	},
	error: function() {
	    $('#ai_bt').val('데이터를 가져오지 못했습니다');
	    $('#ai_bt').prop('disabled', false);
	}
    });
}
   function explain(){
      var errmsg = $("#errtxt").text();
      var expmsg = "";
      for(var i=0; i<pats.length; i++){
        var pat = pats[i];
        var exp = exps[i];
        var ret = pat.exec(errmsg);
        if(ret){
          expmsg += ret+" : "+exp+"<br><hr />";
        }
      }
       <?php if (!$isAC && isset($OJ_AI_API_URL)&&!empty($OJ_AI_API_URL) && $coin >0){ ?>
                expmsg+="<input type=button class='ui button primary' onclick='ai_explain()' value='💰AI Help'>";
        <?php } ?>
      document.getElementById("errexp").innerHTML=expmsg;
    }
    explain();
    function ai_explain(){

       <?php
                if (!$isAC && isset($OJ_AI_API_URL)&&!empty($OJ_AI_API_URL)){ ?>
                let expmsg=document.getElementById("errexp").innerHTML;
                expmsg+="AI 설명을 준비하고 있습니다...<img src='image/loader.gif'>";
                $("#errexp").html(expmsg);
                    $.ajax({
                        url: '<?php echo $OJ_AI_API_URL ?>?sid=<?php echo $id?>',
                        type: 'GET',
                        success: function(data) {
                                if(parseInt(data)>0)
                                        window.setTimeout('pull_result('+data+')',2000);
                                else{
                                        fill_data(data);
                                }
                        },
                        error: function() {
			    console.log('데이터를 가져오지 못했습니다');
                        }
                    });
       <?php } ?>
    }


</script>
<link rel="stylesheet" href="<?php echo $OJ_CDN_URL.$path_fix."template/$OJ_TEMPLATE/css/"?>highlight.css">
<script src="<?php echo $OJ_CDN_URL.$path_fix."template/$OJ_TEMPLATE/js/"?>highlight.min.js"></script>
<script src="<?php echo $OJ_CDN_URL.$path_fix."template/$OJ_TEMPLATE/js/"?>marked.umd.js"></script>
<script src="<?php echo $OJ_CDN_URL.$path_fix."template/$OJ_TEMPLATE/js/"?>marked-highlight.umd.js"></script>
<script> 
 const { Marked } = globalThis.marked;
 const { markedHighlight } = globalThis.markedHighlight;
const marked = new Marked(
  markedHighlight({
	emptyLangClass: 'hljs',
    langPrefix: 'hljs language-',
    highlight(code, lang, info) {
      const language = hljs.getLanguage(lang) ? lang : 'plaintext';
      return hljs.highlight(code, { language }).value;
    }
  })
);
</script>
<script id="MathJax-script" async src="template/bs3/tex-chtml.js"></script>
<script>
    $(document).ready(function(){
                marked.use({
                  // 开启异步渲染
                  async: false,
                  pedantic: false,
                  gfm: true,
                  mangle: false,
                  headerIds: false
                });
                $("#errtxt").each(function(){
                        $(this).html(marked.parse($(this).html()));
                });
                // adding note for ```input1  ```output1 in description
                for(let i=1;i<10;i++){
                        $(".language-input"+i).parent().before("<div><?php echo $MSG_Input?>"+i+":</div>");
                        $(".language-output"+i).parent().before("<div><?php echo $MSG_Output?>"+i+":</div>");
                }


        // 출력 비교 결과(파일명 제목 + 표)가 있으면 테스트별 카드 모양으로 바꾼다. 일반 오류 문구는 그대로 둔다.
        var $err=$("#errtxt");
        if($err.find("table, h2").length){
            $err.addClass("gs-diff").closest(".ui.segment").addClass("gs-diff-wrap");
            $err.find("table").wrap("<div class='gs-table-scroll'></div>");
            $err.find("h2").each(function(){
                $(this).nextUntil("h2").addBack().wrapAll("<div class='gs-case'></div>");
            });
            // 줄 끝(↩)은 흐리게, 출력 끝(⛔)은 글자 배지로 보여 준다. 텍스트 노드만 바꿔 원래 내용은 이스케이프된 채로 둔다.
            $err.find("td, td > code").contents().filter(function(){ return this.nodeType===3; }).each(function(){
                var text=this.nodeValue;
                if(text.indexOf("↩")<0 && text.indexOf("⛔")<0 && text.trim()!=="...") return;
                var frag=document.createDocumentFragment();
                if(text.trim()==="..."){
                    var dots=document.createElement("span"); dots.className="gs-muted"; dots.textContent=text; frag.appendChild(dots);
                }else{
                    text.split(/(↩|⛔)/).forEach(function(part){
                        if(part==="") return;
                        var node;
                        if(part==="↩"){ node=document.createElement("span"); node.className="gs-muted"; node.textContent="↩"; }
                        else if(part==="⛔"){ node=document.createElement("span"); node.className="gs-eof"; node.textContent=<?php echo json_encode(isset($MSG_OUTPUT_END) ? $MSG_OUTPUT_END : "출력 끝")?>; }
                        else node=document.createTextNode(part);
                        frag.appendChild(node);
                    });
                }
                this.parentNode.replaceChild(frag,this);
            });
            // 테스트별 결과 요약 표의 채점기 약어(WA, TLE, RE 등)를 한국어 배지로 바꿔 결과 종류를 구분해 보여 준다.
            var gsResult=<?php echo json_encode(array(
                "AC" => array($MSG_Accepted, "is-ac"), "PE" => array($MSG_Presentation_Error, "is-wa"), "WA" => array($MSG_Wrong_Answer, "is-wa"),
                "TLE" => array($MSG_Time_Limit_Exceed, "is-tle"), "MLE" => array($MSG_Memory_Limit_Exceed, "is-tle"),
                "OLE" => array($MSG_Output_Limit_Exceed, "is-tle"), "RE" => array($MSG_Runtime_Error, "is-re")
            ), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
            $err.find("td").each(function(){
                var m=/^(AC|PE|WA|TLE|MLE|OLE|RE)(\/[\d.]+)?$/.exec(this.textContent.trim());
                if(!m || !gsResult[m[1]]) return;
                var badge=document.createElement("span");
                badge.className="gs-result "+gsResult[m[1]][1];
                badge.textContent=gsResult[m[1]][0]+(m[2] ? " "+m[2].substring(1) : "");
                this.textContent="";
                this.appendChild(badge);
            });
        }
        <?php
          if(isset($OJ_DOWNLOAD)&&$OJ_DOWNLOAD && $coin>0 ){
            if(isset($OJ_DL_1ST_WA_ONLY) && $OJ_DL_1ST_WA_ONLY){
        ?>
           let down=$($("#errtxt").find("h2")[0]);
           let filename=down.text();
           down.html("<a href='download.php?sid=<?php echo $id?>&name=" + filename+ "'>💰" + filename+ "</a>");
        <?php
            }else{
        ?>
           $("#errtxt").find("h2").each(function(){
                   let down=$(this);
                   let filename=down.text();
                   console.log(filename);
                   down.html("<a href='download.php?sid=<?php echo $id?>&name=" + filename+ "'>" + filename+ "</a>");
           });

        <?php
            }
          }
        ?>
        $("th").each(function(){
                let html=$(this).html();
                html=html.replace("Expected","<?php echo $MSG_EXPECTED ?>");
                html=html.replace("Yours","<?php echo $MSG_YOURS ?>");
                html=html.replace("filename","<?php echo $MSG_FILENAME ?>");
                html=html.replace("size","<?php echo $MSG_SIZE ?>");
                html=html.replace("result","<?php echo $MSG_RESULT ?>");
                html=html.replace("memory","<?php echo $MSG_MEMORY?>");
                html=html.replace("time","<?php echo $MSG_TIME ?>");
                $(this).html(html);

        });

    });
</script>

<?php include("template/$OJ_TEMPLATE/footer.php");?>
