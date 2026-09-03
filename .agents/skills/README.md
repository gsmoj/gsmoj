# Skills (Codex)

Codex-compatible mirror of `.claude/skills/`. Each subdirectory's `SKILL.md` is the skill body; read
the one whose name matches the task at hand.

| Skill                                 | Description                                                                                                              |
|---------------------------------------|--------------------------------------------------------------------------------------------------------------------------|
| [git-commit](./git-commit/SKILL.md)   | Splits changes into logical units and commits them under the project convention. Opens a work branch when run on `main`. |
| [write-pr](./write-pr/SKILL.md)       | Builds the PR title, body, and single label from the commits since the base branch, then opens the PR on GitHub.          |
| [convention-review](./convention-review/SKILL.md) | Checklist review of changed files — PHP structure, SQL binding, session/CSRF and permissions, Korean wording, encoding, deployment. |
| [planning](./planning/SKILL.md)       | Structured interview that surfaces hidden requirements, tradeoffs, and constraints, then writes a spec file.              |

## Relationship to `.claude/skills/`

The two trees hold the same content. This one drops Claude-only frontmatter (`allowed-tools`) and
tool names, and spells out reference paths literally instead of using `${CLAUDE_SKILL_DIR}`.

**When editing a skill, change both copies.** Diff them to confirm they stayed in sync:

```bash
diff -r .claude/skills/<name> .agents/skills/<name>
```

Reference and instruction files are written in English. The output they produce — commit messages, PR
bodies, review rationale — stays Korean, per the root `AGENTS.md`.
