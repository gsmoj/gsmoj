<?php
        
    require_once(dirname(__FILE__)."/db_info.inc.php");
    # Connect to memcache:
    global $memcache;
    if ($OJ_MEMCACHE){
	$memcache = new Memcache;
					@$memcache->connect($OJ_MEMSERVER,  $OJ_MEMPORT);
    }

    // 아래 두 함수는 Memcache 사용 여부를 먼저 확인한다.
    // 켜져 있으면 get/set을 수행하고, 꺼져 있으면 false를 반환해 DB 조회로 넘어가게 한다.
    // mysql_query_cache()에서 캐시 조회를 담당한다.
    function getCache($key) {
        global $memcache;
//	if ($memcache->get($key)) echo "true";
        return ($memcache) ? $memcache->get($key) : false;
    }

    // mysql_query_cache()에서 조회 결과를 캐시에 저장할 때 사용한다.
    function setCache($key, $object, $timeout = 60) {
        global $memcache;
        return ($memcache) ? $memcache->set($key,$object,MEMCACHE_COMPRESSED,$timeout) : false;
    }
    $OJ_APCU_OK = ( extension_loaded('apcu') && apcu_enabled() );
    /**
     * SELECT 계열 조회에 짧은 캐시를 붙이는 헬퍼.
     *
     * APCu가 가능하면 프로세스 로컬 캐시를 먼저 쓰고, 아니면 Memcache를 사용한다.
     * 캐시가 없으면 pdo_query()로 DB를 조회한 뒤 같은 키에 저장한다.
     */
    function mysql_query_cache($sql){
        global $OJ_NAME,$OJ_MEMCACHE,$OJ_APCU_OK;
    $linkIdentifier = false;
    $timeout = 5;
	// SQL과 바인딩 인자를 합쳐 캐시 키를 만든다. 같은 쿼리라도 인자가 다르면 다른 캐시로 취급한다.
    $num_args = func_num_args();
    $args = func_get_args();       // 전달된 전체 인자를 배열로 받는다.
    $args = array_slice($args,1,--$num_args);
    $key=md5($OJ_NAME.$_SERVER['HTTP_HOST']."mysql_query" . $sql.implode(" ",$args));
        if($OJ_APCU_OK){   // PHP 내장 APCu가 가능하면 APCu 캐시를 우선 사용한다.
            $apcu_success=false;
            $cache=apcu_fetch($key,$apcu_success);
            if($apcu_success){
                //echo "-APCU cache-";
                return $cache;
            }else{
	            $s_time=microtime(true);
	            $cache =pdo_query($sql,$args);
	            $u_time=microtime(true)-$s_time;
	            if($u_time>$timeout/5) $timeout=round($u_time+1)*5;
                apcu_store($key,$cache,$timeout);
            }
        }else if (!($cache = getCache($key))) {
            $cache = false;
			$s_time=microtime(true);
            $cache =pdo_query($sql,$args);
			$u_time=microtime(true)-$s_time; 
			if($u_time>$timeout/5) $timeout=round($u_time+1)*5;
        // Memcache 서버가 켜져 있으면 조회 결과를 저장한다. 꺼져 있으면 실패해도 페이지 흐름은 유지된다.
                if (!setCache($key, $cache, $timeout)) {
                    # If we get here, there isn’t a memcache daemon running or responding
                 if($OJ_MEMCACHE) echo "You can run these command to get faster speed:<br>sudo apt-get install memcached<br>sudo apt-get install php5-memcache<br>sudo apt-get install php-memcache";
                }

        }
        return $cache;
    }

