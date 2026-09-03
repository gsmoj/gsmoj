# Commit & PR Conventions

## Message Format

`<type>(<scope>): <description>`

- **Type**: lowercase English, exactly one from the table below
- **Scope**: optional — the affected screen or feature area, lowercase. Omit when the change spans
  several areas. For the selection rules, read `scope-guide.md`.
- **Description**: Korean, no period, states the *result* of the change
- Subject line only (no body)

```
feat: 문제 목록 검색 필터 추가
fix(submit): 제출 페이지의 CSRF 검증 오류 수정
translate(contest): 대회 메뉴 한국어 문구 통일
```

## Type Selection Table

| Type        | When to use                                                              | Example                                    |
|-------------|--------------------------------------------------------------------------|--------------------------------------------|
| `feat`      | Adds a feature, or changes how an existing feature or UI behaves          | `feat: 문제 목록의 검색 필터 개선`          |
| `fix`       | Repairs an error, a broken screen, or wrong behavior                      | `fix: 관리자 메뉴 링크 오류 수정`           |
| `refactor`  | Improves structure, duplication, or readability without changing behavior | `refactor: 관리자 권한 확인 로직 정리`      |
| `style`     | Changes markup, layout, or classes only — no behavior change              | `style: 채점 현황 표 정렬 정리`             |
| `translate` | Changes Korean wording or localization                                    | `translate: 대회 메뉴 한국어 문구 통일`     |
| `docs`      | Changes agent instructions or documentation                               | `docs: 디렉터리별 작업 지침 분리`           |
| `test`      | Adds or edits tests and verification scripts                              | `test: PHP 문법 검사 범위 추가`             |
| `ci`        | Changes GitHub Actions or the deployment procedure                        | `ci: 배포 워크플로 검증 단계 추가`          |
| `chore`     | Config, dependency, or data updates that fit none of the above            | `chore: 채점 결과 표시 문구 데이터 갱신`    |

## Choosing Between Overlapping Types

Walk the list top-down and stop at the first match.

```
Repaired broken behavior?              → fix
Korean wording only?                   → translate
Docs or agent instructions only?       → docs
Workflow or deployment only?           → ci
Tests or verification scripts?         → test
Structure only, behavior unchanged?    → refactor
Markup or layout only?                 → style
New feature, or feature/UI behavior?   → feat
Anything else (config, data, deps)     → chore
```

## Description Rules

- Korean, no trailing period
- Name the **result**, not the files touched
- Never a bare vague word such as `수정`, `변경사항`, `개선`
- Wrap file, function, and config names in backticks when they appear
- Use the same Korean term the rest of the project uses (see the terminology table below)

## Bad Titles

| Wrong                          | Why                                | Fixed                                   |
|--------------------------------|------------------------------------|-----------------------------------------|
| `fix: 수정`                     | No result stated                   | `fix: 순위 페이지의 페이징 오류 수정`    |
| `feat: status.php, submit.php`  | Lists file names                   | `feat: 채점 현황 필터 UI 통일`           |
| `Fix: 로그인 오류 수정`         | Type is capitalized                | `fix: 로그인 오류 수정`                  |
| `rafacotr: 로직 정리`           | Misspelled type                    | `refactor: 세션 검증 로직 정리`          |
| `fix(web): 버그 수정`           | Scope is a module, no result       | `fix(submit): 제출 실패 응답 처리 수정`  |

## Korean Terminology

Use one Korean term per concept across the whole project. The root `AGENTS.md` section
"공통 한국어 용어" is authoritative; this is the short form.

| Source term           | Korean      |
|-----------------------|-------------|
| Problem               | 문제        |
| Status                | 채점 현황   |
| Ranklist              | 순위        |
| Contest               | 대회        |
| Submit                | 제출        |
| Accepted              | 정답        |
| Wrong Answer          | 오답        |
| Compile Error         | 컴파일 오류 |
| Runtime Error         | 런타임 오류 |
| Time Limit Exceeded   | 시간 초과   |
| Memory Limit Exceeded | 메모리 초과 |
| Administrator / Admin | 관리자      |
