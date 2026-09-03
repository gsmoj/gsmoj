---
name: git-commit
description: Create Git commits by splitting changes into logical units following project conventions. Detects the default branch and checks out a work branch before committing, since pushing main deploys to production.
allowed-tools: Bash
---

## Step 0 — Branch Check (Required)

Check the current branch first:

```bash
git branch --show-current
```

**If the current branch is `main`:**

A push to `main` triggers the deploy workflow and lands directly on the production server — see
`.github/AGENTS.md`. Never commit straight to `main`; open a work branch instead.

1. Analyze all changes with `git status --short` and `git diff`
2. Infer a branch name from the changes:
   - Format: `<type>/<kebab-case-description>` — the same type as the planned commit
   - Name the affected screen or feature
   - Examples: `fix/submit-csrf-check`, `feat/problem-search-filter`, `translate/contest-menu-korean`
3. Create and check out the branch:
   ```bash
   git checkout -b <type>/<inferred-name>
   ```
4. Proceed with the commit flow below

**If the current branch is NOT `main`:** proceed directly to the commit flow.

---

## Commit Message Rules

Format: `<type>(<scope>): <description>`

- **Types** (lowercase): `feat` / `fix` / `refactor` / `style` / `translate` / `docs` / `test` / `ci` / `chore`
  — for the full selection table, read `${CLAUDE_SKILL_DIR}/references/commit-conventions.md`
- **Scope**: optional, names the affected area — for the selection rules, read
  `${CLAUDE_SKILL_DIR}/references/scope-guide.md`
- **Description**: Korean, no period, states the result of the change — never the file names, and
  never a vague word like `수정` or `변경사항`
  - Good: `feat: 문제 목록의 검색 필터 개선`, `fix(submit): 제출 페이지의 CSRF 검증 오류 수정`
- Subject line only (no body)

## Commit Flow

1. Inspect changes: `git status --short`, `git diff`
2. **Leave files you did not change alone.** Pre-existing modifications may be the user's own work —
   do not revert them, and do not stage unrelated changed files.
3. Categorize your changes into logical units (translation / bug fix / UI cleanup / refactoring / …)
4. Group files per unit
5. Syntax-check every PHP file you changed before staging it:
   ```bash
   php -l <file>
   ```
   Do not pass `-y` to `php -l`.
6. For each group:
   - Stage only the relevant files with `git add <file>` — never `git add -A` or `git add .`
   - Write a message following the rules above
   - `git commit -m "<message>"`
7. Verify with `git log --oneline -n <count>` and `git diff --stat`

## Never Commit

- Secrets, server IPs, private keys, DB passwords
- IDE files unrelated to deployment, such as `.idea/`
- Edits to vendored libraries: `include/jquery-latest.js`, `include/PHPMailer.php`, `kindeditor/`, `ace/`
- Mass reformatting, line-ending conversion, or wholesale template realignment that nobody asked for

## Forbidden Without an Explicit Request

`git reset --hard`, `git checkout -- .`, force push, branch deletion.
