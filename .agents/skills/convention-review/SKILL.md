---
name: convention-review
description: Run a structured checklist over changed files — PHP style, SQL parameter binding, session/CSRF and permission checks, Korean wording consistency, encoding, commit conventions, and security basics. Produces a ✓/⚠/✗ report.
---

# Convention Review Guide

Analyze changed files and verify them against project conventions.

## Step 1 — Load Rules

Discover the rule files dynamically:

```bash
find . -name AGENTS.md -not -path "./.git/*"
```

Read each returned file in full, and for every changed file apply the `AGENTS.md` nearest it —
`admin/`, `lang/`, `template/syzoj/`, and `.github/` each carry their own instructions on top of the
root one. These are the authoritative rules for this review.

## Step 2 — Check Changes

1. List the changed files:
   ```bash
   git diff --stat
   git diff origin/main...HEAD --stat 2>/dev/null
   ```
2. Read the full diff, then read each changed file for detailed analysis
3. Syntax-check every changed PHP file (no `-y` flag):
   ```bash
   php -l <file>
   ```

## Step 3 — Review Checklist

Use the rules loaded in Step 1 as the authoritative source. Common areas to verify:

### PHP Structure
- [ ] Minimal change that follows the surrounding style — no reformatting of untouched lines?
- [ ] Stays inside the existing HUSTOJ structure — no framework swap or wholesale rewrite?
- [ ] Change confined to the files the requested screen or feature actually needs?
- [ ] `php -l` clean on every changed file?

### Database
- [ ] Queries go through the existing `pdo_query` path?
- [ ] Every user-supplied value bound as a parameter — no string-concatenated SQL?
- [ ] No query behavior silently simplified while cleaning up UI?

### Session, Permissions, CSRF
- [ ] Call flow checked before touching session, permission, or CSRF files?
- [ ] Permission checks still present and in the same order on every path?
- [ ] CSRF token verified on state-changing requests?
- [ ] Judging and security logic left intact rather than shortened?

### Korean Wording
- [ ] Shared copy pulled from a `lang/ko.php` message variable rather than hardcoded?
- [ ] Menus and buttons with the same meaning use the same Korean across the project?
- [ ] Terminology matches the table in the root `AGENTS.md` (문제 / 채점 현황 / 순위 / 대회 / 제출 / 정답 / 오답 / 관리자)?
- [ ] Wording is clear for school users rather than needlessly technical?

### Encoding
- [ ] Real on-disk encoding and the output path confirmed before editing?
- [ ] No mojibake introduced, and no whole-file encoding conversion that nobody asked for?

### Vendored Files
- [ ] No edits to `include/jquery-latest.js`, `include/PHPMailer.php`, `kindeditor/`, or `ace/`?

### Deployment
- [ ] For `.github/workflows/` changes: YAML indentation, secret names (`SERVER_HOST`, `SERVER_USER`, `SERVER_KEY`), SSH path and permissions all verified?
- [ ] Manual steps (DB migration, permission change, package install) documented instead of assumed?
- [ ] Aware that a merge to `main` deploys straight to production?

### Commit
- [ ] Messages follow `<type>(<scope>): <description>` with a lowercase type?
- [ ] Description names the change result, not the file names?
- [ ] Commits split into logical units?

### Other
- [ ] No excessive comments?
- [ ] No hardcoded secrets, server IPs, private keys, or DB passwords?
- [ ] No sensitive information written to logs or rendered output?
- [ ] No unrelated files (for example `.idea/`) caught up in the change?

## Report Format

For each item:
- ✓ Pass
- ⚠ Warning (recommendation)
- ✗ Error (needs fix)

Cite a specific source in the rationale for every ⚠ and ✗ — for example
`AGENTS.md §공통 작업 원칙`, `admin/AGENTS.md`, `.github/AGENTS.md §작업 규칙`.

Final summary:
- Total {n} items verified
- {p} passed, {w} warnings, {e} errors
