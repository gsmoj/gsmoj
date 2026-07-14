# Syzoj 사용자 화면 지침

이 디렉터리는 운영 중인 사용자 템플릿이다. 사용자 화면 변경은 `template/syzoj/`에만 적용하며, `template/bs3/`, `template/mdui/`, `template/bshark/`, `template/sidebar/`, `template/sweet/`는 요청이 없는 한 수정하지 않는다.

## 주요 파일

- `header.php`, `index.php`
- `css/style.css`, `css/white.css`, `css/slide.css`, `css/semantic.min.css`
- `js.php`, `css.php`, `faqs.php`

## UI 및 번역 규칙

- 수정 전 `header.php`, `index.php`, 관련 CSS를 먼저 확인한다.
- 새 UI 컴포넌트를 만들기보다 현재 디자인의 간격, 색상, 콘텐츠 폭, 카드·테이블 스타일을 따른다.
- 첫 화면, 헤더 높이, 메뉴 배치, 배경과 카드 스타일은 syzoj 기준 디자인을 유지한다.
- 버튼 색상, 테이블 간격, 폼 라벨, 제목 크기, 경고 메시지 스타일을 화면 간 일관되게 맞춘다.
- 문제 목록·상세, 제출, 채점 현황, 대회, 순위 페이지의 가독성을 우선한다.
- 모바일에서 메뉴, 테이블, 긴 코드 및 문제 제목이 넘치지 않도록 확인한다.
- 직접 박힌 중국어·영어 문구와 `faqs.php`의 문구를 번역할 때는 루트 `AGENTS.md`의 공통 용어를 따른다.
- 번역 후 PHP 따옴표와 세미콜론을 확인한다.

## 수동 확인 페이지

- `/`
- `/problemset.php`
- `/problem.php?id=1000`
- `/submitpage.php?id=1000`
- `/status.php`
- `/ranklist.php`
- `/contest.php`
