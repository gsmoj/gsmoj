<?php
//ini_set("display_errors", "On");  //set this to "On" for debugging  ,especially when no reason blank shows up.
require("admin-header.php");
require_once("../include/set_get_key.php");
require_once('../include/const.inc.php');

if(!(isset($_SESSION[$OJ_NAME.'_'.'administrator'])
        || isset($_SESSION[$OJ_NAME.'_'.'problem_editor'])
        || isset($_SESSION[$OJ_NAME.'_'.'contest_creator'])
        )){
  exit(1);
}


if(isset($OJ_LANG)){
  require_once("../lang/$OJ_LANG.php");
}
?>

<title>문제 목록</title>
<script>document.body.classList.add('admin-form-page');</script>
<div class='admin-form-shell pm-page'>
<header class="admin-form-title">
  <div><h3>문제 목록</h3><p><?php echo $MSG_HELP_PROBLEM_LIST?></p></div>
  <?php if (isset($_SESSION[$OJ_NAME.'_'.'administrator']) || isset($_SESSION[$OJ_NAME.'_'.'contest_creator']) || isset($_SESSION[$OJ_NAME.'_'.'problem_editor'])) { ?>
  <div class="pm-title-actions"><a href="problem_add_page.php" class="btn btn-primary"><?php echo $MSG_PROBLEM." ".$MSG_ADD?></a></div>
  <?php } ?>
</header>

<?php
// 수행평가 전용 문제(defunct='A')는 각 수행평가 화면에서 관리하므로 목록에서 제외한다.
$sql = "SELECT COUNT('problem_id') AS ids FROM `problem` WHERE defunct<>'A'";
$result = pdo_query($sql);
$row = $result[0];

$ids = intval($row['ids']);

$idsperpage = 50;
$pages = intval(ceil($ids/$idsperpage));

if(isset($_GET['page'])){ $page = intval($_GET['page']);}
else{ $page = 1;}

$pagesperframe = 25;
$frame = intval(ceil($page/$pagesperframe));

$spage = ($frame-1)*$pagesperframe+1;
$epage = min($spage+$pagesperframe-1, $pages);

$sid = ($page-1)*$idsperpage;

$sql = "";
if(isset($_GET['keyword']) && $_GET['keyword']!=""){
  $keyword = $_GET['keyword'];
  $keyword = "%$keyword%";
  $sql = "SELECT `problem_id`,`title`,`accepted`,`in_date`,`defunct`,`source`,`remote_oj`,`remote_id`,`coin`  FROM `problem` WHERE defunct<>'A' AND ((problem_id LIKE ?) OR (title LIKE ?) OR (description LIKE ?) OR (source LIKE ?) OR (hint LIKE ?)) ORDER BY `problem_id` ASC";
  $result = pdo_query($sql,$keyword,$keyword,$keyword,$keyword,$keyword);
}else{
  $sql = "SELECT `problem_id`,`title`,`accepted`,`in_date`,`defunct`,`source`,`remote_oj`,`remote_id`,`coin`  FROM `problem` WHERE defunct<>'A' ORDER BY `problem_id` DESC LIMIT $sid, $idsperpage";
  $result = pdo_query($sql);
}
?>

<?php
$is_search = isset($_GET['keyword']) && $_GET['keyword']!="";
// 쪽 이동 링크. 검색 결과는 한 번에 모두 보여 주므로 검색 중에는 표시하지 않는다.
function pm_pagination($page, $spage, $epage, $pages, $extra_class = "") {
  echo "<nav class='pm-pager $extra_class' aria-label='문제 목록 쪽 이동'>";
  echo "<ul class='pagination pagination-sm'>";
  echo "<li class='page-item'><a title='첫 쪽' href='problem_list.php?page=1'>&laquo;</a></li>";
  echo "<li class='page-item'><a title='이전 쪽' href='problem_list.php?page=".($page==1?1:$page-1)."'>&lsaquo;</a></li>";
  for($i=$spage; $i<=$epage; $i++){
    echo "<li class='".($page==$i?"active ":"")."page-item'><a title='".$i."쪽으로 이동' href='problem_list.php?page=".$i."'>".$i."</a></li>";
  }
  echo "<li class='page-item'><a title='다음 쪽' href='problem_list.php?page=".($page==$pages?$page:$page+1)."'>&rsaquo;</a></li>";
  echo "<li class='page-item'><a title='마지막 쪽' href='problem_list.php?page=".$pages."'>&raquo;</a></li>";
  echo "</ul></nav>";
}
?>
<div class="pm-toolbar">
  <form action=problem_list.php class="pm-search" role="search">
    <input type="text" name=keyword value="<?php if(isset($_GET['keyword']))echo htmlentities($_GET['keyword'],ENT_QUOTES,"utf-8")?>" class="form-control search-query" placeholder="<?php echo $MSG_PROBLEM_ID.', '.$MSG_TITLE.', '.$MSG_Description.', '.$MSG_SOURCE?>" aria-label="문제 검색">
    <button type="submit" class="btn btn-primary"><?php echo $MSG_SEARCH?></button>
    <?php if ($is_search) { ?><a class="pm-act" href="problem_list.php">검색 해제</a><?php } ?>
  </form>
  <span class="pm-count"><?php echo $is_search ? "검색 결과 <b>".count($result)."</b>개" : "전체 <b>".$ids."</b>문제 · ".$page." / ".max($pages,1)."쪽"?></span>
  <?php if (!$is_search) pm_pagination($page, $spage, $epage, $pages); ?>
</div>

<?php
$pm_can_manage = isset($_SESSION[$OJ_NAME.'_'.'administrator']) || isset($_SESSION[$OJ_NAME.'_'.'problem_editor']);
// 선택한 문제 일괄 작업 버튼. 표 위·아래에 같은 버튼을 둔다(기존 동작과 같음).
function pm_bulkbar($with_ai, $extra_class = "") {
  global $OJ_NAME, $MSG_CHECK_TO, $MSG_NEW_CONTEST, $MSG_AVAILABLE, $MSG_RESERVED, $MSG_NEW_PROBLEM_LIST, $pm_can_manage;
  ?>
  <div class="pm-bulkbar <?php echo $extra_class?>">
    <span class="pm-bulkbar-label"><?php echo $MSG_CHECK_TO?></span>
    <div class="pm-bulkbar-actions">
      <input class="btn btn-default btn-sm" type=submit name='problem2contest' value='<?php echo $MSG_NEW_CONTEST?>'>
      <?php if ($pm_can_manage) { // problem_df_change.php는 관리자와 problem_editor만 허용하므로 공개/비공개 일괄 버튼도 그 권한에만 보인다. ?>
      <input class="btn btn-default btn-sm" type=submit name='enable' value='<?php echo $MSG_AVAILABLE ?>' onclick='$("form").attr("action","problem_df_change.php")'>
      <input class="btn btn-default btn-sm" type=submit name='disable' value='<?php echo $MSG_RESERVED ?>' onclick='$("form").attr("action","problem_df_change.php")'>
      <?php } ?>
      <input class="btn btn-default btn-sm" type=submit name='plist' value='<?php echo $MSG_NEW_PROBLEM_LIST?>' onclick='$("form").attr("action","news_add_page.php")'>
      <?php if ($with_ai && isset($_SESSION[$OJ_NAME."_administrator"])) { ?><span class='btn btn-primary btn-sm' onclick='$(".ai.label.label-primary").click();'>AI 분류</span><?php } ?>
    </div>
  </div>
  <?php
}
?>
<form id='pform' class="pm-list-form" method=post action=contest_add.php>
<input type="hidden" name=keyword value="<?php if(isset($_GET['keyword']))echo htmlentities($_GET['keyword'],ENT_QUOTES,"utf-8")?>">
<input type="hidden" name=hlist value="" >
<?php require_once("../include/set_post_key.php");?>
<?php pm_bulkbar(true); ?>
<div class="pm-table-wrap">
<table class="table">
  <thead>
    <tr>
      <th style="width:96px"><label class="pm-id" title="이 쪽의 문제 모두 선택"><input type=checkbox onchange='$("input[type=checkbox]").prop("checked", this.checked)'><?php echo $MSG_PROBLEM_ID?></label></th>
      <th class="pm-col-title"><?php echo $MSG_TITLE?></th>
      <th><?php echo $MSG_AC?></th>
      <th><?php echo $MSG_SAVED_DATE?></th>
      <th class="pm-col-title"><?php echo $MSG_SOURCE ?></th><!--분류-->
      <?php if ($pm_can_manage) { ?>
      <th><?php echo $MSG_PROBLEM_STATUS?></th>
      <th><?php echo $MSG_COIN?></th>
      <th>관리</th>
      <?php } ?>
    </tr>
  </thead>
  <tbody>
    <?php
    if (count($result) == 0) {
      echo "<tr><td class='pm-empty' colspan='".($pm_can_manage ? 8 : 5)."'>".($is_search ? "검색 결과가 없습니다." : "등록된 문제가 없습니다.")."</td></tr>";
    }
    foreach($result as $row){
		$view = "";
        echo "<tr>";
        echo "<td><label class='pm-id'><input type=checkbox name='pid[]' value='".$row['problem_id']."'>".$row['problem_id']."</label></td>";
        // 제목·분류는 원문으로 저장되므로(쉼표만 &#44;) 기존 엔티티는 살리고 출력 시 이스케이프한다.
        echo "<td class='pm-col-title'><a href='../problem.php?id=".$row['problem_id']."'>".htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8', false)."</a>";
               if(!empty($row['remote_oj']))echo "<a class='pm-remote' href='".(preg_match('/^https?:\/\//i', $row['source']) ? htmlspecialchars($row['source'], ENT_QUOTES, 'UTF-8') : '#')."' target=_blank>".htmlspecialchars($row['remote_oj'].$row['remote_id'], ENT_QUOTES, 'UTF-8', false)."</a>";
        echo "</td>";
        echo "<td>".$row['accepted']."</td>";
        echo "<td class='pm-date'>".$row['in_date']."</td>";
		echo "<td class='pm-col-source' onDblClick='modify_source(".$row['problem_id'].")' style='cursor: pointer;' title='더블클릭하여 분류 수정'>";//분류

		$category = array();
	    $cate = explode(" ",$row['source']);
	    foreach($cate as $cat){
                        $cat=trim($cat);
                        if(mb_ereg("^http",$cat)){
                                echo "<a class='label' style='display: inline-block;' href=\"".htmlentities($cat,ENT_QUOTES,'utf-8').'" target="_blank">Remote</a>' ;
                        }
		        else array_push($category,trim($cat));
        }
		foreach ($category as $cat) {
			if(trim($cat)==""||trim($cat)=="&nbsp")
				continue;

			$hash_num = hexdec(substr(md5($cat),0,7));
			$label_theme = $color_theme[$hash_num%count($color_theme)];

			if ($label_theme=="") $label_theme = "default";
			$view .= "<a title='".htmlentities($cat,ENT_QUOTES,'UTF-8')."' class='label label-$label_theme' style='display: inline-block;' href='problem_list.php?keyword=".htmlentities(urlencode($cat),ENT_QUOTES,'UTF-8')."'>".htmlspecialchars(mb_substr($cat,0,10,'utf8'), ENT_QUOTES, 'UTF-8', false)."</a>";
		}
		echo "<div id='source_".$row['problem_id']."' class=\"show_tag_controled\">";
    	echo $view;
	if(count(array_filter(array_map('trim', $cate))) < 3) echo "<span class='ai label label-primary' title='AI로 분류 추천' onclick=ai_gen(this,".$row['problem_id'].") > AI+ </span>";
		echo "</div></td>";

        if($pm_can_manage){
            echo "<td><a href=problem_df_change.php?id=".$row['problem_id']."&getkey=".htmlentities($_SESSION[$OJ_NAME.'_'.'getkey'], ENT_QUOTES, 'UTF-8').">".($row['defunct']=="N"?"<span title='눌러서 비공개로 전환' class='admin-badge admin-badge--on'>$MSG_AVAILABLE</span>":"<span class='admin-badge admin-badge--off' title='눌러서 공개로 전환'>$MSG_RESERVED</span>")."</a></td>";
            echo "<td><span class='admin-inline-edit' title='더블클릭하여 수정' fd='coin' problem_id='" . $row['problem_id'] . "'>" . intval($row['coin']) . "</span></td>";
            // 수정·채점 데이터·삭제는 관리자 또는 이 문제의 작성자(pNNNN)만 할 수 있다.
            echo "<td><div class='pm-actions'>";
            if(isset($_SESSION[$OJ_NAME.'_'.'administrator']) || isset($_SESSION[$OJ_NAME.'_'."p".$row['problem_id']]) ){
              echo "<a class='pm-act pm-act--primary' href=problem_edit.php?id=".$row['problem_id']."&getkey=".htmlentities($_SESSION[$OJ_NAME.'_'.'getkey'], ENT_QUOTES, 'UTF-8').">$MSG_EDIT</a>";
              echo "<a class='pm-act' href='javascript:phpfm(".$row['problem_id'].");'>$MSG_TEST_DATA</a>";
              ?>
              <a class="pm-act pm-act--danger" href=# onclick='javascript:if(confirm(<?php echo json_encode($MSG_DELETE."[".htmlentities($row['title'],ENT_QUOTES,"UTF-8")."]?")?>))
                 location.href="problem_del.php?id=<?php echo $row['problem_id']?>&getkey=<?php echo htmlentities($_SESSION[$OJ_NAME.'_'.'getkey'], ENT_QUOTES, 'UTF-8')?>"'><?php echo $MSG_DELETE ?></a>
              <?php
            }
            echo "</div></td>";
        }
    echo "</tr>";
  }
?>
  </tbody>
</table>
</div>
<?php pm_bulkbar(false, "pm-bulkbar--bottom"); ?>
</form>
<?php if (!$is_search) pm_pagination($page, $spage, $epage, $pages, "pm-pager--bottom"); ?>

<script src='../template/bs3/jquery.min.js' ></script>

<script>
function phpfm(pid){
  // 채점 데이터 관리 화면(testdata.php)으로 이동한다.
  document.location.href="testdata.php?pid="+pid;
}
function delPid(pid){

		let plist=sessionStorage.getItem('plist');
		if(typeof(plist)=='undefined'||plist==null) plist="";
		let oldArray=plist.split(',');
		oldArray=oldArray.filter(onlyUnique);
		let index=oldArray.indexOf(pid);
		//	console.log("remove:"+pid+" index:"+index);
			if(index>-1){
				oldArray.splice(index,1);
			}
			plist=oldArray.join();
		if(!!sessionStorage){
		        sessionStorage.setItem('plist',plist);
			$("input[name=hlist]").attr("value",plist);
		//	console.log(plist);
		}
}
function onlyUnique(value, index, array) {
  return array.indexOf(value) === index;
}
function addPid(pid){

		let plist=sessionStorage.getItem('plist');
		if(typeof(plist)=='undefined'||plist==null) plist="";
		let oldArray=plist.split(',');
		//oldArray=oldArray.filter(onlyUnique);
		let index=oldArray.indexOf(pid);
		//	console.log("add:"+pid);
			plist=oldArray.join();
			if(index<0){
				plist+=","+pid;
			}
		if(!!sessionStorage){
		        sessionStorage.setItem('plist',plist);
			$("input[name=hlist]").attr("value",plist);
		
		//	console.log(plist);
		}
}
$(document).ready(function(){
	let plist=sessionStorage.getItem('plist');
	if(typeof(plist)=="undefined" || plist == null) plist="";
	let oldArray=plist.split(',');
	oldArray=oldArray.filter(onlyUnique);
	for(let i=0;i<oldArray.length;i++){
		if(oldArray[i]!="")
			$('input[value='+oldArray[i]+']').prop("checked",true);
	}
	$("input[name=hlist]").val(plist);

	$('input[type=checkbox]').change(function(){
		
		let plist=sessionStorage.getItem('plist');
		if(typeof(plist)=='undefined'||plist==null) plist="";
		let oldArray=plist.split(',');
		oldArray=oldArray.filter(onlyUnique);
		let pid=$(this).attr('value');
		let index=oldArray.indexOf(pid);
//		console.log("pid:"+pid);
//	console.log("before:"+plist);
		if(typeof(pid)=='undefiend'||pid==null ){
//			console.log("all");
			$('input[type=checkbox]').each(function(){
				let pid=parseInt($(this).val());
				if(pid>0){
					if($(this).prop('checked')){
						addPid(""+pid);
					}else{
						delPid(""+pid);
					}
				}
			});
			return;
		}else{
			if($(this).prop('checked')){
				addPid(""+pid);
			}else{
				delPid(""+pid);
			}
		}
//	console.log("after:"+plist);
//		console.log(plist);

	
	});

});
function removeAIEnd(str) {
  // 먼저 앞뒤 공백 제거
  str = str.trim();

  // "AI+"로 끝나는지 확인
  if (str.endsWith("AI+")) {
    // 끝의 "AI+" 제거
    str = str.slice(0, -3);
  }

  // 제거 후 남을 수 있는 공백을 다시 제거
  return str.trim();
}
function modify_source(pid){
	//alert(pid);
	let view=$("#source_"+pid);
	let old=removeAIEnd(view.text());
	view.empty().append($("<input type=text>").attr("id","new_source_"+pid).val(old));
	$('#new_source_'+pid).on("blur change",function (){
		let ns=($(this).val());
		    $.ajax({
		    	url: 'ajax.php', 
			type: 'POST',
			data: { m:'problem_set_source' , pid:pid , ns:ns},
			success: function(data) {
				$("#source_"+pid).empty().append($("<span class='label label-info'>").text(ns));		
			},
			error: function() {
				console.log("분류 추가 실패");
			}
		    });
	});
}
	function fill_data(pid,data){
		if(data.indexOf('请求过于频繁')>-1){
			$("#source_"+pid).text(data);		
			return ;
		}
		console.log(pid+":"+data);	
		    let ns=data;
		    $.ajax({
		    	url: 'ajax.php', 
			type: 'POST',
			data: { m:'problem_add_source' , pid:pid , ns:ns},
			success: function(data) {
				console.log(data);
				if(parseInt(data)>=0){
					$("#source_"+pid).empty().append($("<span class='label label-info'>").text(ns));		
				}
			},
			error: function() {
				console.log("분류 추가 실패");
			}
		    });

	}
	var sleep=10;
	function pull_result(pid,id){
		console.log(id);
	    $.ajax({
		url: '../aiapi/ajax.php', 
		type: 'GET',
		data: { id: id },
		success: function(data) {
			if(data=="waiting"){
				window.setTimeout('pull_result('+pid+','+id+')',1000*Math.sqrt(sleep++));
			}else{
				fill_data(pid,data);
				sleep=1;
			}
		},
		error: function() {
		    	    console.log('데이터 조회 실패');
		}
	    });
	}
	function ai_gen(btn,pid){
		    let oldval=$(btn).text();
		    $(btn).text('AI 생각 중...잠시만 기다려 주세요...');
		    $.ajax({
		    	url: '../<?php echo $OJ_AI_API_URL?>', 
			type: 'GET',
			data: { pid : pid },
			success: function(data) {
				if(parseInt(data)>0)
					window.setTimeout('pull_result('+pid+','+data+')',1000*Math.sqrt(sleep++));
			},
			error: function() {
		    	    $(btn).text('데이터 조회 실패');
			}
		    });
	}


        $("span[fd=coin]").each(function(){
                let sp=$(this);
                let problem_id=$(this).attr("problem_id");
                $(this).dblclick(function(){
                        let val=sp.text();
                        sp.html("<form onsubmit='return false;'><input type=hidden name='m' value='problem_update_coin'><input type=hidden name='problem_id' value='"+problem_id+"'><input type='number' name='coin' value='"+val+"' selected='true' class='input-mini' size=6 ></form>");
                        let ipt=sp.find("input[name=coin]");
                        ipt.focus();
                        ipt[0].select();
                        sp.find("input").change(function(){
                                let newval=sp.find("input[name=coin]").val();
                                $.post("ajax.php",sp.find("form").serialize()).done(function(){
                                        console.log("new coin:"+newval);
                                        sp.html(newval);
                                });

                        });
                });
        });

</script>
</div>
