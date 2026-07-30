# BGA Senior Engineer Skill — Reference Library Implementation

**Date:** 2026-07-30
**Status:** RELEASED
**Version:** 1.0.0
**Authority:** BGA Senior Engineer — Runtime Specification v1.1

---

## 1. Objective

Create the reference library for the BGA Senior Engineer Skill. Reference files explain WHY — they provide architectural context, framework lifecycle knowledge, and migration philosophy. Unlike rules (which specify WHAT) and examples (which demonstrate HOW), references give agents the background understanding needed for architectural decision-making.

---

## 2. Files Created

| File | Lines | Sections | Concepts | Referenced By |
|---|---|---|---|---|
| `references/architecture-reference.json` | 40 | 5 | 5 | 3 prompts |
| `references/migration-reference.json` | 40 | 5 | 5 | 2 prompts |
| `references/bga-framework-reference.json` | 49 | 7 | 6 | 2 prompts |

All files valid JSON. All within size limits.

---

## 3. Architectural Coverage

### 3.1 architecture-reference.json

| Section | Covers |
|---|---|
| Layer Responsibilities | Game.php, Managers, States, Actions, Notifications, Client — what each owns |
| Data Flow | Complete action→notification→client lifecycle with framework routing |
| Manager Ownership Model | Aggregate boundaries, exclusive write ownership, testability |
| Why Layers Are Strict | Real-world consequences of each boundary violation |
| Client-Server Contract | Truth ownership, reconnect model, state projection |

### 3.2 migration-reference.json

| Section | Covers |
|---|---|
| Migration Philosophy | Incremental extraction, behavior preservation, parity verification |
| Why Incremental Extraction Wins | Comparison to big-bang rewrites, isolation of changes |
| Extraction Order Rationale | Risk-based ordering from config→DB→Managers→Notifications→States→Client |
| Safety During Migration | Checkpoints, adapters, parity tests, deprecation |
| Migration and Lazy Loading | How the lazy-load model applies during migration tasks |

### 3.3 bga-framework-reference.json

| Section | Covers |
|---|---|
| Server Request Lifecycle | Complete action→validation→mutation→notification→transition flow |
| Client State Lifecycle | Initial load, notification-driven updates, reconnect replay |
| State Machine Lifecycle | onEnteringState→args→action→transition with framework guarantees |
| Notification Delivery | Queue, delivery, spectator filtering, replay with idempotency |
| Reconnect Behaviour | getAllDatas delegation, notification replay, giveExtraTime |
| Zombie Mode | Disconnection handling, zombie method requirements, multi-zombie |
| Hidden Information Model | Public/private/hidden distinction, framework enforcement, getAllDatas per-player |

---

## 4. Cross-Reference Health

| Check | Result |
|---|---|
| All `related_rules` reference valid rule IDs | ✅ 86 unique rules across 3 references |
| All `related_examples` reference existing files | ✅ All 7 examples referenced |
| All `related_prompts` reference valid tasks | ✅ All prompt IDs exist in index.json |
| All `further_reading` paths are valid | ✅ All reference existing docs |
| No rule text duplicated verbatim | ✅ Explanatory content, not rule text |
| No example content duplicated | ✅ Distinct from example files |
| All reference files referenced by ≥1 prompt | ✅ Each has 2–3 prompt consumers |

---

## 5. Distinction from Other Artifacts

| Artifact | Mode | Example |
|---|---|---|
| Rules (`rules/*.json`) | **What** — normative, verifiable | "Game.php is orchestration only. Under 300 lines." |
| Prompts (`prompts/*.md`) | **How** — procedural, actionable | "Step 1: Identify the aggregate. Step 2: Load canonical example." |
| Examples (`examples/*.json`) | **How** — code demonstration | `class ResourceManager { function spend(...) { ... } }` |
| References (`references/*.json`) | **Why** — explanatory, contextual | "The layer boundaries exist because every violation has been observed in production BGA games." |

This distinction is maintained throughout the three reference files. No rule text is duplicated verbatim. No example code is repeated. Every reference section explains rationale rather than prescribing behavior.

---

## 6. Readiness Assessment

### 6.1 Package State

```
bga-senior-engineer-skill/
├── skill.json          (61 lines)   CREATED
├── index.json          (UPDATED)    References now resolve
├── README.md           (70 lines)   CREATED
│
├── rules/              (12 files)   FROZEN
├── prompts/            (13 files)   COMPLETE
├── checklists/         (3 files)    COMPLETE
├── examples/           (7 files)    COMPLETE
└── references/         (3 files)    COMPLETE ✅
```

### 6.2 All 41 Artifacts Now Present

| Directory | Required | Created | Status |
|---|---|---|---|
| `skill.json` | 1 | 1 | ✅ |
| `index.json` | 1 | 1 | ✅ |
| `README.md` | 1 | 1 | ✅ |
| `rules/` | 12 | 12 | ✅ (frozen) |
| `prompts/` | 13 | 13 | ✅ |
| `checklists/` | 3 | 3 | ✅ |
| `examples/` | 7 | 7 | ✅ |
| `references/` | 3 | 3 | ✅ |
| **Total** | **41** | **41** | **COMPLETE** |

### 6.3 Recommendations for Full Package Validation

| Priority | Recommendation | Rationale |
|---|---|---|
| **High** | Run full validation suite | All 41 artifacts created. Validate cross-references, token budgets, and consistency across the entire package. |
| **Medium** | Verify prompt-example alignment | Ensure every prompt that loads an example actually references the correct example file in its workflow steps. |
| **Low** | Add CHANGELOG.md | Referenced in skill.json metadata but not yet created. |

---

*End of reference library implementation. All 41 skill artifacts are now complete. The package is ready for full validation.*
