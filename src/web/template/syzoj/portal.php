<?php
  include(dirname(__FILE__)."/header.php");
?>
<div class="gs-page-head">
  <h1><?php echo $MSG_TODO?></h1>
  <p>아직 풀지 않은 문제와 참여할 수 있는 대회를 모아 보여 줘요</p>
</div>
<div class="padding">
  <div class="gs-table-wrap">
  <table class="ui very basic center aligned table">
					<thead>
						<tr>
							<td></td>
							<td class='hidden-xs center'>
								<?php echo $MSG_PROBLEM_ID?>
							</td>
							<td class='center'>
								<?php echo $MSG_TITLE?>
							</td>
							<td class='hidden-xs center'>
								<?php echo $MSG_SOURCE?>
							</td>
							<td style="cursor:hand" class='center'>
								<?php echo $MSG_SOVLED?>
							</td>
							<td style="cursor:hand" class='center'>
								<?php echo $MSG_SUBMIT?>
							</td>
						</tr>
					</thead>
    <tbody>
						<?php
						$cnt = 0;
						foreach ( $view_problemset as $row ) {
							if ( $cnt )
								echo "<tr class='oddrow'>";
							else
								echo "<tr class='evenrow'>";
							$i = 0;
							foreach ( $row as $table_cell ) {
								if ( $i == 1 || $i == 3 )echo "<td  class='hidden-xs'>";
								else echo "<td>";
								echo "\t" . $table_cell;
								echo "</td>";
								$i++;
							}
							echo "</tr>";
							$cnt = 1 - $cnt;
						}
						?>
    </tbody>
  </table>
  </div>
</div>
<div class="padding">
  <div class="gs-table-wrap">
      <table class='ui very basic center aligned table'>
        <thead>
          <tr>
            <td><?php echo $MSG_CONTEST_ID?></td>
            <td><?php echo $MSG_CONTEST_NAME?></td>
            <td><?php echo $MSG_CONTEST_STATUS?></td>
            <td><?php echo $MSG_CONTEST_OPEN?></td>
            <td><?php echo $MSG_CONTEST_CREATOR?></td>
          </tr>
        </thead>
        <tbody align='center'>
          <?php
          $cnt=0;
          foreach($view_contest as $row){
            if ($cnt)
              echo "<tr class='oddrow'>";
            else
              echo "<tr class='evenrow'>";
            $i=0;
            foreach($row as $table_cell){
              if($i==2) echo "<td class=text-left>";
              else echo "<td>";
              echo "\t".$table_cell;
              echo "</td>";
              $i++;
            }
            echo "</tr>";
            $cnt=1-$cnt;
          }
          ?>
        </tbody>
      </table>
  </div>
</div>

<?php include(dirname(__FILE__)."/footer.php");?>
