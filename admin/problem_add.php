<?php
require_once ("admin-header.php");
require_once("../include/check_post_key.php");
if (!(isset($_SESSION[$OJ_NAME.'_'.'administrator']) || isset($_SESSION[$OJ_NAME.'_'.'contest_creator']) || isset($_SESSION[$OJ_NAME.'_'.'problem_editor']))) {
  exit(1);
}

require_once ("../include/db_info.inc.php");
require_once ("../include/my_func.inc.php");
require_once ("../include/problem.php");

function normalize_problem_markdown($content, $auto_select = false)
{
  global $OJ_MARKDOWN;

  $content = str_replace(array("\r\n", "\r"), "\n", trim($content));
  $plain_content = trim(html_entity_decode(strip_tags($content), ENT_QUOTES, 'UTF-8'), " \t\n\r\0\x0B\xC2\xA0");
  if ($plain_content === '') return '';
  if (empty($OJ_MARKDOWN) || preg_match('/class=["\'][^"\']*\\bmd\\b/i', $content)) return $content;

  $class_name = $auto_select ? 'md auto_select' : 'md';
  return "<span class='" . $class_name . "'>\n" . $content . "\n</span>";
}

// contest_id
$title = $_POST['title'];
$title = str_replace(",", "&#44;", $title);
$time_limit = $_POST['time_limit'];
$memory_limit = $_POST['memory_limit'];

$description = normalize_problem_markdown($_POST['description'], true);
//$description = str_replace("<p>", "", $description); 
//$description = str_replace("</p>", "<br />", $description);
$description = str_replace(",", "&#44;", $description); 

$input = normalize_problem_markdown($_POST['input']);
//$input = str_replace("<p>", "", $input); 
//$input = str_replace("</p>", "<br />", $input); 
$input = str_replace(",", "&#44;", $input);

$output = normalize_problem_markdown($_POST['output']);
//$output = str_replace("<p>", "", $output); 
//$output = str_replace("</p>", "<br />", $output);
$output = str_replace(",", "&#44;", $output); 

$sample_input = $_POST['sample_input'];
$sample_output = $_POST['sample_output'];
$test_input = $_POST['test_input'];
$test_output = $_POST['test_output'];
/* don't do this , we will left them empty for not generating invalid test data files 
if ($sample_input=="") $sample_input="\n";
if ($sample_output=="") $sample_output="\n";
if ($test_input=="") $test_input="\n";
if ($test_output=="") $test_output="\n";
*/
$hint = normalize_problem_markdown($_POST['hint']);
//$hint = str_replace("<p>", "", $hint); 
//$hint = str_replace("</p>", "<br />", $hint); 
$hint = str_replace(",", "&#44;", $hint);

$source = $_POST['source'];

$spj = $_POST['spj'];


if (false) {
  $title = stripslashes($title);
  $time_limit = stripslashes($time_limit);
  $memory_limit = stripslashes($memory_limit);
  $description = stripslashes($description);
  $input = stripslashes($input);
  $output = stripslashes($output);
  $sample_input = stripslashes($sample_input);
  $sample_output = stripslashes($sample_output);
  $test_input = stripslashes($test_input);
  $test_output = stripslashes($test_output);
  $hint = stripslashes($hint);
  $source = stripslashes($source);
  $spj = stripslashes($spj);
  $source = stripslashes($source);
}

$title = ($title);
$description = ($description);
$input = ($input);
$output = ($output);
$hint = ($hint);
//echo "->".$OJ_DATA."<-"; 
$pid = addproblem($title, $time_limit, $memory_limit, $description, $input, $output, $sample_input, $sample_output, $hint, $source, $spj, $OJ_DATA);
$coin = intval($_POST['coin'] ?? 1);
$sql = "UPDATE problem SET coin=? WHERE problem_id=?";
pdo_query($sql, $coin, $pid);
$basedir = "$OJ_DATA/$pid";
mkdir($basedir);
if(strlen($sample_output) && !strlen($sample_input)) $sample_input = "0";
if(strlen($sample_input)) mkdata($pid, "sample.in", $sample_input, $OJ_DATA);
if(strlen($sample_output)) mkdata($pid, "sample.out", $sample_output, $OJ_DATA);
if(strlen($test_output) && !strlen($test_input)) $test_input = "0";
if(strlen($test_input)) mkdata($pid,"test.in", $test_input, $OJ_DATA);
if(strlen($test_output)) mkdata($pid,"test.out", $test_output, $OJ_DATA);
if(isset($_POST['remote_oj'])){
	$remote_oj=$_POST['remote_oj'];
	$remote_id=intval($_POST['remote_id']);
	$sql="update problem set remote_oj=?,remote_id=? where problem_id=?";
	pdo_query($sql,$remote_oj,$remote_id,$pid);
	?>
<form method=POST action=problem_add_page_<?php echo $remote_oj?>.php>
<?php 
	if($remote_oj=="luogu"){
		$pre=mb_strpos($source,"P");
		$pre=mb_substr($source,0,$pre+1);
		$remote_id=intval(mb_substr($_POST['remote_id'],1));
		echo "remote id :$remote_id";
	
	}else{
		$pre=mb_strpos($source,"=");
		$pre=mb_substr($source,0,$pre+1);
	}
?>
<input name=url type=text size=100  class="input input-xxlarge" value="<?php echo $pre.(++$remote_id) ?>">
  <input type=submit>
</form>
<script>// $("form").submit();</script>

	<?php
}

$sql = "INSERT INTO `privilege` (`user_id`,`rightstr`) VALUES(?,?)";
pdo_query($sql, $_SESSION[$OJ_NAME.'_'.'user_id'], "p$pid");
$_SESSION[$OJ_NAME.'_'."p$pid"] = true;
  
echo "&nbsp;&nbsp;- <a href='javascript:phpfm($pid);'>$MSG_ADD $MSG_DATA</a>";
/*  */
?>

<script src='../template/bs3/jquery.min.js' ></script>
<script>
function phpfm(pid){
  //alert(pid);
  $.post("phpfm.php",{'frame':3,'pid':pid,'pass':''},function(data,status){
    if(status=="success"){
      document.location.href="phpfm.php?frame=3&pid="+pid;
    }
  });
}
window.setTimeout("phpfm(<?php echo $pid?>);",3000);
</script>

