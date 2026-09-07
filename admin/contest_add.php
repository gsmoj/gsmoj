<?php
  
  require_once("../include/db_info.inc.php");
  require_once("../lang/$OJ_LANG.php");
  require_once("../include/const.inc.php");
  require_once("admin-header.php");
   header("Cache-control:private"); 
if(!(isset($_SESSION[$OJ_NAME.'_'.'administrator'])||isset($_SESSION[$OJ_NAME.'_'.'contest_creator']))){
    exit(1);
  }
  $page_heading = $MSG_CONTEST." - ".$MSG_ADD;
  
?>

<body class="admin-form-page">
<?php
$description = "";
if(isset($_POST['startdate'])){
  require_once("../include/check_post_key.php");

  $starttime = $_POST['startdate']." ".intval($_POST['shour']).":".intval($_POST['sminute']).":00";
  $endtime = $_POST['enddate']." ".intval($_POST['ehour']).":".intval($_POST['eminute']).":00";
  //echo $starttime;
  //echo $endtime;

  $title = $_POST['title'];
  $private = $_POST['private'];
  $password = $_POST['password'];
  $description = $_POST['description'];
  
  if(false){
    $title = stripslashes($title);
    $private = stripslashes($private);
    $password = stripslashes($password);
    $description = stripslashes($description);
  }

  $lang = $_POST['lang'];
  $langmask = 0;
  foreach($lang as $t){
    $langmask += 1<<$t;
  } 

  $langmask = ((1<<count($language_ext))-1)&(~$langmask);
  //echo $langmask; 

  $subnet= $_POST['subnet'];
  $contest_types= $_POST['contest_type'];
  $contest_type=0;
  foreach($contest_types as $t){
    $contest_type |= 1<<$t;
  } 
  $sql = "INSERT INTO `contest`(`title`,`start_time`,`end_time`,`private`,`langmask`,`description`,`password`,subnet,contest_type,`user_id`)
          VALUES(?,?,?,?,?,?,?,?,?,?)";

  $description = str_replace("<p>", "", $description); 
  $description = str_replace("</p>", "<br />", $description);
  $description = str_replace(",", "&#44; ", $description);
  $user_id=$_SESSION[$OJ_NAME.'_'.'user_id'];
 // echo $sql.$title.$starttime.$endtime.$private.$langmask.$description.$password,$user_id;
  $cid = pdo_query($sql,$title,$starttime,$endtime,$private,$langmask,$description,$password,$subnet,$contest_type,$user_id) ;
  echo "Add Contest ".$cid;

  $sql = "DELETE FROM `contest_problem` WHERE `contest_id`=$cid";
  $plist = trim($_POST['cproblem']);
  $pieces = explode(",",$plist );
  $pieces = array_unique($pieces);
  if(count($pieces)>0 && intval($pieces[0])>0){
     
     
    $sql_1 = "INSERT INTO `contest_problem`(`contest_id`,`problem_id`,`num`) VALUES (?,?,?)";
    $plist="";
    $pid=0;
    for($i=0; $i<count($pieces); $i++){
      $sql="select problem_id from problem where problem_id=?";
      $has=pdo_query($sql,$pieces[$i]);
      if(count($has) > 0) {
         if($plist) $plist.=",";
         $plist.=intval($pieces[$i]);
         pdo_query($sql_1,$cid,$pieces[$i],$pid);
         $pid++;
      }else{
         print("Problem not exists:".$pieces[$i]."<br>\n");
      }
    }
    //echo $sql_1;
    $sql = "UPDATE `problem` SET defunct='N' WHERE `problem_id` IN ($plist)";
    pdo_query($sql) ;
  }

  $sql = "DELETE FROM `privilege` WHERE `rightstr`=?";
  pdo_query($sql,"c$cid");

  $sql = "INSERT INTO `privilege` (`user_id`,`rightstr`) VALUES(?,?)";
  pdo_query($sql,$_SESSION[$OJ_NAME.'_'.'user_id'],"m$cid");

  $_SESSION[$OJ_NAME.'_'."m$cid"] = true;
  $pieces = explode("\n", trim($_POST['ulist']));

  if(count($pieces)>0 && strlen($pieces[0])>0){
    $sql_1 = "INSERT INTO `privilege`(`user_id`,`rightstr`) VALUES (?,?)";
    for($i=0; $i<count($pieces); $i++){
      pdo_query($sql_1,trim($pieces[$i]),"c$cid") ;
    }
  }
  echo "<script>window.location.href=\"contest_list.php\";</script>";
}
else{
  if(isset($_GET['cid'])){
    $cid = intval($_GET['cid']);
    $sql = "select * FROM contest WHERE `contest_id`=?";
    $result = pdo_query($sql,$cid);
    $row = $result[0];
    $title = $row['title']."-Copy";

    $private = $row['private'];
    $langmask = $row['langmask'];
    $description = $row['description'];

    $plist = "";
    $sql = "select `problem_id` FROM `contest_problem` WHERE `contest_id`=? ORDER BY `num`";
    $result = pdo_query($sql,$cid);
    foreach($result as $row){
      if($plist) $plist = $plist.',';
      $plist = $plist.$row[0];
    }

    $ulist = "";
    $sql = "select `user_id` FROM `privilege` WHERE `rightstr`=? order by user_id";
    $result = pdo_query($sql,"c$cid");

    foreach($result as $row){
      if($ulist) $ulist .= "\n";
      $ulist .= $row[0];
    }
  }
  else if(isset($_POST['problem2contest'])){
    $plist = "";
    
    sort($_POST['pid']);
    foreach($_POST['pid'] as $i){       
      if($plist)
      	$plist.=','.intval($i);
      else
        $plist=intval($i);
    }
  $plist = trim($_POST['hlist'] ?? '');
  $pieces = explode(",",$plist);
  $pieces = array_unique($pieces);
  if($pieces[0]=="") unset($pieces[0]);
  // 安全修复：每个ID元素强制intval，防止SQL注入
  $plist = implode(",", array_map('intval', $pieces));

  }else if(isset($_GET['spid'])){
    //require_once("../include/check_get_key.php");
    $spid = intval($_GET['spid']);

    $plist = "";
    $sql = "select `problem_id` FROM `problem` WHERE `problem_id`>=? ";
    $result = pdo_query($sql,$spid);
    foreach($result as $row){
      if($plist) $plist.=',';
      $plist.=$row[0];
    }
  }

?>
<html>
<head>
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Cache-Control" content="no-cache">
  <meta http-equiv="Content-Language" content="ko">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>대회 등록</title>
</head>
<?php 
  include_once("kindeditor.php") ;
?>

  <div class="admin-form-shell">
    <header class="admin-form-title"><h3><?php echo htmlentities($page_heading, ENT_QUOTES, 'UTF-8')?></h3></header>
    <form method=POST class="contest-form">
    <section class="contest-form-card">
    <p align=left>
      <label class="admin-field-label">대회 이름</label>
      <input class="input input-xxlarge" style="width:100%;" type=text name=title value="<?php echo isset($title)?$title:""?>"><br><br>
    </p>
    </section>
    <section class="contest-form-card"><div class="contest-form-grid">
    <p align=left>
      <label class="admin-field-label">시작 시간</label>
      <input class=input-large type=date name='startdate' value='<?php echo date('Y').'-'. date('m').'-'.date('d')?>' size=4 >
      시: <input class=input-mini type=text name=shour size=2 value=<?php echo date('H')?>>&nbsp;
      분: <input class=input-mini type=text name=sminute value=00 size=2 >
    </p>
    <p align=left>
      <label class="admin-field-label">종료 시간</label>
      <input class=input-large type=date name='enddate' value='<?php echo date('Y').'-'. date('m').'-'.date('d')?>' size=4 >
      시: <input class=input-mini type=text name=ehour size=2 value=<?php echo (date('H')+4)%24?>>&nbsp;
      분: <input class=input-mini type=text name=eminute value=00 size=2 >
    </p>
    </div></section>
    <section class="contest-form-card"><p align=left>
      <label class="admin-field-label">문제 번호</label>
      <span class="admin-field-help">쉼표로 구분해 입력하세요.</span><br>
      <input id="plist" onchange="showTitles()" class=input-xxlarge placeholder="예: 1000, 1001, 1002" type=text style="width:100%" name=cproblem value="<?php echo isset($plist)?$plist:""?>">
      <div id="ptitles"></div>
    </p>
    <label class="admin-field-label">접속 허용 IP 대역</label>
      <input class=input-xxlarge type=text style="width:100%" name=subnet value='' placeholder='0.0.0.0/0'>
    </section>
    <section class="contest-form-card">
    <p align=left>
      <label class="admin-field-label">대회 설명</label>
      <textarea class=kindeditor rows=13 name=description cols=80><?php echo isset($description)?$description:""?></textarea>
      <br>
      <table class="contest-options-table" width="100%">
        <tr>
          <td rowspan=2>
            <p aligh=left>
              <?php echo $MSG_CONTEST."-".$MSG_LANG?>
              <span class="admin-field-help">Ctrl(Windows) 또는 Cmd(macOS)를 누른 채 여러 언어를 선택할 수 있습니다.</span><br>
              <select name="lang[]" multiple="multiple" style="height:220px">
              <?php
              $lang_count = count($language_ext);
              $lang = (~((int)$langmask))&((1<<$lang_count)-1);

              if(isset($_COOKIE['lastlang'])) $lastlang=$_COOKIE['lastlang'];
              else $lastlang = 0;

              for($i=0; $i<$lang_count; $i++){
		       if( (1<<$i) & $OJ_LANGMASK ) continue;
                       echo "<option value=$i ".( $lang&(1<<$i)?"selected":"").">".$language_name[$i]."</option>";
              }
              ?>
              </select>
            </p>
          </td>
          <td rowspan=2>
            <p aligh=left>
              <?php echo $MSG_FORBIDDEN?><br>
              <?php
              $locks_count = count($contest_locks);
              $contest_lock = 0;
              $contest_type = 32;
              for($i=0; $i<$locks_count; $i++){
                echo "<input type=checkbox name='contest_type[]'  value=$i ".( $contest_type&(1<<$i)?"checked":"").">".$contest_locks[$i]."<br>";
              }
              ?>
            </p>
          </td>
          <td height="10px">
            <p align=left>
              <?php echo $MSG_CONTEST."-".$MSG_Public?>:
              <select name=private style="width:150px;">
                <option value=0 <?php echo $private=='0'?'selected=selected':''?>><?php echo $MSG_Public?></option>
                <option value=1 <?php echo $private=='1'?'selected=selected':''?>><?php echo $MSG_Private?></option>
              </select>
              <?php echo $MSG_CONTEST."-".$MSG_PASSWORD?>:
              <input type=text name=password style="width:150px;" value="">
            </p>
          </td>
        </tr>
        <tr>
          <td height="*">
            <p align=left>
              <?php echo $MSG_CONTEST."-".$MSG_USER?>
              <span class="admin-field-help">비공개 대회에 참여할 사용자 ID를 한 줄에 하나씩 입력하세요.</span>
              <select id="copy_from" onchange="copy_user_from_contest($(this).val());" >
              <option value=0 ><?php echo $MSG_COPY_USER_LIST_FROM_CONTEST ?></option>
                        <?php
                                $contests="0";
                                foreach($_SESSION as $right=>$value){
                                        if(strpos($right,$OJ_NAME.'_m')===0){
                                        //      echo "<option>".substr($right,strlen($OJ_NAME."_m"))."[".strpos($right,$OJ_NAME.'_m')."]</option>";
                                                $contests.=",".substr($right,strlen($OJ_NAME."_m"));
                                        }
                                }
                                $contests=pdo_query("select contest_id,title from contest where contest_id in ($contests)  order by contest_id desc limit 20 ");
                                if(!empty($contests)){

                                        foreach( $contests as $contest ){
                                            echo "<option value='".$contest['contest_id']."'>".$contest['title']."</option>";
                                        }

                                }
                        ?>
                </select>

              <br>
              <textarea id="ulist" name='ulist' rows='10' style='width:100%;' placeholder='user1<?php echo "\n"?>user2<?php echo "\n"?>user3<?php echo "\n"?>
              <?php echo $MSG_PRIVATE_USERS_ADD?><?php echo "\n"?>'><?php if(isset($ulist)){ echo $ulist;}?></textarea>
            </p>
          </td>
        </tr>
      </table>

      </section>
      <div align=center class="contest-form-actions">
        <?php require_once("../include/set_post_key.php");?>
        <input type=submit value='<?php echo $MSG_SAVE?>' name=submit>
      </div>
  </form>
</div>

<script>
  function copy_user_from_contest(cid){
      $("#ulist").val($.ajax({url:"ajax.php",method:"post",data:{"contest_id":cid,"m":"get_user_list_of_contest"},async:false}).responseText);
  }

	function showTitles(){
		let ts=$("#ptitles");
		let pids=$("#plist").val().split(",");
		let html="";
		pids.forEach(function(v,i,a){
			let title=$.ajax({url:"ajax.php",method:"post",data:{"pid":v,"m":"problem_get_title"},async:false}).responseText;
			html+=(v)+":<a href='../problem.php?id="+v+"' target='_blank'>"+title+"</a><br>\n";
			console.log(v);
		});
		ts.html(html);
		
	}
	$(document).ready(function(){
		showTitles();
	
	});

</script>
<?php }

?>
</body>
</html>
