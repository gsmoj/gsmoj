<?php
// 모든 사용자 페이지가 공통으로 거치는 초기화 파일이다.
// 보안 헤더, 세션, DB 연결, 언어, 템플릿, 접속 IP, 로깅 기본값을 여기서 준비한다.

// XSS 보호 헤더를 켜고, 같은 출처 리소스 기준으로 브라우저 보안 정책을 제한한다.
header("X-XSS-Protection: 1; mode=block; sameorigin");
header("X-Download-Options: noopen");
header("Referrer-Policy: same-origin");
//header("X-Permitted-Cross-Domain-Policies: none");
$domain=basename($_SERVER["HTTP_HOST"]??"");
session_set_cookie_params([
          'lifetime' => 0,
            'path' => '/',
//              'domain' => $domain ,
//                'secure' => true,      // HTTPS 환경에서만 쿠키 전송
                  'httponly' => true,    // JavaScript에서 세션 쿠키 접근 방지
                    'samesite' => 'Strict'
]);
@session_start();
require_once(dirname(__FILE__)."/pdo.php");
require_once(dirname(__FILE__)."/memcache.php");

// 시간대나 URL 파라미터에 따라 야간 모드를 자동 적용할 때 쓰는 예시 코드.
//if(date('H')<5||date('H')>21||isset($_GET['dark'])) $OJ_CSS="dark.css";

// tp 파라미터나 쿠키로 템플릿을 임시 전환할 때 쓰던 레거시 코드.
/*
if(in_array($_GET['tp'],$OJ_TP)){
    $OJ_TEMPLATE=$_GET['tp'];
    setcookie("tp", $_GET['tp'], time()+3600);
}else if (in_array($_COOKIE['tp'],$OJ_TP)){
    $OJ_TEMPLATE=$_COOKIE['tp'];
}
*/

// 세션, 쿠키, URL 파라미터, 브라우저 언어 순서로 화면 언어를 결정한다.
if (isset($_SESSION[$OJ_NAME . '_' . 'OJ_LANG'])) {
	$OJ_LANG=$_SESSION[$OJ_NAME . '_' . 'OJ_LANG'];
} else if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], array("cn", "ug", "en", 'fa', 'ko', 'th'))) {
	$OJ_LANG=$_COOKIE['lang'];
} else if (isset($_GET['lang']) && in_array($_GET['lang'], array("cn", "ug", "en", 'fa', 'ko', 'th'))) {
	$OJ_LANG=$_GET['lang'];
} else if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) &&   $OJ_LANG != "cn") {
    $userLanguages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    foreach ($userLanguages as $userLang) {
        $langParts = explode(';', $userLang);
        $lang = strtolower(substr($langParts[0], 0, 2));
	if (in_array($lang, array("zh", "ug", "en", 'fa', 'ko', 'th'))) {
            $OJ_LANG = $lang;
	    if($lang=="zh") $OJ_LANG="cn";
            break;
        }
    }
}
require(dirname(__FILE__)."/../lang/$OJ_LANG.php");

if($OJ_SaaS_ENABLE){
	$DOMAIN="my.hustoj.com";   // SaaS를 사용할 경우 실제 서비스의 메인 도메인으로 바꿔야 한다.
	if(isset($_SERVER["HTTP_HOST"])) $OJ_SaaS_CONF=realpath(dirname(__FILE__)."/..")."/SaaS/".basename($_SERVER["HTTP_HOST"]).".php";
	if(isset($OJ_SaaS_CONF) && file_exists($OJ_SaaS_CONF)){
		require_once($OJ_SaaS_CONF);
	}else{
		echo "You need \$_SERVER[\"HTTP_HOST\"] to enable \$OJ_SaaS_ENABLE ";
		//exit();
	}
	if($domain==$DOMAIN)  $MSG_REG_INFO.="/MyOJ 초기화";
}else{
	$DOMAIN=$domain;
}

$UA="";
if(isset($_SERVER["HTTP_USER_AGENT"])){  // w3m 또는 구형 IE는 syzoj 대신 bs3 템플릿으로 우회한다.
    $UA=$_SERVER["HTTP_USER_AGENT"];
    if(strpos($UA,"w3m") !== false || strpos($UA,"MSIE") !== false)
        $OJ_TEMPLATE="bs3";
}

$ip = '';
if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
} else if (!empty($_SERVER['REMOTE_ADDR'])) {
    $ip = $_SERVER['REMOTE_ADDR'];
}
if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty(trim($_SERVER['HTTP_X_FORWARDED_FOR']))) {
    $forwardedIps = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $ip = trim($forwardedIps[0]);
} else if (isset($_SERVER['HTTP_X_REAL_IP']) && !empty(trim($_SERVER['HTTP_X_REAL_IP']))) {
    $ip = trim($_SERVER['HTTP_X_REAL_IP']);
}
// 프록시 헤더에서 얻은 IP라도 최종적으로 올바른 IP 형식인지 검증한다.
$ip = filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';

if(isset($OJ_LIP_URL) && isset($_COOKIE['lip']) ){
	 $ip=long2ip(intval($_COOKIE['lip']));
}
if(isset($_SESSION[$OJ_NAME.'_user_id'])&&isset($OJ_LIMIT_TO_1_IP)&& $OJ_LIMIT_TO_1_IP){
        $sql="select ip from loginlog where user_id=? order by time desc";
        $rows=pdo_query($sql,$_SESSION[$OJ_NAME.'_user_id'] );
        $lastip=$rows[0][0];
        if($ip!=$lastip){
                unset($_SESSION[$OJ_NAME.'_'.'user_id']);
                setcookie($OJ_NAME."_user","");
                setcookie($OJ_NAME."_check","");
                session_destroy();
                $view_errors="Logged in another ip address:$lastip, auto logout!";
                require("template/$OJ_TEMPLATE/error.php");
                exit(0);
        }

}



$OJ_LOG_FILE="/var/log/hustoj/{$OJ_NAME}.log";
require_once(dirname(__FILE__) . "/logger.php");

$logger=new Logger(isset($_SESSION[$OJ_NAME . '_' . 'user_id'])?$_SESSION[$OJ_NAME . '_' . 'user_id']:"guest", 
					$OJ_LOG_FILE, 
					$OJ_LOG_DATETIME_FORMAT, 
					$OJ_LOG_ENABLED, 
					$OJ_LOG_PID_ENABLED,
					$OJ_LOG_USER_ENABLED,
					$OJ_LOG_URL_ENABLED,
					$OJ_LOG_URL_HOST_ENABLED,
					$OJ_LOG_URL_PARAM_ENABLED,
					$OJ_LOG_TRACE_ENABLED);
$logger->info();
// JudgeHub 기반 SaaS 운영을 쉽게 켜기 위한 HUSTOJ 호환 옵션 묶음.
// 숫자가 높을수록 더 많은 편의 기능을 자동으로 활성화한다.
switch($OJ_FRIENDLY_LEVEL) {
	case 9:
	   $OJ_GUEST=true;
	case 8:
	   $OJ_DOWNLOAD=true;
	case 7:
	   $OJ_BBS="discuss3";
	   $OJ_FREE_PRACTICE=true;
	case 6:
	   $OJ_LONG_LOGIN=true; 
	case 5:
	   $OJ_TEST_RUN=true; 
	case 4:
	   $OJ_MAIL=true;
	   $OJ_AUTO_SHARE=true;
	case 3:
	   $OJ_SHOW_DIFF=true; 
	   $OJ_VCODE=false;
	case 2:
	   $OJ_LANG="cn";
	case 1:
	   date_default_timezone_set("Asia/Seoul");
	   pdo_query("SET time_zone ='+9:00'");
	case 0:
	   break;
	case -1:
	   $OJ_NEED_LOGIN=true;
	   $OJ_REGISTER=false;
	   
}
if(!isset($OJ_SUBMIT_COOLDOWN_TIME)) $OJ_SUBMIT_COOLDOWN_TIME=3;
// 시험 모드나 현장 대회가 켜져 있으면 자유 연습을 자동으로 끈다.
if(isset($OJ_ON_SITE_CONTEST_ID) || isset($OJ_EXAM_CONTEST_ID)) $OJ_FREE_PRACTICE=false;

// $OJ_BG="/image/bg".date('H').".jpg";  // 매 정시마다 배경을 바꿀 때 사용한다. image/bg0.jpg ~ bg23.jpg가 필요하다.
// if (time() < strtotime('2025-3-31')) $OJ_BG="http://cdn.hustoj.com/upload/bg/nz0".(date('i')%9+1).".png";  // 기간 한정 배경을 분 단위로 바꾸는 예시.

if (!empty($OJ_CDN_URL)) {
        header('Access-Control-Allow-Origin:'.$OJ_CDN_URL);
}
if(isset($_SESSION[$OJ_NAME."_user_id"])){
	 $coins=pdo_query("select coin_earned,coin_bonus,coin_spent from users where user_id=?",$_SESSION[$OJ_NAME."_user_id"])[0];
	 $coin=$coins['coin_earned']+$coins['coin_bonus']-$coins['coin_spent'];
}else{
	 $coin=0;	
}
