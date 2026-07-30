# BGA Senior Engineer Skill

Production-grade BGA game implementation guidance for AI agents.

## Architecture

The skill uses a three-tier progressive-disclosure loading model:

| Tier | Contents | When Loaded | Max Tokens |
|---|---|---|---|
| **Tier 0** | `skill.json` | On skill activation | 200 |
| **Tier 1** | `index.json` + prompt + rules + checklist | On task receipt | 3,000 |
| **Tier 2** | examples + references | On explicit prompt request | 600 |

## Directory Structure

```
bga-senior-engineer-skill/
├── skill.json        Manifest (always loaded)
├── index.json        Task-to-artifact map (loaded first in Tier 1)
├── README.md         This file
├── rules/            12 domain rule files (Tier 1)
├── prompts/          13 task-specific prompts (Tier 1)
├── checklists/       3 self-validation checklists (Tier 1)
├── examples/         7 canonical code examples (Tier 2, lazy-load)
└── references/       3 supplementary lookup tables (Tier 2, lazy-load)
```

## Supported Tasks

| Task | Purpose |
|---|---|
| `migrate-manager` | Extract a Manager from legacy Game.php |
| `migrate-state` | Convert states.inc.php to State classes |
| `migrate-notifications` | Extract centralized Notifications class |
| `migrate-client` | Convert Dojo to ES modules and BgaCards |
| `review-action` | Review a single action handler |
| `review-manager` | Review a Manager class |
| `review-state-machine` | Review the state machine |
| `review-notifications` | Audit notification system |
| `review-persistence` | Audit DB schema and globals |
| `review-full` | Full pre-release audit (phased) |
| `debug-session` | Systematic debugging workflow |
| `new-feature` | Add a new game feature (phased) |
| `refactor-module` | Refactor a module to canonical standards |

## Artifact Overview

| Artifact | Format | Location | Contents |
|---|---|---|---|
| Manifest | JSON | `skill.json` | Identity, version, capabilities, loading model |
| Index | JSON | `index.json` | Task-to-artifact mapping with keyword classification |
| Rules | JSON | `rules/*.json` | Domain-specific engineering rules with checks and fixes |
| Prompts | Markdown | `prompts/*.md` | Step-by-step task execution instructions |
| Checklists | JSON | `checklists/*.json` | Self-validation items with pass/fail conditions |
| Examples | JSON | `examples/*.json` | Canonical code patterns with annotations |
| References | JSON | `references/*.json` | Supplementary lookup tables |

## Version Compatibility

- **Package version:** 1.0.0
- **Runtime Specification:** v1.1 (frozen)
- **Runtime Validator:** ^1.0.0
- **Platform:** Mercurio agent platform v1.0.0+

## Extension

Add new tasks by creating a prompt file and adding an entry to `index.json`. Add new rules by creating a rule file and referencing it in the index. No existing artifacts need restructuring.

See `docs/ai-os/runtime-skill-architecture.md` for the complete architecture specification.
