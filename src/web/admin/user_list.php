<?php
require("admin-header.php");
require_once("../include/set_get_key.php");
if(!(isset($_SESSION[$OJ_NAME.'_'.'administrator'])||isset($_SESSION[$OJ_NAME.'_'.'password_setter']))){
  exit(1);
}
if(isset($OJ_LANG)){
  require_once("../lang/$OJ_LANG.php");
}
?>
<title><?php echo $MSG_USER." ".$MSG_LIST?></title>

<div class='padding admin-content-page'>
<div class="admin-page-heading">
  <div><h3><?php echo $MSG_USER." - ".$MSG_LIST?></h3><p><?php echo isset($_GET['trash'])?$MSG_VIEW_DISABLED_USER:$MSG_HELP_USER_LIST?></p></div>
  <div class="admin-heading-actions">
    <?php if(isset($_GET['trash'])){ ?>
    <a href="user_list.php" class="btn btn-default"><?php echo $MSG_USER." - ".$MSG_LIST?></a>
    <?php }else{ ?>
    <a href="user_list.php?trash" class="btn btn-default" title="<?php echo $MSG_VIEW_DISABLED_USER?>">비활성 사용자</a>
    <?php } ?>
    <?php if(isset($_SESSION[$OJ_NAME.'_'.'administrator'])||isset($_SESSION[$OJ_NAME.'_'.'user_adder'])){ ?>
    <a href="user_add.php" class="btn btn-primary"><?php echo $MSG_USER." ".$MSG_ADD?></a>
    <?php } ?>
  </div>
</div>
<?php
$sql = "select COUNT('user_id') AS ids FROM `users`";
$result = pdo_query($sql);
$row = $result[0];
$ids = intval($row['ids']);
$idsperpage = 25;
$pages = intval(ceil($ids/$idsperpage));
if(isset($_GET['page'])){ 
  $page = intval($_GET['page']);
}else{ 
  $page = 1;
}
$pagesperframe = 5;
$frame = intval(ceil($page/$pagesperframe));
$spage = ($frame-1)*$pagesperframe+1;
$epage = min($spage+$pagesperframe-1, $pages);
$sid = ($page-1)*$idsperpage;
$sql = "";
$gkeyword="";
$trash="";
if(isset($_GET['keyword']) && $_GET['keyword']!=""){
  $gkeyword = $_GET['keyword'];
  $keyword = "%$gkeyword%";
  $sql = "select `user_id`,`nick`,email,`accesstime`,`reg_time`,`expiry_date`,`ip`,`school`,`group_name`,`defunct`,`coin_earned`,`coin_bonus`,`coin_spent` FROM `users` WHERE (user_id LIKE ?) OR (nick LIKE ?) OR (school LIKE ?)  OR (group_name LIKE ?) or (ip like ?) ORDER BY `user_id` DESC";
  $result = pdo_query($sql,$keyword,$keyword,$keyword,$keyword,$keyword);
}else if(isset($_GET['trash'])){
  $trash="&trash";
  $sql = "select `user_id`,`nick`,email,`accesstime`,`reg_time`,`expiry_date`,`ip`,`school`,`group_name`,`defunct`,`coin_earned`,`coin_bonus`,`coin_spent` FROM `users` where defunct='Y' ORDER BY `accesstime` DESC LIMIT $sid, $idsperpage";
  $result = pdo_query($sql);
}else{
  $sql = "select `user_id`,`nick`,email,`accesstime`,`reg_time`,`expiry_date`,`ip`,`school`,`group_name`,`defunct`,`coin_earned`,`coin_bonus`,`coin_spent` FROM `users` where defunct='N' ORDER BY `accesstime` DESC LIMIT $sid, $idsperpage";
  $result = pdo_query($sql);
}
?>

<form action=user_list.php class="form-search form-inline admin-list-search">
  <input type="text" name="keyword"  value="<?php echo htmlentities($gkeyword,ENT_QUOTES) ?>"  class="form-control search-query" placeholder="<?php echo $MSG_USER_ID.', '.$MSG_NICK.', '.$MSG_SCHOOL?>">
  <button type="submit" class="btn btn-primary"><?php echo $MSG_SEARCH?></button>
</form>

  <div class="admin-table-wrap">
  <table class="table admin-user-table">
<thead>
    <tr>
    <th><?php echo $MSG_USER_ID?></th>
      <th><?php echo $MSG_NICK?></th>
      <th>IP</th>
      <th><?php echo $MSG_EMAIL?></th>
      <th><?php echo $MSG_SCHOOL?></th>
      <th><?php echo $MSG_GROUP_NAME?></th>
      <th><?php echo $MSG_LAST_LOGIN?></th>
      <th><?php echo $MSG_REGISTER?></th>
      <th><?php echo $MSG_EXPIRY_DATE?></th>
      <th><?php echo $MSG_EARNED.$MSG_COIN?></th>
      <th><?php echo $MSG_BONUS.$MSG_COIN?></th>
      <th><?php echo $MSG_SPENT.$MSG_COIN?></th>
      <th><?php echo $MSG_COIN_BALANCE ?></th>
      <th><?php echo $MSG_STATUS?></th>
      <th><?php echo $MSG_ADMIN ?></th>
      <th><?php echo $MSG_SETPASSWORD?></th>
      <th><?php echo $MSG_PRIVILEGE."-".$MSG_ADD ?></th>
      </tr>
</thead>
<tbody>
    <?php
    foreach($result as $row){
      echo "<tr>";
        echo "<td><a href='../userinfo.php?user=".htmlentities(urlencode($row['user_id']))."'>".$row['user_id']."</a></td>";
        if($row['nick']=="") $row['nick']="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        echo "<td><span class='admin-inline-edit' title='더블클릭하여 수정' fd='nick' user_id='".$row['user_id']."'>".$row['nick']."</span></td>";
        echo "<td><a href='user_list.php?keyword=".htmlentities(urlencode($row['ip']))."' >".$row['ip']."</a></td>";
        if($OJ_SaaS_ENABLE && $domain == $DOMAIN){
                echo "<td><a href='http://".$row['user_id'].".$DOMAIN' target=_blank >".$row['email']."&nbsp;</a></td>";
        }else{
                echo "<td>".$row['email']." </td>";
        }
        if($row['school']=="") $row['school']="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        echo "<td><span class='admin-inline-edit' title='더블클릭하여 수정' fd='school' user_id='".$row['user_id']."'>".$row['school']."</span></td>";
        if($row['group_name']=="") $row['group_name']="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        echo "<td><span class='admin-inline-edit' title='더블클릭하여 수정' fd='group_name' user_id='".$row['user_id']."'>".$row['group_name']."</span></td>";
        echo "<td>".$row['accesstime']."</td>";
        echo "<td>".$row['reg_time']."</td>";
        $color="expired";
        $edate= new DateTime($row['expiry_date']);
        $tomorrow= new DateTime(add_days(7));   // 7 日临期预警蓝色
        $today= new DateTime(add_days(0));
        if($edate>$tomorrow) $color="ok";
        else if($edate>=$today) $color="soon";
        echo "<td><span fd='expiry_date' user_id='".$row['user_id']."' class='admin-inline-edit admin-expiry admin-expiry--".$color."' title='더블클릭하여 수정' >".$row['expiry_date']."</span></td>";

        echo "<td>".intval($row['coin_earned'])."</td>";
        echo "<td><span class='admin-inline-edit' title='더블클릭하여 수정' fd='coin_bonus' user_id='".$row['user_id']."'>".intval($row['coin_bonus'])."</span></td>";
        echo "<td><span class='admin-inline-edit' title='더블클릭하여 수정' fd='coin_spent' user_id='".$row['user_id']."'>".intval($row['coin_spent'])."</span></td>";
        echo "<td>".intval($row['coin_earned']+$row['coin_bonus']-$row['coin_spent'])."</td>";
        echo "<td>".($row['defunct']=="N"?"<span class='admin-badge admin-badge--on'>$MSG_NORMAL</span>":"<span class='admin-badge admin-badge--off'>$MSG_DELETED</span>")."</td>";
      if(isset($_SESSION[$OJ_NAME.'_'.'administrator']) && $row['user_id']!=$_SESSION[$OJ_NAME."_user_id"]){
        echo "<td><a href=user_df_change.php?cid=".$row['user_id']."&getkey=".htmlentities($_SESSION[$OJ_NAME.'_'.'getkey'], ENT_QUOTES, 'UTF-8').">".
           ($row['defunct']=="N"?"<span class='admin-badge admin-badge--warn' title='$MSG_CLICK_TO_DELETE'>$MSG_CLICK_TO_DELETE</span>":"<span class='admin-badge admin-badge--info' title='$MSG_CLICK_TO_RECOVER'>$MSG_CLICK_TO_RECOVER</span>")
            ."</a></td>";
      }else{
      	   echo "<td>&nbsp;</td>";
      }
        echo "<td><a class='btn btn-default btn-xs' href=changepass.php?uid=".$row['user_id']."&getkey=".htmlentities($_SESSION[$OJ_NAME.'_'.'getkey'], ENT_QUOTES, 'UTF-8').">".$MSG_RESET."</a></td>";
        echo "<td><a class='btn btn-default btn-xs' href=privilege_add.php?uid=".$row['user_id']."&getkey=".htmlentities($_SESSION[$OJ_NAME.'_'.'getkey'], ENT_QUOTES, 'UTF-8').">".$MSG_ADD."</a></td>";
      echo "</tr>";
    } ?>
</tbody>
  </table>
  </div>

<?php
if(!(isset($_GET['keyword']) && $_GET['keyword']!=""))
{
  echo "<div style='display:inline;'>";
  echo "<nav class='center'>";
  echo "<ul class='pagination pagination-sm'>";
  echo "<li class='page-item'><a href='user_list.php?page=".(strval(1))."$trash'>&lt;&lt;</a></li>";
  echo "<li class='page-item'><a href='user_list.php?page=".($page==1?strval(1):strval($page-1))."$trash'>&lt;</a></li>";
  for($i=$spage; $i<=$epage; $i++){
    echo "<li class='".($page==$i?"active ":"")."page-item'><a title='go to page' href='user_list.php?page=".$i."$trash'>".$i."</a></li>";
  }
  echo "<li class='page-item'><a href='user_list.php?page=".($page==$pages?strval($page):strval($page+1))."$trash'>&gt;</a></li>";
  echo "<li class='page-item'><a href='user_list.php?page=".(strval($pages))."$trash'>&gt;&gt;</a></li>";
  echo "</ul>";
  echo "</nav>";
  echo "</div>";
}
?>

</div>
<script>
function admin_mod(){
        $("span[fd=group_name]").each(function(){
                let sp=$(this);
                let user_id=$(this).attr('user_id');
                $(this).dblclick(function(){
                        let group_name=sp.text();
                        sp.html("<form onsubmit='return false;'><input type=hidden name='m' value='user_update_group_name'><input type='hidden' name='user_id' value='"+user_id+"'><input type='text' name='group_name' value='"+group_name+"' selected='true' class='input-large' size=20 ></form>");
                        let ipt=sp.find("input[name=group_name]");
                        ipt.focus();
                        ipt[0].select();
                        sp.find("input").change(function(){
                                let newgroup_name=sp.find("input[name=group_name]").val();
                                $.post("ajax.php",sp.find("form").serialize()).done(function(){
                                        console.log("new group_name"+newgroup_name);
                                        sp.html(newgroup_name);
                                });

                        });
                });
        });
        $("span[fd=school]").each(function(){
                let sp=$(this);
                let user_id=$(this).attr('user_id');
                $(this).dblclick(function(){
                        let school=sp.text();
                        sp.html("<form onsubmit='return false;'><input type=hidden name='m' value='user_update_school'><input type='hidden' name='user_id' value='"+user_id+"'><input type='text' name='school' value='"+school+"' selected='true' class='input-large' size=20 ></form>");
                        let ipt=sp.find("input[name=school]");
                        ipt.focus();
                        ipt[0].select();
                        sp.find("input").change(function(){
                                let newschool=sp.find("input[name=school]").val();
                                $.post("ajax.php",sp.find("form").serialize()).done(function(){
                                        console.log("new school"+newschool);
                                        sp.html(newschool);
                                });

                        });
                });
        });

        $("span[fd=nick]").each(function(){
                let sp=$(this);
                let user_id=$(this).attr('user_id');
                $(this).dblclick(function(){
                        let nick=sp.text();
                        console.log("user_id:"+user_id+"  nick:"+nick);
                        sp.html("<form onsubmit='return false;'><input type=hidden name='m' value='user_update_nick'><input type='hidden' name='user_id' value='"+user_id+"'><input type='text' name='nick' value='"+nick+"' selected='true' class='input-mini' size=2 ></form>");
                        let ipt=sp.find("input[name=nick]");
                        ipt.focus();
                        ipt[0].select();
                        sp.find("input").change(function(){
                                let newnick=sp.find("input[name=nick]").val();
                                $.post("ajax.php",sp.find("form").serialize()).done(function(){
                                        console.log("new nick:"+newnick);
                                        sp.html(newnick);
                                });

                        });
                });


        });
        $("span[fd=expiry_date]").each(function(){
                let sp=$(this);
                let user_id=$(this).attr('user_id');
                $(this).dblclick(function(){
                        let expiry_date=sp.text();
                        console.log("user_id:"+user_id+"  expiry_date:"+expiry_date);
                        sp.html("<form onsubmit='return false;'><input type=hidden name='m' value='user_update_expiry_date'><input type='hidden' name='user_id' value='"+user_id+"'><input type='date' name='expiry_date' value='"+expiry_date+"' selected='true' class='input-mini' size=2 ></form>");
                        let ipt=sp.find("input[name=expiry_date]");
                        ipt.focus();
                        ipt[0].select();
                        sp.find("input").change(function(){
                                let newexpiry_date=sp.find("input[name=expiry_date]").val();
                                $.post("ajax.php",sp.find("form").serialize()).done(function(){
                                        console.log("new expiry_date:"+newexpiry_date);
                                        sp.html(newexpiry_date);
                                });

                        });
                });

        });
        $("span[fd=coin_bonus]").each(function(){
                let sp=$(this);
                let user_id=$(this).attr('user_id');
                $(this).dblclick(function(){
                        let val=sp.text();
                        sp.html("<form onsubmit='return false;'><input type=hidden name='m' value='user_update_coin_bonus'><input type=hidden name='user_id' value='"+user_id+"'><input type='number' name='coin_bonus' value='"+val+"' selected='true' class='input-mini' size=6 ></form>");
                        let ipt=sp.find("input[name=coin_bonus]");
                        ipt.focus();
                        ipt[0].select();
                        sp.find("input").change(function(){
                                let newval=sp.find("input[name=coin_bonus]").val();
                                $.post("ajax.php",sp.find("form").serialize()).done(function(){
                                        console.log("new coin_bonus:"+newval);
                                        sp.html(newval);
                                });

                        });
                });


        });
        $("span[fd=coin_spent]").each(function(){
                let sp=$(this);
                let user_id=$(this).attr('user_id');
                $(this).dblclick(function(){
                        let val=sp.text();
                        sp.html("<form onsubmit='return false;'><input type=hidden name='m' value='user_update_coin_spent'><input type=hidden name='user_id' value='"+user_id+"'><input type='number' name='coin_spent' value='"+val+"' selected='true' class='input-mini' size=6 ></form>");
                        let ipt=sp.find("input[name=coin_spent]");
                        ipt.focus();
                        ipt[0].select();
                        sp.find("input").change(function(){
                                let newval=sp.find("input[name=coin_spent]").val();
                                $.post("ajax.php",sp.find("form").serialize()).done(function(){
                                        console.log("new coin_spent:"+newval);
                                        sp.html(newval);
                                });

                        });
                });


        });

}
$(document).ready(function(){
        admin_mod();
});

</script>
