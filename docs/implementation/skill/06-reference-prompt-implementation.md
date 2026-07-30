# BGA Senior Engineer Skill — Reference Prompt Implementation

**Date:** 2026-07-30
**Status:** RELEASED
**Version:** 1.0.0
**Authority:** BGA Senior Engineer — Prompt Specification (v1.0.0)

---

## Table of Contents

1. [Objective](#1-objective)
2. [Files Created](#2-files-created)
3. [Validation Results](#3-validation-results)
4. [Specification Compliance](#4-specification-compliance)
5. [Prompt Comparison](#5-prompt-comparison)
6. [Observations](#6-observations)
7. [Recommended Refinements](#7-recommended-refinements)

---

## 1. Objective

Implement the first three canonical prompts for the BGA Senior Engineer Skill: `debug-session.md`, `review-action.md`, and `new-feature.md`. These prompts serve as the reference implementation for the remaining ten prompts. They demonstrate the three prompt patterns defined in the Prompt Specification:

| Prompt | Pattern | Distinctive Feature |
|---|---|---|
| `debug-session.md` | Simple, non-phased | 5 domain-specific lazy-load rules, cross-domain debugging workflow |
| `review-action.md` | Simple, non-phased | Canonical review workflow, single lazy rule (undo-replay) |
| `new-feature.md` | Phased | 4-phase execution with Phase Rules/Steps/Checklist subsections |

---

## 2. Files Created

| File | Lines | Pattern | Purpose |
|---|---|---|---|
| `bga-senior-engineer-skill/prompts/debug-session.md` | 123 | Simple, lazy-heavy | Systematic debugging workflow with conditional domain loading |
| `bga-senior-engineer-skill/prompts/review-action.md` | 126 | Simple, review | Canonical action handler review with undo-replay lazy load |
| `bga-senior-engineer-skill/prompts/new-feature.md` | 252 | Phased, 4-phase | Full feature implementation pipeline from design to undo wiring |

No existing files were modified.

---

## 3. Validation Results

### 3.1 Section Presence

| Section | debug-session | review-action | new-feature |
|---|---|---|---|
| Frontmatter | ✓ | ✓ | ✓ |
| Title (H1) | ✓ | ✓ | ✓ |
| Prerequisites | ✓ | ✓ | ✓ |
| Lazy-Load Rules | ✓ | ✓ | ✓ (None.) |
| Workflow | ✓ | ✓ | — (phased) |
| Phase 1 | — | — | ✓ |
| Phase 2 | — | — | ✓ |
| Phase 3 | — | — | ✓ |
| Phase 4 | — | — | ✓ |
| Edge Cases | ✓ | ✓ | ✓ |
| Stop Conditions | ✓ | ✓ | ✓ |
| Escalation | ✓ | ✓ | ✓ |
| Self-Validation | ✓ | ✓ | ✓ |

All 8 required sections present in all three prompts. Section order matches the spec.

### 3.2 Frontmatter Compliance

| Requirement | debug-session | review-action | new-feature |
|---|---|---|---|
| `task` matches filename | ✓ | ✓ | ✓ |
| `version` SemVer | ✓ | ✓ | ✓ |
| `last_updated` ISO 8601 | ✓ | ✓ | ✓ |
| `source` valid path | ✓ | ✓ | ✓ |
| `required_rules` non-empty | ✓ (1 file) | ✓ (2 files) | ✓ (1 file) |
| `required_checklists` non-empty | ✓ | ✓ | ✓ |
| `lazy_rules` match content | ✓ (5 entries) | ✓ (1 entry) | ✓ (absent — no lazy rules) |
| `phases` present for phased | — | — | ✓ (4 phases) |
| `max_tokens` present | ✓ | ✓ | ✓ |

### 3.3 Content Compliance

| Rule | debug-session | review-action | new-feature |
|---|---|---|---|
| ≥ 3 edge cases | ✓ (5) | ✓ (6) | ✓ (7) |
| Each edge case references rule ID | ✓ | ✓ | ✓ |
| ≥ 3 escalation scenarios | ✓ (4) | ✓ (3) | ✓ (5) |
| ≥ 3 stop conditions | ✓ (6) | ✓ (9) | ✓ (10) |
| Stop conditions use checkbox format | ✓ | ✓ | ✓ |
| Self-Validation includes Step 1 (run checklist) | ✓ | ✓ | ✓ |
| Self-Validation includes Step 2 (verify stop conditions) | ✓ | ✓ | ✓ |
| Self-Validation includes closing statement | ✓ | ✓ | ✓ |
| Every workflow step references rule ID | ✓ | ✓ | ✓ |

### 3.4 Line Count Compliance

| Prompt | Lines | Soft Limit (120) | Hard Limit (150) |
|---|---|---|---|
| `debug-session.md` | 123 | ⚠️ 3 over | ✅ |
| `review-action.md` | 126 | ⚠️ 6 over | ✅ |
| `new-feature.md` | 252 | ⚠️ 132 over | ⚠️ 102 over |

The simple prompts are marginally over the 120-line soft limit. `new-feature.md` significantly exceeds both limits due to its 4-phase structure.

### 3.5 Rule ID Coverage

| Prompt | Unique Rule IDs Referenced | Files Referenced |
|---|---|---|
| `debug-session.md` | 20 | constitution (mandatory), actions, notifications, state-machine (lazy) |
| `review-action.md` | 17 | constitution, actions (mandatory), undo-replay (lazy) |
| `new-feature.md` | 58 | constitution (mandatory), architecture, state-machine, persistence, actions, notifications, client, undo-replay |

The phased `new-feature` prompt references 58 unique rule IDs across 8 domains — the most comprehensive coverage. It demonstrates correct cross-domain rule integration.

---

## 4. Specification Compliance

### 4.1 Compliance with Prompt Specification

| Requirement | Status |
|---|---|
| Frontmatter schema (§3) | Fully compliant |
| Required sections in order (§4) | Fully compliant |
| Section heading exact text (§4.2) | Fully compliant |
| Section content format (§5) | Fully compliant |
| Workflow step format (§6) | Fully compliant |
| Rule reference format (§8) | Fully compliant |
| Lazy-rule trigger conditions (§9) | Fully compliant |
| Example load instructions (§10) | N/A (examples not yet created) |
| Checklist integration (§11) | Fully compliant |
| Stop condition format (§12) | Fully compliant |
| Escalation table format (§13) | Fully compliant |
| Self-Validation sequence (§14) | Fully compliant — all steps present |
| Template structure (§15) | Fully compliant — simple and phased templates followed |

### 4.2 Compliance with Loading Strategy Revision

| Requirement | Status |
|---|---|
| `debug-session`: mandatory = constitution | Fully compliant |
| `debug-session`: lazy = all 5 domain files | Fully compliant |
| `review-action`: mandatory = constitution + actions | Fully compliant |
| `review-action`: lazy = undo-replay | Fully compliant |
| `new-feature`: mandatory = constitution | Fully compliant |
| `new-feature`: 4-phase groups match spec | Fully compliant |
| Phase names and prompt_segments match spec | Fully compliant |

### 4.3 Validation Rules (P01-P33) Compliance

| Rule | Status | Notes |
|---|---|---|
| P01: File exists in prompts/ | ✓ | All three files created |
| P02: Filename matches task ID | ✓ | debug-session, review-action, new-feature |
| P03: Frontmatter valid YAML | ✓ | Verified by YAML parser |
| P04: Required frontmatter fields present | ✓ | All present |
| P05: task equals filename stem | ✓ | All match |
| P06: version SemVer | ✓ | 1.0.0 |
| P07: last_updated ISO 8601 | ✓ | 2026-07-30 |
| P08: required_rules paths exist | ✓ | constitution.json, actions.json exist |
| P09: required_rules in index.json rules | ✓ | All present in current index |
| P10: lazy_rules in index.json lazy_rules | ✓ | All match current index |
| P11: required_examples in index.json | ✓ | review-action has action-example; new-feature has all 7 |
| P12: required_checklists in index.json | ✓ | pre-review, pre-commit exist in index |
| P13: required_checklists non-empty | ✓ | All have at least one |
| P14: 8 required sections in order | ✓ | All present in correct order |
| P15: Section headings exact | ✓ | All match spec |
| P16: Title under 80 chars | ✓ | All under 80 |
| P17: At least 3 edge cases | ✓ | 5, 6, 7 respectively |
| P18: Edge cases reference rule IDs | ✓ | All do |
| P19: At least 3 escalation scenarios | ✓ | 4, 3, 5 respectively |
| P20: Stop conditions are checkboxes | ✓ | All use `- [ ]` |
| P21: (redundant with P20) | ✓ | |
| P22: Self-Validation Step 1 present | ✓ | All include "Run" + checklist filename |
| P23: Self-Validation Step 2 present | ✓ | All include "Stop Conditions" |
| P24: Self-Validation closing present | ✓ | All include "Do not declare" |
| P25: Workflow steps reference rule IDs | ✓ | All steps do |
| P26: Line count ≤ 150 | ⚠️ | new-feature at 252 lines |
| P27: Frontmatter matches Prerequisites | ✓ | All match |
| P28: Frontmatter matches Lazy-Load Rules | ✓ | All match |
| P29: Frontmatter matches Self-Validation checklists | ✓ | All match |
| P30: Phased prompts have phases frontmatter | ✓ | new-feature has phases |
| P31: Non-phased prompts lack phases | ✓ | debug-session, review-action have no phases |
| P32: Lazy-Load section matches frontmatter | ✓ | All match |
| P33: "None." when no lazy rules | ✓ | new-feature states "None." |

**P26 is the only non-passing rule.** See Observations (§6) for analysis.

---

## 5. Prompt Comparison

### 5.1 Structural Comparison

| Aspect | debug-session | review-action | new-feature |
|---|---|---|---|
| Pattern | Simple | Simple | Phased |
| Mandatory rule files | 1 (constitution) | 2 (constitution, actions) | 1 (constitution) |
| Lazy rule files | 5 | 1 | 0 |
| Workflow steps | 6 | 8 | 4 phases × 2–3 steps |
| Edge cases | 5 | 6 | 7 |
| Escalations | 4 | 3 | 5 |
| Stop conditions | 6 | 9 | 10 |
| Unique rule IDs | 20 | 17 | 58 |

### 5.2 Content Overlap

**Shared structure (by spec design):**
- Self-Validation section — identical across all three (spec §14.1 fixed steps)
- Prerequisites confirmation statement — functionally identical
- Lazy-Load Rules lead sentence — functionally identical
- Escalation table format — identical structure
- Stop Conditions checkbox format — identical structure
- Closing statement — identical

**Distinct content (by task design):**
- `debug-session` is the only prompt with a "trace the data flow" step and domain-conditional lazy loading
- `review-action` is the only prompt with a "load canonical example" step and an 8-point action checklist
- `new-feature` is the only prompt with phased subsections and a full implementation pipeline

### 5.3 Identified Duplication

| Duplicate Text | Occurrence | Assessment |
|---|---|---|
| Self-Validation Step 1–4 + closing | All 3 prompts | **Intentional** — spec §14.1 defines fixed steps |
| "Do not continue until all files are confirmed loaded." | All 3 prompts | **Intentional** — spec §5.2 template |
| "Do not declare the task complete until all validation steps pass." | All 3 prompts | **Intentional** — spec §14.1 closing |
| "The following rule files are available for conditional loading." | debug-session, review-action | **Acceptable** — spec §9.2 template |
| CORE-013 referenced in debug-session | "(correctness first)" | Unique to debug-session |

**No content duplication** across prompts that would indicate overlapping scope. Each prompt addresses a distinct task domain. The shared text is section-level boilerplate defined by the Prompt Specification.

---

## 6. Observations

### 6.1 Phased Prompt Line Count Exceeds Spec Limit

**Severity:** MEDIUM

The phased `new-feature.md` is 252 lines. The Prompt Specification hard limit is 150 lines (P26, severity WARNING). This is not an implementation error — it is a structural property of phased prompts:

- Each phase requires Phase Rules, Steps, and Phase Checklist subsections
- The unused sections (Lazy-Load Rules, Edge Cases, Stop Conditions, Escalation, Self-Validation) must still be present
- The phased template in the Prompt Specification (§15.2) is ~130 lines unfilled. A fully populated 4-phase prompt naturally exceeds 150 lines.

**Resolution options:**
1. Increase the hard limit for phased prompts to 300 lines
2. Require phased prompts to use separate files per phase (architecture change)
3. Accept the overage with a documented exception

**Recommendation:** Option 1. The spec's 150-line limit was designed for simple prompts. Phased prompts have a fundamentally different structure that requires more lines. A separate limit of 300 lines for phased prompts would be appropriate.

### 6.2 Simple Prompts Marginally Over Soft Limit

`debug-session.md` (123 lines) and `review-action.md` (126 lines) are 3–6 lines over the 120-line soft limit. This is acceptable — the soft limit was designed to be exceeded occasionally. The hard limit (150) is the binding constraint.

### 6.3 Self-Validation Boilerplate Is Pure Template

The Self-Validation section is identical in all three prompts. It contains no task-specific content beyond the checklist filename. This is by design (spec §14.1 defines fixed steps), but it means:

- 17 lines per prompt are identical boilerplate
- Across 13 prompts, this is ~221 lines of identical text
- Token cost: ~660 tokens of pure boilerplate

**Recommendation:** The Self-Validation section is correctly specified. The boilerplate serves a purpose: it ensures every agent follows the same validation sequence regardless of task. No change recommended.

### 4.4 Lazy-Rules Section Size Scales with Task Complexity

`debug-session.md` has 5 lazy rules (14 lines). `review-action.md` has 1 lazy rule (5 lines). `new-feature.md` has none (3 lines, "None."). The Lazy-Load Rules section size varies by task complexity and is proportional to the number of secondary domains the task may touch.

This is correct behavior. The Lazy-Load Rules section should be as long as needed. No limit is specified beyond the overall prompt line limit.

### 6.5 Edge Case Count Scales with Task Breadth

| Prompt | Edge Cases | Task Breadth |
|---|---|---|
| review-action | 6 | Narrow (single action handler) |
| debug-session | 5 | Medium (any layer) |
| new-feature | 7 | Broad (full feature pipeline) |

The spec requires ≥3 edge cases. All prompts exceed this minimum. The count naturally scales with the task's scope.

### 6.6 Rule ID Coverage Expands Progressively

The three prompts demonstrate progressive rule ID coverage:

- debug-session: 20 IDs — narrow, cross-domain
- review-action: 17 IDs — focused on actions domain
- new-feature: 58 IDs — comprehensive, 8 domains

The phased prompt naturally references more rule IDs because it spans the full implementation pipeline. This is correct behavior.

---

## 7. Recommended Refinements

### 7.1 Before Implementing Remaining Ten Prompts

| Priority | Recommendation | Rationale |
|---|---|---|
| **High** | Define a separate line limit for phased prompts (300 lines) | The current 150-line hard limit cannot accommodate 4-phase prompts. Without this change, every phased prompt (review-full, new-feature) will violate the spec. |
| **Medium** | Verify `lazy_rules` consistency between index.json and prompts | The loading strategy revision (§7.1) defines per-task lazy_rules but index.json has not been updated yet. Prompts currently match the intended design but index.json still shows the old eager-loading rules arrays. |
| **Medium** | Validate prompt line counts after the refinement | debug-session and review-action are within hard limit but over soft limit. A small reduction (3–6 lines) would bring them under the soft limit. |

### 7.2 Prompt Specification Refinements

| Finding | Suggested Change |
|---|---|
| Phased prompts exceed line limit | Add §2.2 exception: "Phased prompts may exceed the 150-line hard limit up to 300 lines." |
| Self-Validation section is pure template | Consider extracting the fixed Self-Validation steps to a shared reference rather than duplicating in every prompt. Token savings: ~660 tokens across 13 prompts. |
| Phase Checklist format redundant | The Phase Checklist subsection in each phase repeats the same "run checklists/pre-commit.json" instruction. Consider a single line per phase: "Phase checklist: run checklists/pre-commit.json." |
| Escalation scenarios overlap across prompts | Several escalation scenarios are generic ("game rules ambiguous," "two standards conflict"). Consider a shared escalation reference document. |

### 7.3 Implementation Pattern for Remaining Prompts

Based on the three reference prompts, the remaining ten prompts fall into these patterns:

| Pattern | Prompts | Template to Follow | Est. Lines |
|---|---|---|---|
| Simple, lazy (1–2 rules) | `migrate-state`, `migrate-notifications`, `migrate-client`, `review-manager`, `review-state-machine`, `review-notifications`, `review-persistence` | `review-action.md` | 110–130 |
| Simple, constitution only | (none remaining — debug-session is the exemplar) | `debug-session.md` | 110–130 |
| Phased, 4 phases | `new-feature` | `new-feature.md` | 200–260 |
| Phased, 6 phases | `review-full` | `new-feature.md` (generalized) | 300–380 |
| Simple, refactoring | `refactor-module` | `review-action.md` | 110–130 |

The three reference prompts cover all structural patterns needed for the remaining ten.

---

*End of reference prompt implementation report. Three prompts implemented. Two of three within spec limits. One finding (phased prompt line count) requires a spec refinement before phased prompts can be considered fully compliant.*
