# Skills

Reusable units of work, invoked in Claude Code as `/<skill-name>`.
Adapted for this repository from
[themoment-team/themoment-ai-harness-sync](https://github.com/themoment-team/themoment-ai-harness-sync).

## Git Workflow

| Skill                                 | Description                                                                                                              |
|---------------------------------------|--------------------------------------------------------------------------------------------------------------------------|
| [git-commit](./git-commit/SKILL.md)   | Splits changes into logical units and commits them under the project convention. Opens a work branch when run on `main`. |
| [write-pr](./write-pr/SKILL.md)       | Builds the PR title, body, and single label from the commits since the base branch, then opens the PR on GitHub.          |

## Code Quality

| Skill                                   | Description                                                                                                                         |
|-----------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------|
| [convention-review](./convention-review/SKILL.md)   | Checklist review of changed files — PHP structure, SQL binding, session/CSRF and permissions, Korean wording, encoding, deployment. Prints a ✓/⚠/✗ report. |

## Design & Planning

| Skill                             | Description                                                                                                   |
|-----------------------------------|---------------------------------------------------------------------------------------------------------------|
| [planning](./planning/SKILL.md)   | Structured interview that surfaces hidden requirements, tradeoffs, and constraints, then writes a spec file.   |

## Conventions

Rules shared between skills live in each skill's `references/`:

- `git-commit/references/commit-conventions.md` — message format and type table (mirrored into `write-pr/references/`)
- `git-commit/references/scope-guide.md` — how to pick a commit scope
- `write-pr/references/labels.md` — which GitHub label a PR gets

Reference and instruction files are written in English. The output they produce — commit messages, PR
bodies, review rationale — stays Korean, per the root `AGENTS.md`.

## Codex Mirror

`.agents/skills/` holds the same skills for Codex, minus Claude-only frontmatter and tool names, with
reference paths spelled out literally instead of `${CLAUDE_SKILL_DIR}`.

**When editing a skill, change both copies.** Diff them to confirm they stayed in sync:

```bash
diff -r .claude/skills/<name> .agents/skills/<name>
```
