# GSMOJ 작업 지침

## 프로젝트 목적

이 저장소는 HUSTOJ 기반 온라인 저지 웹 프로젝트를 학교 운영에 맞는 GSMOJ로 개선하는 프로젝트다.

우선순위는 한국어 번역 품질, 사용자·관리자 UI 통일, 관리자 기능 정리다. 세부 지침은 작업 파일과 가장 가까운 디렉터리의 `AGENTS.md`를 함께 따른다.

## 저장소 구조

저장소 루트는 서버의 채점기 홈 디렉터리(`/home/judge` → `/home/ubuntu/judge`)와 같다.

- `src/web/`: 웹 루트(nginx root). 이 문서와 하위 `AGENTS.md`에서 말하는 `admin/`, `lang/`, `include/`, `template/syzoj/` 등의 경로는 모두 `src/web/` 기준이다.
- `src/core/`: 채점기(`judge_client`, `judged`, `sim`) 소스
- `src/install/`: 설치·백업·유지보수 스크립트
- `src/docker/`: 채점용 Docker 이미지 설정
- `etc/`: 채점기 설정. 실제 `judge.conf`는 비밀값이 있어 올리지 않으며, `judge.conf.example`을 참고한다.
- 채점 데이터(`data/`), 실행 디렉터리(`run*/`), 로그, 샌드박스 시스템 파일(`bin/`, `lib/`, `lib64/`)은 `.gitignore`로 제외한다. 공개 저장소이므로 이 항목들을 커밋하지 않는다.

## 공통 작업 원칙

- HUSTOJ의 기존 PHP 구조를 존중하며, 큰 프레임워크 교체나 전면 재작성은 하지 않는다.
- 변경 범위는 요청된 화면 또는 기능에 필요한 파일로 제한한다.
- 공통 문구는 하드코딩보다 `lang/ko.php`의 메시지 변수를 우선 사용한다.
- 보안·채점 로직, 데이터베이스 쿼리, 권한 체크는 UI 정리 중 임의로 단순화하지 않는다.
- 인코딩이 깨져 보이는 파일은 수정 전 실제 저장 인코딩과 화면 출력 방식을 확인한다.
- PHP 파일은 기존 스타일을 따라 최소 변경한다. SQL은 기존 `pdo_query`와 파라미터 바인딩 방식을 따른다.
- 세션·권한·CSRF 관련 파일은 변경 전 호출 흐름을 확인한다.
- 외부 라이브러리 파일은 직접 수정하지 않는다. 예: `include/jquery-latest.js`, `include/PHPMailer.php`, `kindeditor/`, `ace/`

## 공통 한국어 용어

- Problem: 문제
- Status: 채점 현황
- Ranklist: 순위
- Contest: 대회
- Submit: 제출
- Accepted: 정답
- Wrong Answer: 오답
- Compile Error: 컴파일 오류
- Runtime Error: 런타임 오류
- Time Limit Exceeded: 시간 초과
- Memory Limit Exceeded: 메모리 초과
- Administrator/Admin: 관리자

같은 의미의 메뉴와 버튼은 프로젝트 전체에서 같은 한국어를 사용한다. 학교 사용자를 대상으로 하므로 과하게 기술적인 표현보다 명확한 표현을 우선한다.

## 스킬

반복 작업은 스킬 문서를 먼저 읽고 그 절차를 따른다. Claude Code는 `/<스킬명>`으로 호출하고, Codex는
`.agents/skills/<스킬명>/SKILL.md`를 직접 읽는다.

| 스킬 | 용도 | Claude | Codex |
|------|------|--------|-------|
| `git-commit` | 변경을 논리적 단위로 나눠 컨벤션에 맞게 커밋한다 | `.claude/skills/git-commit/` | `.agents/skills/git-commit/` |
| `write-pr` | 커밋 기준으로 PR 제목·본문·라벨을 만들고 PR을 연다 | `.claude/skills/write-pr/` | `.agents/skills/write-pr/` |
| `convention-review` | 변경 파일을 체크리스트로 검토하고 ✓/⚠/✗ 리포트를 낸다 | `.claude/skills/convention-review/` | `.agents/skills/convention-review/` |
| `planning` | 구조화된 인터뷰로 요구사항을 정리하고 구현 스펙을 쓴다 | `.claude/skills/planning/` | `.agents/skills/planning/` |

- 두 트리는 같은 내용이다. 스킬을 수정하면 양쪽을 함께 바꾸고 `diff -r`로 확인한다.
- 스킬 안의 지침·레퍼런스 문서는 영어로 쓰고, 산출물(커밋 메시지, PR 본문, 리뷰 근거)은 한국어로 쓴다.

## Git 및 검증

- 작업 전후 `git -c safe.directory=C:/Users/master/IdeaProjects/gsmoj status --short`로 변경 파일을 확인한다.
- 이미 수정된 파일은 사용자 작업일 수 있으므로 되돌리지 않고, 관련 없는 변경 파일도 건드리지 않는다.
- 대량 포맷팅·줄바꿈 변환·템플릿 전체 재정렬은 요청 없이는 하지 않는다.
- `git reset --hard`, `git checkout -- .`, 강제 push, 브랜치 삭제는 명시 요청 없이는 하지 않는다.
- 비밀값, 서버 IP, 개인키, DB 비밀번호 및 배포와 무관한 IDE 파일(예: `.idea/`)은 커밋하지 않는다.
- 수정한 PHP 파일은 `php -l`로 문법을 검사한다. `php -l`에는 `-y` 옵션을 붙이지 않는다.
- 변경 후 `git diff --stat`과 주요 화면을 확인하고, 의도하지 않은 변경이 없는지 점검한다.

## 운영 서버 원격 작업

운영 서버의 `/home/judge`는 nginx가 직접 서비스하는 디렉터리다. 저장하는 순간 사용자 화면에 반영되고, 커밋하지 않은 수정이 쌓이면 `git pull`이 막혀 머지된 PR이 배포되지 않는다.

- 운영 서버에서 코드를 수정할 때는 **반드시 브랜치를 먼저 만든다.** `main`에서 직접 수정하거나 커밋하지 않는다.
  ```bash
  cd /home/judge && git switch main && git pull --ff-only && git switch -c <type>/<작업-이름>
  ```
- 작업 단위마다 커밋하고 push한다. 커밋하지 않은 수정을 서버에 남겨 두지 않는다.
- 작업이 끝나면 PR을 열어 `main`에 머지한다. 서버에서 `main`으로 직접 push하지 않는다.
- 머지한 뒤에는 서버를 `main`으로 되돌린다.
  ```bash
  cd /home/judge && git switch main && git pull --ff-only
  ```
- 작업을 시작하기 전에 `git status -sb`로 서버가 `main`이고, 변경 파일이 없고, `origin/main`보다 뒤처지지 않았는지 확인한다. 커밋하지 않은 수정이 있으면 되돌리지 말고 브랜치로 옮겨 커밋한 뒤 PR로 정리한다.
- `include/db_info.inc.php`처럼 `.gitignore`로 제외된 서버 설정 파일은 이 절차에서 제외한다. 이 파일은 php-fpm(`www-data`)이 읽을 수 있어야 하므로 수정한 뒤 권한을 확인한다.

## 커밋 및 PR 메시지

- 커밋과 PR 제목은 반드시 `<type>(<scope>): <변경 결과>` 형식을 사용한다. 예: `fix(submit): 제출 페이지의 CSRF 검증 오류 수정`.
- `type`은 소문자 영어로 쓰고, 변경 의도에 따라 다음 중 하나를 사용한다.
  - `feat`: 새 기능을 추가하거나 기존 기능·UI 동작을 변경한다. 예: `feat: 문제 목록의 검색 필터 개선`
  - `fix`: 오류, 깨진 화면, 잘못된 동작을 수정한다. 예: `fix: 관리자 메뉴 링크 오류 수정`
  - `refactor`: 사용자 동작을 바꾸지 않고 코드 구조·중복·가독성을 개선한다. 예: `refactor: 관리자 권한 확인 로직 정리`
  - `style`: 동작 변화 없이 화면 서식·정렬·클래스만 정리한다. 예: `style: 채점 현황 표 정렬 정리`
  - `translate`: 한국어 문구 또는 현지화를 변경한다. 예: `translate: 대회 메뉴 한국어 문구 통일`
  - `docs`: 작업 지침이나 문서를 변경한다. 예: `docs: 디렉터리별 작업 지침 분리`
  - `test`: 테스트 또는 검증 스크립트를 추가·수정한다. 예: `test: PHP 문법 검사 범위 추가`
  - `ci`: GitHub Actions 또는 배포 절차를 변경한다. 예: `ci: 배포 워크플로 검증 단계 추가`
  - `chore`: 설정, 의존성, 데이터 갱신 등 위 유형에 없는 작업을 처리한다. 예: `chore: 채점 결과 표시 문구 데이터 갱신`
- `scope`는 선택 사항이며 변경된 화면 또는 기능 영역을 소문자로 쓴다. 예: `submit`, `problem`, `status`, `contest`, `ranklist`, `user`, `admin`, `lang`, `template`, `ci`
- 여러 영역에 걸친 변경은 `scope`를 억지로 붙이지 않고 생략한다. 예: `refactor: 공통 세션 검증 로직 정리`
- `rafacotr` 대신 표준 철자인 `refactor`를 사용한다.
- 위 유형·범위 선택 표와 예시는 `.claude/skills/git-commit/references/`(Codex는 `.agents/skills/git-commit/references/`)를 따른다.
- 제목에는 변경한 파일명 나열이나 모호한 표현(예: `수정`, `변경사항`)만 쓰지 말고, 변경 결과를 구체적으로 쓴다.
- PR 본문에는 변경 목적, 수정 파일 또는 기능 범위, 검증 결과를 작성한다.
- UI·번역 변경은 영향을 받은 화면과 주요 문구를, 배포 변경은 워크플로 및 운영 영향 여부를 명시한다.
- 데이터베이스·권한·서버 설정처럼 수동 절차나 위험이 있는 변경은 별도 항목으로 적는다.
- 관련 없는 변경, 비밀값, 개인 정보는 PR과 커밋에 포함하지 않는다.

## 작업 완료 보고

1. 관련 파일과 현재 구조를 확인한다.
2. 변경 범위를 작게 정하고 수정한다.
3. 가능한 문법 검사 또는 정적 검사를 실행한다.
4. 수정 파일, 검증 결과, 남은 위험을 짧게 보고한다.

안전한 기본값이 있으면 진행한다. 단, 데이터 삭제, 서버 설정 변경, DB 스키마 변경, 비밀값 변경은 먼저 확인을 받는다.
