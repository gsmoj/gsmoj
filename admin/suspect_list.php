<?php
require("admin-header.php");

//if (!(isset($_SESSION[$OJ_NAME.'_'.'administrator'])|| isset($_SESSION[$OJ_NAME.'_'.'contest_creator']))) {
//
//	exit(1);
//}

if (isset($OJ_LANG)) {
	require_once("../lang/$OJ_LANG.php");
}
?>

<title><?php echo $MSG_IP_VERIFICATION?></title>

<div class='padding admin-content-page'>
<div class="admin-page-heading">
	<div><h3><?php echo $MSG_IP_VERIFICATION?></h3><p><?php echo $MSG_CONTEST." #".intval($_GET['cid'])?></p></div>
	<a href="contest_list.php" class="btn btn-default"><?php echo $MSG_CONTEST." ".$MSG_LIST?></a>
</div>

	<?php
	require_once("../include/set_get_key.php");

	$contest_id = intval($_GET['cid']);

	$sql = "select * from (select count(distinct user_id) c,ip from solution where contest_id=? group by ip) suspect inner join (select distinct ip, user_id, in_date from solution where contest_id=? ) u on suspect.ip=u.ip and suspect.c>1 order by c desc, u.ip, in_date, user_id";

	$result = pdo_query($sql,$contest_id,$contest_id);
	?>

	<section class="admin-suspect-section">
		<p class="admin-field-help"><?php echo $MSG_CONTEST_SUSPECT1?></p>
		<div class="admin-table-wrap">
		<table class="table admin-suspect-table">
			<thead>
				<tr>
					<th>IP 주소</th>
					<th colspan=2><?php echo $MSG_USER_ID?></th>
					<th><?php echo $MSG_TIME?></th>
					<th>사용 ID 수</th>
				</tr>
			</thead>
			<tbody>

				<?php
				foreach ($result as $row) {
					echo "<tr>";
						echo "<td>".$row['ip']."</td>";
						echo "<td>".$row['user_id']."</td>";
						echo "<td class='admin-suspect-links'>";
							echo "<a href='../userinfo.php?user=".$row['user_id']."'>".$MSG_USERINFO."</a> / ";
							echo "<a href='../status.php?cid=$contest_id&user_id=".$row['user_id']."'>".$MSG_CONTEST." ".$MSG_SUBMIT."</a>";
						echo "</td>";
            echo "<td>".$row['in_date']."</td>";
						echo "<td>".$row['c']."</td>";
					echo "</tr>";
				}
				?>
			</tbody>
		</table>
		</div>
	</section>

	<?php
	$start = pdo_query("select start_time from contest where contest_id=?",$contest_id)[0][0];
	$end = pdo_query("select end_time from contest where contest_id=?",$contest_id)[0][0];
	$sql = "select * from (select count(distinct ip) c,user_id from loginlog where time>=? and time<=? group by user_id) suspect inner join (select distinct user_id from solution where contest_id=? ) u on suspect.user_id=u.user_id and suspect.c>1 inner join (select distinct ip, user_id, time from loginlog where time>=? and time<=? ) ips on ips.user_id=u.user_id order by c desc, u.user_id, ips.time, ip";
	$result = pdo_query($sql,$start,$end,$contest_id,$start,$end);
	?>

	<section class="admin-suspect-section">
		<p class="admin-field-help"><?php echo $MSG_CONTEST_SUSPECT2?></p>
		<div class="admin-table-wrap">
		<table class="table admin-suspect-table">
			<thead>
				<tr>
					<th colspan=2><?php echo $MSG_USER_ID?></th>
					<th>IP 주소</th>
					<th><?php echo $MSG_TIME?></th>
					<th>사용 IP 수</th>
				</tr>
			</thead>
			<tbody>

				<?php
				foreach ($result as $row) {
					echo "<tr>";
						echo "<td>".$row['user_id']."</td>";
						echo "<td class='admin-suspect-links'>";
							echo "<a href='../userinfo.php?user=".$row['user_id']."'>".$MSG_USERINFO."</a> / ";
							echo "<a href='../status.php?cid=$contest_id&user_id=".$row['user_id']."'>".$MSG_CONTEST." ".$MSG_SUBMIT."</a>";
						echo "</td>";
						echo "<td>".$row['ip']."</td>";
						echo "<td>".$row['time']."</td>";
						echo "<td>".$row['c']."</td>";
						echo "</tr>";
				}
				?>
			</tbody>
		</table>
		</div>
	</section>

</div>

<?php
require("../oj-footer.php");
?>
