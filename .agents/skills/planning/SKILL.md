---
name: planning
argument-hint: [instructions]
description: Conduct an in-depth structured interview with the user to uncover non-obvious requirements, tradeoffs, and constraints, then produce a detailed implementation spec file.
---

Interview me relentlessly about every aspect of this plan until we reach a shared understanding. Walk
down each branch of the design tree, resolving dependencies between decisions one-by-one. After I
respond to each question, provide your evaluation and recommended answer.

Ask the questions one at a time.

If a question can be answered by exploring the codebase, explore the codebase instead.

## Before You Start

Read the instructions that govern the area in question — the root `AGENTS.md` plus the `AGENTS.md`
nearest the files under discussion:

```bash
find . -name AGENTS.md -not -path "./.git/*"
```

Do not ask about anything those files already answer.

## Branches to Resolve

Walk these where they bear on the plan — they are the decisions this project keeps running into:

- **Scope boundary** — which screens or admin pages does this touch? Does it stay inside the existing
  HUSTOJ PHP structure, or does it require structural change? Structural change needs an explicit
  decision, not an assumption.
- **Korean wording** — does this need new copy? Can an existing message variable in `lang/ko.php`
  carry it, or must a variable be added? Does the wording conflict with the shared terminology in the
  root `AGENTS.md`?
- **User vs. admin UI** — how far does the change extend into `admin/`? Are the two surfaces meant to
  match after this?
- **Template layer** — does it touch `template/syzoj/`? Do other templates need the same change?
- **Data and permissions** — any impact on the DB schema, permission checks, or the session/CSRF
  flow? If so, what manual steps does that imply?
- **Scoring and security** — does it reach judging logic or security checks? If so, what stays
  untouched, and why?
- **Encoding** — has the real on-disk encoding and the output path of each target file been checked?
- **Deployment risk** — a push to `main` deploys to production. Can this ship straight through, or
  does it need staged verification first?
- **Verification** — beyond `php -l`, which screens get checked, and in what order?

## Output

When the interview converges, write the spec to `docs/plans/<date>-<topic>.md` (create the directory
if needed) with these sections:

```markdown
# <주제>

## 목표
## 범위 (포함 / 제외)
## 확정된 결정
| 결정 | 선택 | 근거 |
## 변경 대상 파일
## 구현 순서
## 검증 계획
## 수동 절차 및 위험
## 열린 질문
```

Do not start implementing. Report the spec path, and list any open questions that remain.
