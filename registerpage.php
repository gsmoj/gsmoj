<?php
//////////////////////////// 공통 헤더 설정 부
$cache_time = 10;          // 페이지 캐시 유지 시간을 10초로 설정
$OJ_CACHE_SHARE = false;   // 캐시 공유 여부를 비활성화

// 주요 필수 링크 파일들을 불러옴 (한 번만 로드)
require_once('./include/cache_start.php'); // 캐시 제어 시작 (출력 버퍼링 등)
require_once('./include/db_info.inc.php'); // DB 연결 정보 및 사이트 전역 설정 로드
require_once('./include/memcache.php');   // Memcached(메모리 캐시) 기능 로드
require_once('./include/setlang.php');    // 사용자의 언어(다국어) 설정 로드
require_once('./include/bbcode.php');     // 게시판 형태의 BBCode 파서 로드

// 관리자가 회원가입을 비활성화(!$OJ_REGISTER)해두었다면, 즉시 실행을 종료(blank page)
if (isset($OJ_REGISTER) && !$OJ_REGISTER) exit(0);

// 브라우저 탭 등에 표시될 웹 페이지의 제목을 지정
$view_title = "Register a new account"; // [수정] Registe 오타를 Register로 수정

/////////////////////////// 메인 로직 처리부 (백엔드 이메일 검증 추가)

// 사용자가 폼을 제출(POST 방식)했을 때 서버 측에서 이메일을 최종 검사합니다.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    
    // 이메일이 @gsm.hs.kr로 끝나지 않는 경우 (뒤에서 10글자가 일치하는지 비교)
    if (substr($email, -10) !== "@gsm.hs.kr") {
        echo "<script>
            alert('gsm.hs.kr 도메인의 학교 이메일만 가입 가능합니다.');
            history.go(-1);
        </script>";
        exit(0); // 조건에 맞지 않으면 처리를 중단하고 종료
    }
}

///////////////////////// 템플릿(HTML 화면) 출력
// 현재 설정된 테마($OJ_TEMPLATE) 폴더 내의 실제 회원가입 폼 화면(HTML)을 불러옴
require("template/" . $OJ_TEMPLATE . "/registerpage.php");

///////////////////////// 공통 푸터 및 캐시 마무리
// 캐시 종료 파일이 존재한다면 실행하여 위에서 설정한 10초간의 캐시 생성을 완료함
if (file_exists('./include/cache_end.php'))
    require_once('./include/cache_end.php');
?>