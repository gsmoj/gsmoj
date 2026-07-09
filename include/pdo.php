<?php
/**
 * HUSTOJ 전역 DB 헬퍼.
 *
 * 사용 예:
 *   pdo_query("select * from users where user_id=?", $user_id);
 *
 * SELECT는 fetchAll() 배열, INSERT는 lastInsertId(), 그 외 UPDATE/DELETE는 영향 받은 행 수를 반환한다.
 * 구형 코드가 전역 함수 하나로 DB를 쓰도록 설계되어 있어, 대부분의 페이지가 이 함수를 통해 쿼리한다.
 */
function pdo_query($sql){
    $query_start_time=microtime(true);
    $num_args = func_num_args();
    $args = func_get_args();       // 전달된 전체 인자를 배열로 받는다.
    $args = array_slice($args,1,--$num_args);
    if(isset($args[0])&&is_array($args[0])) $args=$args[0]; 
    global $DB_HOST,$DB_NAME,$DB_USER,$DB_PASS,$dbh,$OJ_TEMPLATE;
    try{
	    if(!$dbh||stripos($sql,"create") === 0||stripos($sql,"drop") === 0|| stripos($sql,"grant") === 0){
				
				
	    		if(stripos($sql,"create") === 0||stripos($sql,"drop") === 0|| stripos($sql,"grant") === 0){
					$dbh=new PDO("mysql:host=".$DB_HOST, $DB_USER, $DB_PASS,array(PDO::MYSQL_ATTR_INIT_COMMAND => "set names utf8mb4;"));
	//					echo "General SQL";
					$sql="use $DB_NAME; ".$sql;
				}else{
					$dbh=new PDO("mysql:host=".$DB_HOST.';dbname='.$DB_NAME, $DB_USER, $DB_PASS,array(PDO::ATTR_PERSISTENT=>true,PDO::MYSQL_ATTR_INIT_COMMAND => "set names utf8mb4"));
//					echo "$DB_NAME SQL";
				}
			
			
	    }
	    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	    $sth = $dbh->prepare($sql);
	    $sth->execute($args);
	    $result=array();
	    // 쿼리 종류에 따라 기존 HUSTOJ 호출부가 기대하는 반환 타입을 맞춘다.
	    if(stripos($sql,"select") === 0){
		$result=$sth->fetchAll();
	    }else if(stripos($sql,"insert") === 0){
		$result=$dbh->lastInsertId();
	    }else{
		$result=$sth->rowCount();
	    }
	    //print($sql);
	    $sth->closeCursor();
        $query_used_time=microtime(true)-$query_start_time;
        if($query_used_time>3) {
            global $logger;
            $logger->warn("slow SQL of [$query_used_time] sec : $sql \n
                    in page [".$_SERVER['REQUEST_URI']."]");
        }
	    return $result;
    }catch(PDOException $e){
//	    echo "<span class=red>".$e->getMessage()."</span>";    // SQL 오류를 직접 확인해야 할 때 임시로 켠다.
//	$view_errors="SQL:".$sql."\n".$e->getMessage();
//	echo htmlentities($view_errors."\n\n");
	GLOBAL $MSG_UPDATE_DATABASE,$MSG_HELP_UPDATE_DATABASE;
        GLOBAL $POP_UPED,$OJ_NAME,$_SESSION;
        if(php_sapi_name ()=='cli'||(!$POP_UPED&&isset($_SESSION[$OJ_NAME.'_administrator']))){
                echo " $MSG_HELP_UPDATE_DATABASE <a href='/admin/update_db.php'>$MSG_UPDATE_DATABASE</a>。";
		$view_errors="SQL:".$sql."\n".$e->getMessage();
		echo htmlentities($view_errors."\n\n");
                $POP_UPED=true;
        }

	if(stripos($sql,"create") === 0||stripos($sql,"drop") === 0) echo "continue\n";
	//else exit(0);
	return -1;
    }
}
