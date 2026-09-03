---
name: write-pr
description: Generate a PR title, body, and a single label from commits since the base branch, then create the PR on GitHub. Handles base branch detection, label selection, and PR creation end-to-end.
allowed-tools: Bash(git *:*), Bash(bash *create-pr.sh:*), Bash(cat *:*), Bash(find *:*), Bash(gh pr *:*), Bash(gh repo view:*), Bash(gh label list:*), Read, Write, AskUserQuestion
---

## Step 1 — Gather Context

```bash
git branch --show-current
git log origin/main..HEAD --oneline 2>/dev/null || git log --oneline -15
git diff origin/main...HEAD --stat 2>/dev/null || git diff HEAD~5...HEAD --stat
git diff origin/main...HEAD 2>/dev/null || git diff HEAD~5...HEAD
```

Then read the instructions that govern the changed paths:

```bash
cat AGENTS.md
find . -name AGENTS.md -not -path "./.git/*"
```

Read the `AGENTS.md` nearest each changed file — `admin/`, `lang/`, `template/syzoj/`, and `.github/`
each carry their own. A PR template is optional in this repository; read it only if it exists:

```bash
cat .github/PULL_REQUEST_TEMPLATE.md 2>/dev/null
```

## Step 2 — Determine the Label

Read `${CLAUDE_SKILL_DIR}/references/labels.md` and select **exactly one** label based on the nature
of the changes. When several labels apply, follow the priority order in that reference and keep only
the top match.
Read `${CLAUDE_SKILL_DIR}/references/commit-conventions.md` for the title type and scope rules.

## Step 3 — Generate PR Content

**Title** — generate 3 options in the format `<type>(<scope>): <description>`:
- Type: one of `feat` / `fix` / `refactor` / `style` / `translate` / `docs` / `test` / `ci` / `chore`,
  chosen from the reference table — the same format as the commit titles
- Scope: optional; omit it when the PR spans several areas
- Description: Korean, concise, no emojis, states the change result — never a list of file names and
  never a vague word like `수정` or `변경사항`
- Wrap file names, function names, config keys, and technical terms in backticks
  (e.g. `submit.php`, `pdo_query`, `lang/ko.php`)

**Body** — write it in Korean using this structure:

```markdown
## 변경 목적
왜 이 변경이 필요한지 한두 문장으로 작성한다.

## 변경 범위
- 수정한 파일 또는 기능 범위
- UI·번역 변경이라면 영향을 받은 화면과 주요 문구
- 배포 변경이라면 워크플로 및 운영 영향 여부

## 검증 결과
- `php -l` 문법 검사 결과
- 확인한 화면과 동작
- `git diff --stat` 기준 의도하지 않은 변경 없음

## 수동 절차 및 위험
데이터베이스·권한·서버 설정처럼 수동 절차나 위험이 있는 변경은 별도 항목으로 적는다.
해당 사항이 없으면 "없음"이라고 쓴다.
```

Rules for the body:
- Korean 합쇼체: `~하였습니다`, `~되었습니다`, `~추가하였습니다`
- No emojis
- Max 2500 characters
- Wrap every file name, function name, table name, config key, and lang variable in backticks
- A merge to `main` deploys to production, so state plainly in 검증 결과 what was verified and how
- Never include unrelated changes, secrets, or personal data

## Step 4 — Write Body & Show Preview

Write the body to `PR_BODY.md`, then display:

```
## PR 제목 후보
1. [title1]
2. [title2]
3. [title3]

## 선택된 라벨
- label

## PR 본문 미리보기
[body content]
```

Use AskUserQuestion to ask which title to use (present options 1/2/3). Wait for the answer before
proceeding.

## Step 5 — Create PR

Run the creation script with the confirmed title and the single label:

```bash
bash "${CLAUDE_SKILL_DIR}/scripts/create-pr.sh" "<confirmed-title>" "PR_BODY.md" "<label>"
```

The script refuses to run from the base branch, and it drops the label and creates the PR unlabeled
when the repository does not have that label. Never create a missing label or swap in a different
one — an unlabeled PR is the expected outcome there.

After creation, display the PR URL. If the label was dropped, say so in one line.
Cleanup: remove `PR_BODY.md`.
