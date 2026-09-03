# GitHub Labels Reference

Select **exactly 1 label** from the PR-eligible list below. Even when several labels apply, pick only
the single highest-priority one. Do NOT use issue-only or manual labels.

## PR-Eligible Labels (auto-selectable)

| Label           | When to use                                                    |
|-----------------|----------------------------------------------------------------|
| `bug`           | Bug fix — broken screen, wrong behavior, error                 |
| `documentation` | Docs-only changes (`AGENTS.md`, README, comments)              |
| `enhancement`   | New feature, improvement to an existing feature, refactoring, translation, UI cleanup |

## Off-limits Labels (do NOT assign)

| Label              | Reason                                        |
|--------------------|-----------------------------------------------|
| `question`         | Issues only                                   |
| `help wanted`      | Issues only                                   |
| `good first issue` | Issues only                                   |
| `invalid`          | Issues only                                   |
| `duplicate`        | Issues only                                   |
| `wontfix`          | Applied manually when the work is dropped     |

## Quick Decision

Walk the list top-down and stop at the first match — that is the one label to apply.

```
Bug fix?                              → bug
Docs only?                            → documentation
New feature, improvement, refactor,
translation, or UI cleanup?           → enhancement
Unsure?                               → enhancement
```

## Label Not Present in the Repository

These labels are a convention, not a guarantee — a target repository may not have them.
If the chosen label does not exist there, create the PR **without any label** instead of
creating the label or substituting another one. `scripts/create-pr.sh` handles this
automatically: it verifies the label up front and drops it when missing.
