# Commit Scope Selection Guide

## Priority Rule

**Feature or screen area > file name > directory name.**

Scope names the part of the product that changed, not the file that happened to hold it. Omit the
scope entirely when the change spans several areas — an omitted scope is correct and common here.

## Discover Scope at Runtime

Do not rely on a hardcoded list. Determine the scope from:

1. `git diff --name-only` — which files changed
2. The page or screen those files render (this project is flat PHP: `submit.php`, `contest.php`,
   `ranklist.php` each map to one screen)
3. The nearest `AGENTS.md` to the changed files — `admin/`, `lang/`, `template/syzoj/`, and
   `.github/` each carry their own instructions and name their own area

## Common Scopes

| Scope      | When to use                                                       |
|------------|-------------------------------------------------------------------|
| `submit`   | Submission pages and flow: `submit.php`, `submitpage.php`         |
| `problem`  | Problem pages: `problem.php`, `problemset.php`, `problemstatus.php` |
| `status`   | Judging status pages: `status.php`, `status-ajax.php`             |
| `contest`  | Contest pages and ranking: `contest*.php`, `contestrank*.php`     |
| `ranklist` | Overall ranking: `ranklist.php`                                   |
| `user`     | Accounts, login, registration, profile                            |
| `admin`    | Anything under `admin/`                                           |
| `lang`     | Message variables under `lang/`                                   |
| `template` | Anything under `template/`                                        |
| `ci`       | `.github/workflows/`, deployment procedure                        |

## Cross-cutting Changes

When a change genuinely touches several areas, omit the scope rather than inventing an umbrella one:

```
# CORRECT
refactor: 공통 세션 검증 로직 정리
translate: 채점 결과 문구 전체 통일

# WRONG — invented umbrella scope
refactor(global): 공통 세션 검증 로직 정리
translate(all): 채점 결과 문구 전체 통일
```

## Examples

| Wrong                                 | Correct                                    | Reason                              |
|---------------------------------------|--------------------------------------------|-------------------------------------|
| `fix(php): 로그인 오류 수정`           | `fix(user): 로그인 오류 수정`               | `php` is a language, not an area    |
| `feat(status.php): 필터 추가`          | `feat(status): 채점 현황 필터 추가`         | Scope is an area, not a file name   |
| `translate(include): 버튼 문구 통일`   | `translate(lang): 제출 버튼 문구 통일`      | Wording lives in `lang/`            |
| `style(front): 표 정렬 정리`           | `style(status): 채점 현황 표 정렬 정리`     | `front` is not an area of this app  |
