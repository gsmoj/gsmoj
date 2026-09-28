<?php require("admin-header.php");
require_once("../include/const.inc.php");
if (!(isset($_SESSION[$OJ_NAME.'_'.'administrator']))){
        exit(1);
}?>
<?php if(isset($_POST['do'])){
        require_once("../include/check_post_key.php");
        if (isset($_POST['rjpid'])){
                $rjpid=intval($_POST['rjpid']);
                if($rjpid == 0) {
                    echo "Rejudge Problem ID should not equal to 0";
                    exit(1);
                }
                $sql="UPDATE `solution` SET `result`=1,pass_rate=0 WHERE `problem_id`=? and problem_id>0";
                pdo_query($sql,$rjpid) ;
                $sql="UPDATE `problem` SET `accepted`=0 WHERE `problem_id`=? and problem_id>0";
                pdo_query($sql,$rjpid) ;
                $sql="delete from `sim` WHERE `s_id` in (select solution_id from solution where `problem_id`=?)";
                pdo_query($sql,$rjpid) ;
                $url="../status.php?problem_id=".$rjpid;
                echo "Rejudged Problem ".$rjpid;
                echo "<script>location.href='$url';</script>";
        }else if (isset($_POST['rjsid'])){
                $rjsid=intval($_POST['rjsid']);
                $sql="delete from `sim` WHERE `s_id`=?";
                pdo_query($sql,$rjsid) ;
                $sql="UPDATE `solution` SET `result`=1,pass_rate=0 WHERE `solution_id`=? and problem_id>0" ;
                pdo_query($sql,$rjsid) ;
                $sql="select contest_id from `solution` WHERE `solution_id`=? " ;
                $data=pdo_query($sql,$rjsid);
                $row=$data[0];
                $cid=intval($row[0]);
                if ($cid>0)
                        $url="../status.php?cid=".$cid."&top=".($rjsid+1);
                else
                        $url="../status.php?top=".($rjsid+1);
                echo "Rejudged Runid ".$rjsid;
                echo "<script>location.href='$url';</script>";
        }else if (isset($_POST['result'])){
                $result=intval($_POST['result']);
                $to=intval($_POST['to']);
                $sql="UPDATE `solution` SET `result`=$to,pass_rate=0 WHERE `result`=? and problem_id>=0" ;
                pdo_query($sql,$result) ;
                $url="../status.php?jresult=$to";
                echo "<script>location.href='$url';</script>";
        }else if (isset($_POST['rjcid'])){
                $rjcid=intval($_POST['rjcid']);
		if( $rjcid>0 ){
			if(isset($_POST['pid'])){
				$pid=intval($_POST['pid']);
				$sql="UPDATE `solution` SET `result`=1,pass_rate=0 WHERE `contest_id`=? and num=?";
				pdo_query($sql,$rjcid,$pid) ;
			}else{
				$sql="UPDATE `solution` SET `result`=1,pass_rate=0 WHERE `contest_id`=? and problem_id>0";
				pdo_query($sql,$rjcid) ;
			}
			$url="../status.php?cid=".($rjcid);
			echo "Rejudged Contest id :".$rjcid;
			echo "<script>location.href='$url';</script>";
		}else{
			echo "<script>location.href='rejudge.php';</script>";
		}
        }
        echo str_repeat(" ",4096);
        flush();
        if($OJ_REDIS){
           $redis = new Redis();
           $redis->connect($OJ_REDISSERVER, $OJ_REDISPORT);
           if(isset($OJ_REDISAUTH)) $redis->auth($OJ_REDISAUTH);
                $sql="select solution_id from solution where result=1,pass_rate=0 and problem_id>0";
                 $result=pdo_query($sql);
                 foreach($result as $row){
                        echo $row['solution_id']."\n";
                        $redis->lpush($OJ_REDISQNAME,$row['solution_id']);
                }
           $redis->close();
        }
        if (isset($OJ_UDP) && $OJ_UDP) {
           trigger_judge();
        }
}
?>
<title><?php echo $MSG_REJUDGE?></title>
<script>document.body.classList.add('admin-form-page');</script>
<div class="admin-form-shell">
<header class="admin-form-title">
        <h3><?php echo htmlentities($MSG_SYSTEM." - ".$MSG_REJUDGE, ENT_QUOTES, 'UTF-8')?></h3>
        <p><?php echo $MSG_HELP_REJUDGE?>. 실행하면 해당 제출이 모두 다시 채점 대기열에 들어갑니다.</p>
</header>
        <section class="admin-tool-card">
        <h4>1) <?php echo $MSG_PROBLEM?> <?php echo $MSG_REJUDGE?></h4>
        <form action='rejudge.php' method=post class="admin-tool-form">
                <input type=text class="form-control" name='rjpid' placeholder="<?php echo $MSG_PROBLEM_ID?> (예: 1001)" aria-label="<?php echo $MSG_PROBLEM_ID?>">      <input type='hidden' name='do' value='do'>
                <input type=submit class="btn btn-primary" value='<?php echo $MSG_REJUDGE?>'>
                <?php require_once("../include/set_post_key.php");?>
        </form>
        </section>
        <section class="admin-tool-card">
        <h4>2) <?php echo $MSG_SUBMIT?> <?php echo $MSG_REJUDGE?></h4>
        <form action='rejudge.php' method=post class="admin-tool-form">
                <input type=text class="form-control" name='rjsid' placeholder="<?php echo $MSG_RUNID?> (예: 1002)" aria-label="<?php echo $MSG_RUNID?>">      <input type='hidden' name='do' value='do'>
                <input type=hidden name="postkey" value="<?php echo htmlentities($_SESSION[$OJ_NAME.'_'.'postkey'], ENT_QUOTES, 'UTF-8')?>">
                <input type=submit class="btn btn-primary" value='<?php echo $MSG_REJUDGE?>'>
        </form>
        </section>
        <section class="admin-tool-card">
        <h4>3) <?php echo "$MSG_Manual"?></h4>
        <p class="admin-field-help">선택한 결과의 제출을 모두 다른 결과로 바꿉니다.</p>
        <form action='rejudge.php' method=post class="admin-tool-form">
		<select name='result' class="form-control" aria-label="변경 전 결과">      
			<?php
				for($i=0;$i<4;$i++){
					echo "<option value='$i' ".($i==3?"selected":"").">".$jresult[$i]."</option>\n";
				}
				for($i=16;$i<17;$i++){
					echo "<option value='$i' >".$jresult[$i]."</option>\n";
				}
	
			?>
		</select>
		<input type='hidden' name='do' value='do'> <span aria-hidden="true">&rarr;</span>
		<select name='to' class="form-control" aria-label="변경 후 결과">      
			<?php
				$i=1;
				echo "<option value='$i'>".$jresult[$i]."</option>\n";
				for($i=4;$i<18;$i++){
					echo "<option value='$i' ".($i==6?"selected":"").">".$jresult[$i]."</option>\n";
				}
	
			?>
		</select>
                <input type=hidden name="postkey" value="<?php echo htmlentities($_SESSION[$OJ_NAME.'_'.'postkey'], ENT_QUOTES, 'UTF-8')?>">
                <input type=submit class="btn btn-primary" value='결과 변경'>
        </form>
        </section>
        <section class="admin-tool-card">
        <h4>4) <?php echo $MSG_CONTEST?> <?php echo $MSG_REJUDGE?></h4>
        <p class="admin-field-help">대회 전체 또는 대회의 특정 문제만 다시 채점합니다.</p>
        <form action='rejudge.php' method=post class="admin-tool-form">
                <input type=text class="form-control" name='rjcid' placeholder="1003" value='1003' aria-label="<?php echo $MSG_CONTEST_ID?>"> <input type='hidden' name='do' value='do'>
                <input type=hidden name="postkey" value="<?php echo htmlentities($_SESSION[$OJ_NAME.'_'.'postkey'], ENT_QUOTES, 'UTF-8')?>">
                <input type=submit class="btn btn-primary" value='<?php echo $MSG_CONTEST?> 전체 <?php echo $MSG_REJUDGE?>'>
        </form>
        <form action='rejudge.php' method=post class="admin-tool-form">
                <input type=text class="form-control" name='rjcid' placeholder="<?php echo $MSG_CONTEST_ID?> (예: 1004)" aria-label="<?php echo $MSG_CONTEST_ID?>">
                <select name=pid class="form-control" aria-label="<?php echo $MSG_PROBLEM?>">
<?php
                foreach($PID as $i=>$id){
                        echo "<option value='$i'>$id</option>";
                }

                ?>
                </select>
                <input type='hidden' name='do' value='do'>
                <input type=hidden name="postkey" value="<?php echo htmlentities($_SESSION[$OJ_NAME.'_'.'postkey'], ENT_QUOTES, 'UTF-8')?>">
                <input type=submit class="btn btn-primary" value='선택 문제 <?php echo $MSG_REJUDGE?>'>
        </form>
        </section>
</div>
