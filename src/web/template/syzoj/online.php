<?php $show_title="$MSG_HELP_ONLINE - $OJ_NAME"; ?>
<?php include("template/$OJ_TEMPLATE/header.php");?>

<div class="padding">
	<?php	
		if(isset($_SESSION[$OJ_NAME.'_'.'administrator'])){
	?>
  <form class="ui mini form" role="form" style="margin-bottom: 25px; text-align: right; ">
    <div class="ui action left icon input inline" style="width: 180px; margin-right: 77px; ">
      <i class="search icon"></i><input name="search" placeholder="IP" type="text">
      <button class="ui mini button" type="submit"><?php echo $MSG_SEARCH ?></button>
    </div>
  </form>

	    <table class="ui very basic center aligned table" style="table-layout: fixed; ">
	        <thead>
	        <tr>
			<th>IP</th>
			<th>접속 주소</th>
			<th>이전 페이지</th>
			<th>접속 시간</th>
			<th>브라우저 정보</th>
	        </tr>
	        </thead>
	        <tbody>

		<?php 
		foreach($users as $u){
			 if(!empty($u)){
		?>
		<tr>
			<td><?php $l = $location->getlocation($u['ip']);
                                echo $u['ip'].'<br />';
                                echo $l??"";
                            ?>
                        </td>
			<td><?php echo $u['uri']?></td>
			<td><?php echo $u['refer']?></td>
			<td><?php echo sprintf("%dmin %dsec",($u['lastmove']-$u['firsttime'])/60,($u['lastmove']-$u['firsttime']) % 60)?></td>
			<td><?php echo $u['ua']?></td>
		</tr>
		<?php 
				}
			}
		?>	

	        </tbody>
	    </table>



	    <table class="ui very basic center aligned table" style="table-layout: fixed; ">
	        <thead>
	        <tr>
         		<th><?php echo $MSG_USER ?></th>
			<th>로그인 결과</th>
			<th>IP</th>
			<th><?php echo $MSG_TIME ?></th>
	        </tr>
	        </thead>
	        <tbody>
			<?php 
				foreach($view_online as $row){
					echo "<tr>";
					$i=0;
					foreach($row as $table_cell){
						echo "<td>";
						echo "\t".$table_cell;
						if($i==2){
                                                        $l = $location->getlocation( $table_cell );
                                                        echo "<br>".$l['country'].$l['area']??"";
                                                }
						$i++;
						echo "</td>";
					}
					echo "</tr>";
				}
			?>

	        </tbody>
	    </table>
	<?php
		} else {
			// 관리자가 아니면 IP·접속 주소·로그인 기록 없이 접속자 수만 보여 준다.
			$online_count = count(array_filter($users));
	?>
	    <p><?php echo $MSG_HELP_ONLINE ?>: <?php echo $online_count ?>명</p>
	<?php
		}
	?>


</div>

<?php include("template/$OJ_TEMPLATE/footer.php");?>
