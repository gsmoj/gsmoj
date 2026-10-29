# 관리자 사이드바 메뉴 UI 개선

## 목표

관리자 좌측 메뉴(`admin/menu2.php`)에서 **여러 그룹을 동시에 펼칠 수 없는 문제**를 해결하고, 사용자 화면 헤더(`template/syzoj/header.php`)의 색 기준에 맞춰 사이드바를 정리한다.

원인은 Bootstrap 3의 `data-toggle="dropdown"`이다. 문서 클릭 시 `clearMenus()`가 열려 있는 다른 드롭다운을 모두 닫으므로, 마크업을 그대로 두고 CSS만으로는 동시 열림을 만들 수 없다. 한편 `admin/admin.css:499`는 이미 `.dropdown-menu`를 `position:static; width:100%`로 바꿔 두어, 화면상으로는 드롭다운이 아니라 인라인 아코디언처럼 보인다. 즉 겉모습은 아코디언인데 동작만 드롭다운 규칙에 묶여 있는 상태다.

### 색 기준 (사용자 헤더에서 추출)

`template/syzoj/header.php:117-197`의 인라인 `<style>` 값을 그대로 기준으로 삼는다.

| 용도 | 값 |
|------|-----|
| 헤더 배경 | `linear-gradient(135deg, #1f2937, #111827)` |
| 강조 / active | `#2563eb` (hover `#1d4ed8`) |
| 비활성 텍스트 | `#9ca3af` |
| 활성 텍스트 | `#ffffff` |
| 드롭다운 내부 텍스트 | `#4b5563` / hover 배경 `#f3f4f6` |

`admin/admin.css:362-370`에 이미 같은 계열 토큰(`--admin-primary:#2563eb`, 사이드바 `#111827`/`#1f2937`)이 정의되어 있으므로 새 팔레트를 만들지 않고 이를 재사용한다.

## 범위 (포함 / 제외)

### 포함

- `admin/menu2.php` 좌측 메뉴 마크업: dropdown → collapse 교체
- 하단 낱개 항목(외부 링크 7개, 회색 고급 도구 3개)을 접힘 그룹 2개로 편입
- 고급 도구 항목의 한국어 병기 및 위험 항목 색 구분
- 클릭한 항목에 active 표시 유지
- `admin/admin.css`의 사이드바 스타일 블록을 클래스 셀렉터로 교체하고 헤더 색 기준에 맞춤
- `lang/ko.php`에 새 라벨 변수 추가

### 제외

- **프레임셋 구조 변경 없음.** `admin/index.php`의 `<frameset cols="260,*">`와 `target="main"` 흐름을 그대로 유지한다.
- **기능 삭제·이동 없음.** 모든 링크와 권한 분기(`isset($_SESSION[...])`)는 한 줄도 바꾸지 않는다.
- **그룹 순서 재배치 없음.** 공지 → 사용자 → 문제 → 대회 → 시스템 순서를 유지한다.
- `admin/menu.php`와 `admin/admin.css:8-70`의 `#sidebar` 블록. 현재 배포(`OJ_TEMPLATE=syzoj`)에서는 로드되지 않으나 `OJ_TEMPLATE=sidebar`로 바꾸면 살아나므로 죽은 코드로 단정하지 않고 손대지 않는다.
- 관리자 본문 화면(목록·폼)의 스타일. 이번 작업은 메뉴 프레임에 한정한다.
- 사용자 화면(`template/syzoj/`), 다른 템플릿, DB 스키마, 권한 체크, CSRF, 채점 로직.

## 확정된 결정

| 결정 | 선택 | 근거 |
|------|------|------|
| 동시 열림 구현 | Bootstrap collapse로 교체 | `template/bs3/bootstrap.min.js`가 v3.4.1이고 collapse 플러그인·data-api를 포함(확인함). `data-parent`를 주지 않으면 그룹마다 독립 동작하므로 직접 작성할 JS가 없다. |
| 진입 시 초기 상태 | 전부 닫힘 | 현재와 동일하게 두어 변경 폭을 최소화한다. 메뉴 프레임은 `/admin/` 진입 시에만 다시 로드되므로 이 값이 매 접속의 기본값이 된다. |
| 메뉴 구성 | 하단 낱개 항목을 "고급 도구", "외부 링크" 두 접힘 그룹으로 편입 | `admin/AGENTS.md`의 "핵심 메뉴 우선 노출", "레거시·외부 도구는 별도 그룹 분리" 규칙. 삭제 없이 전부 유지한다. |
| 새 라벨 문구 | `lang/ko.php`에 변수 추가 + `isset()` 폴백 | 루트 `AGENTS.md`의 "하드코딩보다 `lang/ko.php` 변수 우선". 폴백은 `admin/admin-header.php:31`이 이미 쓰는 방식이라 다른 언어 파일에서도 빈 라벨이 나오지 않는다. |
| CSS 연결 방식 | `body`에 `admin-menu-frame` 클래스 부여 후 셀렉터 교체 | 현재 `admin/admin.css:486-503`은 `body[style*="text-align:center"]`로 인라인 style 문자열에 의존한다. 마크업을 고치다 인라인 style을 건드리면 사이드바 스타일이 통째로 날아간다. |
| 고급 도구 표기 | 한국어 병기 + 위험 항목 색 구분 | 현재 `<font color="eeeeee">`로 사실상 안 보이고, `ReOrderProblem`의 "Danger" 경고는 마우스오버 title에만 있다. 영어 이름을 괄호로 남겨 기존 관리자의 탐색을 깨지 않는다. |
| active 표시 | 클릭한 항목에 표시 유지 (jQuery 3~4줄) | 프레임셋이라 메뉴 프레임은 main 프레임의 현재 페이지를 알 수 없다. 메뉴 프레임이 다시 로드되지 않으므로 클릭 시점에 클래스를 붙이면 표시가 유지된다. |
| 사용 안 하는 코드 | `menu.php`·`#sidebar` 블록은 건드리지 않고 열린 질문으로 기록 | `AGENTS.md`의 변경 범위 제한·기능 삭제 금지 원칙. |
| 배포 경로 | 작업 브랜치 + PR, 병합 후 서버 확인 | `main` push가 곧 운영 반영이다. `git-commit` 스킬 절차를 따른다. |

## 변경 대상 파일

| 파일 | 변경 내용 |
|------|-----------|
| `admin/menu2.php` | `body` 태그에 클래스 추가(`:22`), 그룹 5개의 `dropdown` → `collapse` 교체(`:33-127`), 하단 낱개 항목을 그룹 2개로 편입(`:131-153`), active 표시 스크립트 3~4줄 추가 |
| `admin/admin.css` | `:486-503` 사이드바 블록의 셀렉터를 `.admin-menu-frame` 기반으로 교체, 그룹 버튼·목록·active·위험 항목 스타일을 헤더 색 기준으로 정리 |
| `lang/ko.php` | 라벨 변수 5개 추가 (UTF-8 + CRLF, 기존 탭 들여쓰기 유지) |

### 새 변수

```php
$MSG_ADVANCED_TOOLS="고급 도구";
$MSG_EXTERNAL_LINKS="외부 링크";
$MSG_SOLUTION_STATISTICS="채점 통계 리포트";
$MSG_PROBLEM_COPY="문제 복사";
$MSG_PROBLEM_REORDER="문제 번호 재정렬";
```

용어는 루트 `AGENTS.md`의 공통 용어(문제 / 채점 현황 / 관리자)를 따른다.

### 그룹 마크업 형태

```php
<div class="admin-menu-group">
  <button type="button" class="btn btn-default btn-sm admin-menu-toggle collapsed"
          data-toggle="collapse" data-target="#admin-grp-user"
          aria-expanded="false" aria-controls="admin-grp-user">
    <?php echo $MSG_USER."-".$MSG_ADMIN ?> <span class="caret"></span>
  </button>
  <ul id="admin-grp-user" class="admin-menu-list collapse" role="menu">
    <!-- 기존 li와 권한 분기를 그대로 옮긴다 -->
  </ul>
</div>
```

그룹 id: `admin-grp-news`, `admin-grp-user`, `admin-grp-problem`, `admin-grp-contest`, `admin-grp-system`, `admin-grp-tools`, `admin-grp-links`.

`data-parent`를 주지 않는 것이 이 작업의 핵심이다. 주는 순간 아코디언이 되어 한 번에 하나만 열린다.

### 색 적용

| 대상 | 값 |
|------|-----|
| 메뉴 프레임 배경 | `linear-gradient(180deg, #1f2937, #111827)` (헤더 그라데이션과 같은 두 색, 세로 방향) |
| 그룹 버튼 | 배경 `#1f2937`, 텍스트 `#e5e7eb`, 테두리 `#374151` |
| 펼친 목록 배경 | `#0f172a` |
| 목록 링크 | `#cbd5e1`, hover `#fff` / 배경 `#334155` |
| active 항목 | 텍스트 `#fff`, `box-shadow: inset 3px 0 0 #2563eb` (헤더의 3px `#2563eb` active 표시와 대응) |
| GSMOJ 브랜드 버튼 | 기존 `#2563eb` 유지 (`admin.css:493`) |
| 위험 항목(문제 번호 재정렬) | 텍스트 `#fca5a5`, hover 배경 `#7f1d1d` |
| caret | `.admin-menu-toggle:not(.collapsed) .caret { transform: rotate(180deg) }` — Bootstrap 3이 트리거에 `collapsed` 클래스를 붙였다 뗀다 |

## 구현 순서

1. `lang/ko.php`에 변수 5개 추가 → `php -l lang/ko.php`
2. `admin/admin.css`에 `.admin-menu-frame` 기반 블록을 새로 작성. 이 시점에는 기존 `body[style*=...]` 블록도 함께 남겨 두어 화면이 깨지지 않게 한다.
3. `admin/menu2.php` `body` 태그에 `class="admin-menu-frame"` 추가. 인라인 `style`은 그대로 둔다.
4. 그룹 5개를 collapse 마크업으로 교체. 권한 분기 PHP 조건과 `target` 속성은 복사만 하고 수정하지 않는다.
5. 하단 낱개 항목을 "고급 도구", "외부 링크" 그룹으로 편입. `<font color="eeeeee">` 제거, 한국어 병기 적용.
6. active 표시 스크립트 추가.
7. `admin/admin.css`에서 이제 쓰이지 않는 `body[style*=...]` 블록 제거, `.dropdown-toggle`/`.dropdown-menu` 규칙을 `.admin-menu-toggle`/`.admin-menu-list`로 정리.
8. `php -l admin/menu2.php`, `git diff --stat`으로 의도치 않은 변경 확인.

2번과 7번을 나누는 이유는 중간 단계에서도 화면이 정상으로 보이게 하여, 문제가 생겼을 때 어느 단계에서 깨졌는지 좁힐 수 있게 하기 위함이다.

## 검증 계획

### 로컬

1. `php -l admin/menu2.php`
2. `php -l lang/ko.php`
3. `git diff --stat` — 변경 파일이 위 3개인지, `.idea/` 등이 섞이지 않았는지 확인

### 서버 (`/admin/`) — 순서대로

1. 메뉴 프레임이 뜨고 그룹 7개가 **모두 닫힌** 상태로 시작하는지
2. **그룹 두 개 이상을 동시에 펼칠 수 있는지** (이번 작업의 본질)
3. 펼친 그룹을 다시 눌러 닫히는지, caret 방향이 상태와 맞는지
4. 목록 링크 클릭 시 main 프레임에 열리는지 (`target="main"`)
5. GSMOJ / OJ 보기는 `target="_top"`으로 전체 이동하는지
6. 외부 링크 그룹의 항목이 새 탭(`target="_blank"`)으로 열리는지
7. 클릭한 항목에 active 표시가 남고, 다른 항목을 누르면 옮겨 가는지
8. 260px 폭에서 라벨이 넘치거나 잘리지 않는지, 전부 펼쳤을 때 메뉴 프레임 안에서 스크롤되는지
9. 권한별 노출 확인 — 관리자 / `contest_creator` / `problem_editor` / `password_setter` 계정으로 각각 접속해 보이는 항목이 변경 전과 같은지
10. 사용자 화면 `/`의 헤더가 그대로인지 (관리자 CSS가 새어 나가지 않았는지)

9번은 권한 분기를 통째로 옮기는 작업이라 반드시 확인해야 한다. 최소한 관리자와 `problem_editor` 두 계정은 확인한다.

## 수동 절차 및 위험

- **DB 스키마, 권한 체크, 세션, CSRF, 채점 로직 변경 없음.** 수동 마이그레이션 절차도 없다.
- **권한 분기 이식 위험.** 그룹 5개를 옮기면서 `<?php if (isset($_SESSION[...])){?>` 블록의 여닫는 위치가 어긋나면 특정 권한에서 메뉴가 사라지거나 반대로 노출된다. 조건문은 재작성하지 않고 그대로 옮기며, 검증 9번으로 확인한다.
- **CSS 셀렉터 교체 여파.** `body[style*="text-align:center"]`는 `menu.php`의 body에도 걸린다. 셀렉터를 바꾸면 `OJ_TEMPLATE=sidebar` 환경에서 그 규칙이 빠지지만, `menu.php`는 자체 `#sidebar` 스타일을 갖고 있고 현재 배포에서는 로드되지 않으므로 영향이 없다.
- **브라우저 캐시.** `admin.css`는 상대 경로로 로드되어 CDN을 타지 않지만, 반영 후 처음에는 강제 새로고침(Ctrl+F5)이 필요할 수 있다.
- **배포.** `main` push가 운영에 그대로 반영된다. 작업 브랜치에서 커밋하고 PR로 검토한 뒤 병합한다.
- **되돌리기.** 변경이 3개 파일에 한정되므로 문제 발생 시 해당 커밋만 revert하면 원상 복구된다.

## 열린 질문

1. `admin/menu.php`와 `admin/admin.css:8-70`의 `#sidebar` 블록 정리 — 현재 배포에서 로드되지 않는 중복 구현이다. 별도 작업으로 뺄지, 계속 둘지.
2. `admin/index.php`의 `frameset cols="260,*"` 고정 폭 유지 여부. 그룹이 7개로 늘어 전부 펼치면 세로가 길어진다. 이번에는 손대지 않았다.
3. 그룹 열림 상태를 `localStorage`로 기억할지. 이번에는 제외했고, 쓰다 보면 매번 다시 펼치는 게 번거로울 수 있다.
4. 시스템 그룹의 위험 기능(재채점, DB 업데이트, 백업)에 확인 흐름을 붙일지. `admin/AGENTS.md`가 요구하지만 각 페이지 자체를 고쳐야 하므로 이번 범위 밖이다.
5. `$MSG_LIST="리스트"` 등 기존 용어를 공통 용어("목록")로 통일할지. 관리자 메뉴 라벨 전반에 영향을 주므로 별도 작업이 맞다.
