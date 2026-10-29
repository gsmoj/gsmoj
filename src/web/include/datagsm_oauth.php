<?php
// DataGSM OAuth(PKCE) 로그인에 쓰는 함수 모음이다.
// 설정값 로드, PKCE·state 생성, 토큰 교환, userinfo 조회를 맡는다. 토큰은 저장하지 않는다.
// 규격: https://docs.datagsm.kr/oauth

define('DATAGSM_AUTHORIZE_URL', 'https://oauth.authorization.datagsm.kr/v1/oauth/authorize');
define('DATAGSM_TOKEN_URL', 'https://oauth.authorization.datagsm.kr/v1/oauth/token');
define('DATAGSM_USERINFO_URL', 'https://oauth.resource.datagsm.kr/userinfo');

// 같은 이름의 환경 변수가 있으면 db_info.inc.php 값보다 우선한다.
function datagsm_config($name) {
    $env = getenv($name);
    if ($env !== false && trim($env) !== '') return trim($env);
    if (isset($GLOBALS[$name])) return trim(strval($GLOBALS[$name]));
    return '';
}

// 클라이언트 ID가 있고 curl 확장이 있을 때만 DataGSM 로그인을 켠다.
function datagsm_enabled() {
    return datagsm_config('OJ_DATAGSM_CLIENT_ID') !== '' && function_exists('curl_init');
}

// 설정이 비어 있으면 현재 호스트 기준 <scheme>://<host>/login_datagsm.php 를 쓴다.
function datagsm_redirect_uri() {
    $uri = datagsm_config('OJ_DATAGSM_REDIRECT_URI');
    if ($uri !== '') return $uri;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    return ($https ? 'https' : 'http') . '://' . $host . '/login_datagsm.php';
}

function datagsm_base64url($bytes) {
    return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
}

// PKCE code_verifier(43자)와 S256 code_challenge를 만든다.
function datagsm_new_pkce() {
    $verifier = datagsm_base64url(random_bytes(32));
    $challenge = datagsm_base64url(hash('sha256', $verifier, true));
    return array($verifier, $challenge);
}

function datagsm_new_state() {
    return datagsm_base64url(random_bytes(32));
}

// scope는 넘기지 않는다. 생략하면 클라이언트에 등록된 scope 전체가 부여된다.
function datagsm_authorize_url($state, $challenge) {
    return DATAGSM_AUTHORIZE_URL . '?' . http_build_query(array(
        'response_type' => 'code',
        'client_id' => datagsm_config('OJ_DATAGSM_CLIENT_ID'),
        'redirect_uri' => datagsm_redirect_uri(),
        'state' => $state,
        'code_challenge' => $challenge,
        'code_challenge_method' => 'S256',
    ));
}

// HTTP 요청을 보내고 array(상태 코드, JSON 배열)를 돌려준다. 연결 실패·타임아웃이면 false.
function datagsm_request($url, $headers, $body = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $response = curl_exec($ch);
    $status = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
    curl_close($ch);
    if ($response === false || $status === 0) return false;
    $data = json_decode($response, true);
    return array($status, is_array($data) ? $data : array());
}

// Authorization Code를 access_token으로 바꾼다. 실패하면 false.
function datagsm_exchange_code($code, $verifier) {
    $body = json_encode(array(
        'grant_type' => 'authorization_code',
        'code' => $code,
        'client_id' => datagsm_config('OJ_DATAGSM_CLIENT_ID'),
        'redirect_uri' => datagsm_redirect_uri(),
        'code_verifier' => $verifier,
    ));
    $result = datagsm_request(DATAGSM_TOKEN_URL, array('Content-Type: application/json', 'Accept: application/json'), $body);
    if ($result === false || $result[0] !== 200 || empty($result[1]['access_token'])) return false;
    return strval($result[1]['access_token']);
}

// userinfo를 조회해 array(상태 코드, 사용자 정보)를 돌려준다. 연결 실패면 false.
function datagsm_fetch_userinfo($access_token) {
    return datagsm_request(DATAGSM_USERINFO_URL, array('Authorization: Bearer ' . $access_token, 'Accept: application/json'));
}

// 학번(studentNumber) 체계의 학년+반 두 자리(예: 2학년 1반 → 21). 졸업생처럼 값이 없으면 ''.
function datagsm_group_name($student) {
    if (!is_array($student) || !isset($student['grade']) || !isset($student['classNum'])) return '';
    return intval($student['grade']) . intval($student['classNum']);
}
?>
