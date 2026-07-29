# Rule Schema Design Review

**Purpose:** Review the canonical rule schema established in `rules/constitution.json` before it is frozen and reused across all 12 rule files.

**Status:** Schema review — content neutral

**Authority:** `docs/ai-os/runtime-skill-architecture.md` section 5.3 (formal specification)

---

## Table of Contents

1. [Field-by-Field Evaluation](#1-field-by-field-evaluation)
2. [Architecture Alignment Analysis](#2-architecture-alignment-analysis)
3. [Essential vs Optional vs Removed](#3-essential-vs-optional-vs-removed)
4. [Token Budget Analysis](#4-token-budget-analysis)
5. [Retrieval Optimization](#5-retrieval-optimization)
6. [LLM Reasoning Optimization](#6-llm-reasoning-optimization)
7. [Frozen Canonical Schema](#7-frozen-canonical-schema)
8. [File-Level Metadata Standard](#8-file-level-metadata-standard)
9. [Schema Migration from Constitution](#9-schema-migration-from-constitution)
10. [Final Recommendations](#10-final-recommendations)

---

## 1. Field-by-Field Evaluation

### 1.1 File-Level Metadata

| Field | Current | Evaluation |
|---|---|---|
| `domain` | Mandatory | **Essential.** Identifies which domain file. Used by index.json for task mapping. Required by architecture. No alternative. KEEP. |
| `description` | Mandatory | **Redundant with domain name.** The domain name "constitution", "architecture", "actions" etc. already conveys purpose. Description adds ~20 tokens per file with zero retrieval benefit. An agent never needs to read a description to understand what a rule file contains. REMOVE from file-level. |
| `version` | Mandatory | **Essential.** Required by architecture. Enables artifact-level versioning. KEEP. |
| `last_updated` | Mandatory | **Essential.** Required by architecture. Enables maintenance tracking. KEEP. |
| `source` | Mandatory | **Essential.** Required by architecture. Traceability to the documentation corpus. KEEP at file-level. |
| `schema_note` | Mandatory | **Documentation, not runtime.** The schema is defined by the structure of the rules array itself and formally specified in the runtime architecture document. An agent never reads this field. REMOVE. |

### 1.2 Per-Rule Fields: Essential (must be mandatory)

These fields are consumed by agents during every task execution and cannot be inferred or derived.

#### `id`

| Question | Answer |
|---|---|
| Why does it exist? | Stable identifier for cross-referencing across prompts, checklists, other rules, and index. |
| Is it actually consumed? | Yes. Prompts reference rule IDs. Checklists reference rule IDs. `see_also` references rule IDs. Index maps tasks to rule files by domain name, not rule ID, but individual rules are referenced by ID. |
| Is it redundant? | No. Unique purpose. |
| Could it be inferred? | No. Must be explicit and stable. |
| Should it move elsewhere? | No. Belongs with the rule itself. |
| Token cost: ~5 tokens per rule. | Acceptable. |
| Retrieval improvement: High. | ID is the primary lookup key. |
| Reasoning improvement: Medium. | Enables cross-referencing but not directly used in reasoning about the rule. |
| **Recommendation:** | **MANDATORY. KEEP.** |

#### `priority`

| Question | Answer |
|---|---|
| Why does it exist? | Conflict resolution. When two rules apply and contradict, lower priority number wins. |
| Is it actually consumed? | Yes, but indirectly. An agent reading two rules from different domains may need to decide which takes precedence. In practice, priority 1 rules (immutable) override everything. Priority 2-3 rules may conflict. |
| Is it redundant? | No. |
| Could it be inferred? | Potentially — rules from `constitution.json` are always priority 1. Rules from domain files could be priority 2-3. But explicit numbering removes ambiguity. |
| Should it move elsewhere? | No. |
| Token cost: ~5 tokens per rule. | Acceptable. |
| **Recommendation:** | **MANDATORY. KEEP.** |

#### `rule`

| Question | Answer |
|---|---|
| Why does it exist? | The actual guidance. This is the rule. |
| Is it actually consumed? | Yes. This is what the agent reads to determine what to do. This is the entire point. |
| Is it redundant? | No. Primary content. |
| Token cost: ~25 tokens per rule. | The essential content. Cannot eliminate. |
| **Recommendation:** | **MANDATORY. KEEP.** (Truncate to 200 chars max) |

#### `exceptions`

| Question | Answer |
|---|---|
| Why does it exist? | Conditions where the rule does not apply. Prevents over-application. |
| Is it actually consumed? | Yes. Without exceptions, an agent applies rules rigidly. Die rolls should not require undo logging. |
| Is it redundant? | No. |
| Could it be inferred? | No. Exceptions are domain knowledge specific to each rule. |
| Token cost: ~15 tokens per rule (when populated). | Acceptable — prevents wrong application that costs more tokens to debug. |
| **Recommendation:** | **MANDATORY. KEEP.** (Allow empty array) |

#### `check`

| Question | Answer |
|---|---|
| Why does it exist? | Self-validation. How the agent verifies its output complies with the rule. |
| Is it actually consumed? | Yes. The agent runs checks after writing code. Checklists reference rule IDs but do not embed check logic. The check exists with the rule, not with the checklist. |
| Is it redundant? | No. |
| Could it be inferred? | No. Each rule has a unique verification path. |
| Should it move elsewhere? | Should it move to checklists? No — checklists are task-specific groupings. The check for a rule belongs with the rule definition. If it moves, two places must be updated when a rule changes. |
| Structure: {method, automated}. | The architecture specifies `check` as a flat String. My implementation uses an object `{method, automated}`. This is a **divergence** from the architecture. |
| Token cost: ~30 tokens per rule. | This is the largest field. Could be compressed. |
| **Recommendation:** | **MANDATORY. FLATTEN TO STRING.** The architecture defines `check` as a string, not an object. The `automated` sub-field is aspirational — agents do not execute shell commands. A single prose string for both serves the same purpose in fewer tokens. |

#### `violations`

| Question | Answer |
|---|---|
| Why does it exist? | Pattern matching. LLMs recognize constraints best through examples. Violations show what wrong looks like. |
| Is it actually consumed? | Yes. When an agent reads code and looks for problems, it matches against violation patterns. |
| Is it redundant? | No. Compliments `rule` by providing negative examples. |
| Plural (array) vs singular (string). | The architecture defines `violation` as a singular String. My implementation uses `violations` as a plural array. This is a **divergence** from the architecture. |
| Which is better? | Array is better. Multiple patterns help agents recognize different manifestations of the same violation. A single string can list multiple patterns but becomes verbose and harder to scan. Argument in favor of divergence. |
| Token cost: ~25 tokens per rule (3 violations). | Acceptable for the reasoning improvement. |
| **Recommendation:** | **MANDATORY. KEEP AS ARRAY.** Multiple examples improve LLM pattern matching. Acceptable to diverge from the architecture on this point. |

#### `remediation`

| Question | Answer |
|---|---|
| Why does it exist? | Fix instruction. When the agent detects a violation, it needs to know how to fix it. |
| Is it actually consumed? | Yes. The self-correction workflow depends on this field. |
| Is it redundant? | No. |
| Named `remediation` vs architecture's `fix`. | The architecture defines `fix`. My implementation uses `remediation`. This is a **naming divergence**. |
| Which name is better? | `fix` is shorter (3 chars vs 10). Both convey the same meaning. The architecture is authoritative. |
| **Recommendation:** | **MANDATORY. RENAME TO `fix`.** Align with the architecture. Shorter field name saves tokens across all rules. |

#### `tags`

| Question | Answer |
|---|---|
| Why does it exist? | Cross-domain keyword filtering. Enables the agent to find which rule file to lazy-load when encountering an unexpected concern. |
| Is it actually consumed? | Yes. When loading supplementary rules (Tier 2 lazy-load), the agent scans tags to identify the correct file. |
| Is it redundant with category? | **Yes.** `category` serves the same purpose as tags but at a coarser granularity. Tags can express everything category does. |
| Token cost: ~12 tokens per rule (4 tags). | Acceptable. |
| **Recommendation:** | **MANDATORY. KEEP.** Single classification system. |

---

### 1.3 Per-Rule Fields: Optional (useful but not mandatory)

These fields improve reasoning or maintenance but are not required for task execution.

#### `rationale`

| Question | Answer |
|---|---|
| Why does it exist? | Explain why the rule exists. Helps agents understand the principle so they can apply it to novel situations. |
| Is it actually consumed? | Indirectly. When the agent faces an ambiguous situation not explicitly covered by the rule, the rationale helps it reason by first principles. But many tasks never need this. |
| Is it redundant? | No. Provides context the `rule` field does not. |
| Token cost: ~20 tokens per rule. | Significant. 12 files x 15 rules x 20 tokens = ~3,600 tokens saved if removed or made optional. |
| Does it improve retrieval? | No. |
| Does it improve reasoning? | Yes, but only for edge cases. Not needed for standard scenarios. |
| **Recommendation:** | **OPTIONAL.** Include only when the rationale is non-obvious. For most rules, the rule text itself conveys sufficient context. |

#### `applies_to`

| Question | Answer |
|---|---|
| Why does it exist? | Lists component types the rule governs. |
| Is it actually consumed? | Sometimes. In constitution.json, this helps agents understand which components a cross-cutting rule affects. In domain files (e.g., `actions.json`), ALL rules apply to actions — `applies_to` is redundant with the domain. |
| Is it redundant? | **Yes, with the file domain.** For constitution.json (cross-domain), it is useful. For every other rule file, the domain is the `applies_to`. |
| Token cost: ~8 tokens per rule. | Modest, but accumulates. |
| **Recommendation:** | **OPTIONAL.** Include only in constitution.json where rules span multiple domains. Omit from domain-specific files where `applies_to` is always the domain name. |

#### `see_also`

| Question | Answer |
|---|---|
| Why does it exist? | Navigation. Points the agent to related rules when this rule is relevant but a different rule handles a specific aspect. |
| Is it actually consumed? | Yes, when navigating between rules. Prompts can say "if X is relevant, also check CORE-007." The see_also field provides this navigation. But the agent can also discover related rules through tags. |
| Is it redundant? | Partially — tags and see_also overlap in purpose. But tags are for discovery ("find all rules about undo") while see_also is explicit ("if you are reading this rule, you should also read that rule"). |
| Token cost: ~5 tokens per rule. | Low. |
| **Recommendation:** | **OPTIONAL.** Useful but not required in every rule. |

#### `source` (per-rule)

| Question | Answer |
|---|---|
| Why does it exist? | Traceability. Cites which part of the source document this specific rule was distilled from. |
| Is it actually consumed by agents? | **No.** Agents never read source documents once the skill is built. This field is for human maintainers. |
| Is it redundant with the file-level `source`? | **Yes.** The file-level `source` tells you which documents the entire rule file came from. Per-rule `source` is only needed when a single rule comes from a different document than the rest of the file. This is rare. |
| Token cost: ~10 tokens per rule. | Significant across all files. |
| **Recommendation:** | **OPTIONAL at per-rule level. MANDATORY at file level.** File-level source is sufficient for traceability. Per-rule source only when the rule comes from a different source than its file. |

---

### 1.4 Per-Rule Fields: Remove

#### `title`

| Question | Answer |
|---|---|
| Why does it exist? | Human-readable label for quick scanning. |
| Is it actually consumed by agents? | **No.** An agent reads the `rule` field for the actual guidance. `title` is a shorter version of the same information. For example, CORE-001: title="Server Authority", rule="Server owns all truth...". The agent scans the rule, not the title, when loading rules. |
| Is it redundant? | **Yes.** `rule` IS the title. The first sentence of `rule` serves as the title. |
| Token cost: ~5 tokens per rule. | Low per rule, but ~60 tokens across 12 files x 15 rules saved. |
| Retrieval improvement. | Zero. Agents do not scan a list of rule titles — they load specific files via index.json. |
| **Recommendation:** | **REMOVE.** `rule` field serves the same purpose. One-sentence titles are embedded in the rule text. |

#### `category`

| Question | Answer |
|---|---|
| Why does it exist? | Sub-classification within a domain. |
| Is it actually consumed? | **Not for execution.** The agent filters rules by tags, not by category. For the agent, tags and categories serve the same purpose — two parallel classification systems. |
| Is it redundant? | **Yes, with `tags`.** A tag "architecture" does the same job as category "architecture". Two classification systems create maintenance burden and confuse agents. |
| Token cost: ~5 tokens per rule. | Low. |
| Retrieval improvement. | Zero — tags already provide this. |
| **Recommendation:** | **REMOVE.** Use `tags` for all classification and filtering. |

---

### 1.5 Schema Field: `check` Structure

The architecture defines `check` as a **flat string**:

```json
"check": "Count lines in game.php. Assert fewer than 300."
```

My implementation uses an **object**:

```json
"check": {
  "method": "Count lines in game.php...",
  "automated": "wc -l < game.php..."
}
```

**Analysis:**

The `automated` sub-field contains shell commands. Agents do not execute shell commands as part of their workflow. They read code and reason about it. The automated field adds ~15 tokens per rule that an agent never acts on.

The `method` sub-field contains the human-readable verification instructions. This is what agents actually consume.

**Recommendation:** **FLATTEN to a single string.** The architecture specification is authoritative. A single `check` string containing the manual verification method is sufficient. Remove `automated` — agents do not execute shell scripts.

**But wait — the automated check is useful.** If this skill is ever integrated with CI/CD tooling, the automated check becomes valuable. For the initial release with pure AI agents, it is dead weight. Should we keep it for future use?

Decision: Remove `automated`. The check field is for agent validation, not pipeline integration. If CI/CD integration is needed later, add it back as an optional sub-field. The single string aligns with the architecture.

---

## 2. Architecture Alignment Analysis

### 2.1 Deviations from the Architecture

The architecture (section 5.3) defines this schema:

| Field | Architecture Spec | Constitution Implementation | Verdict |
|---|---|---|---|
| `fix` | MANDATORY String | Named `remediation` | **Divergence.** Rename to `fix`. |
| `check` | MANDATORY String | Object `{method, automated}` | **Divergence.** Flatten to string. |
| `violation` | MANDATORY String (singular) | Array `violations[]` (plural) | **Intentional improvement.** Array enables multiple patterns. But rename to match architecture's singular `violation`. |
| `source` | OPTIONAL per-rule | MANDATORY per-rule | **Divergence.** Move source to file-level MANDATORY, per-rule OPTIONAL. |
| — | Not specified | Added `title` | **Remove.** Redundant with `rule`. |
| — | Not specified | Added `category` | **Remove.** Redundant with `tags`. |
| — | Not specified | Added `rationale` | **Make optional.** Useful but not mandatory. |
| — | Not specified | Added `applies_to` | **Make optional.** Only needed in cross-domain files. |
| — | Not specified | Added `exceptions` | **Keep as mandatory.** Critical guardrail not covered by architecture. |
| — | Not specified | Added `description` (file-level) | **Remove.** Redundant with domain name. |
| — | Not specified | Added `schema_note` (file-level) | **Remove.** Documentation, not runtime. |

### 2.2 Loading Model Alignment

| Loading Principle | Current Schema | Recommendation |
|---|---|---|
| **Progressive disclosure** | Agent loads entire rule file. All fields loaded at once. | Rule files are small enough (12-16 rules each). Loading per-rule fields selectively is not practical. No change needed. |
| **Deterministic retrieval** | Agent finds rules by domain → file path mapping in index.json. Rule ID provides stable reference. | Aligned. No changes needed. |
| **Tiered loading** | Rules are Tier 1 (task-load). All rules in a file are loaded together. | Aligned. Fields are all consumed at load time. No lazy-loadable sub-fields within a rule file. |
| **Token budget** | Current average ~36 lines per rule. | Optimized schema targets ~20 lines per rule. |

### 2.3 Retrieval Strategy Alignment

| Retrieval Principle | Current Schema | Recommendation |
|---|---|---|
| **Task classification** | index.json maps tasks to rule files. | Aligned. No per-rule retrieval needed. |
| **Rule discovery** | Agent loads all rules in a file. No per-rule search. | Aligned. |
| **Cross-domain filtering** | Agent uses tags to identify supplementary rules for lazy-loading. | Aligned. Keep tags. Remove category (redundant). |

### 2.4 Deterministic Execution Alignment

| Determinism Principle | Current Schema | Recommendation |
|---|---|---|
| **Same input → same guidance** | Fixed fields, no conditional content. | Aligned. All fields are static. |
| **Priority ordering** | Lower number = higher priority. | Aligned. Keep priority. |
| **Conflict resolution** | Lower priority overridden by higher priority. | Aligned. |

---

## 3. Essential vs Optional vs Removed

### 3.1 File-Level Metadata (Final)

| Field | Status | Rationale |
|---|---|---|
| `domain` | MANDATORY | Required by architecture. Identifies the domain. |
| `version` | MANDATORY | Required by architecture. Enables artifact versioning. |
| `last_updated` | MANDATORY | Required by architecture. Maintenance tracking. |
| `source` | MANDATORY | Traceability. Moved from per-rule to file-level. |

**Removed from file-level:** `description`, `schema_note`

### 3.2 Per-Rule Fields (Final)

| Field | Status | Rationale |
|---|---|---|
| `id` | MANDATORY | Stable identifier, cross-referencing. |
| `priority` | MANDATORY | Conflict resolution. |
| `rule` | MANDATORY | The guidance itself. |
| `violation` | MANDATORY | Restore architecture's singular name. Keep as array (improvement). |
| `check` | MANDATORY | Restore architecture's flat string format. |
| `fix` | MANDATORY | Restore architecture's name (was `remediation`). |
| `exceptions` | MANDATORY | Critical guardrail. Architecture did not specify this — improvement. |
| `tags` | MANDATORY | Cross-domain filtering. Architecture specified. |
| `see_also` | OPTIONAL | Navigation aid. Architecture specified as optional. |
| `rationale` | OPTIONAL | Context for edge cases. Not in architecture — improvement. |
| `applies_to` | OPTIONAL | Only in cross-domain files. Not in architecture — improvement. |
| `source` | OPTIONAL (per-rule) | Only when rule source differs from file. Architecture specified as optional. |

**Removed:** `title`, `category`

### 3.3 Field Ordering (for agent readability)

```json
{
  "id": "CORE-001",
  "priority": 1,
  "rule": "Server owns all truth...",
  "violation": [
    "Client JS mutates state without server action",
    "Server trusts client-supplied value without re-validating"
  ],
  "check": "Verify every DB write originates from PHP...",
  "fix": "Move all state mutation into server-side Manager methods...",
  "exceptions": [],
  "tags": ["server", "authority", "security"],
  "see_also": ["CORE-005", "CORE-011"]
}
```

Optional fields appear after mandatory fields. Field order is: identity → importance → guidance → detection → verification → correction → guardrails → cross-refs.

---

## 4. Token Budget Analysis

### 4.1 Current Constitution Metrics

| Metric | Value |
|---|---|
| Total lines | 584 |
| Rules | 16 |
| Average lines per rule | 36.5 |
| Estimated tokens (full file) | ~1,750 |
| Tokens per rule (average) | ~109 |

### 4.2 Optimized Schema Projection

With the optimized schema (10 mandatory fields, 4 optional):

| Field | Avg Tokens | Status |
|---|---|---|
| `id` | 5 | Mandatory |
| `priority` | 3 | Mandatory |
| `rule` | 30 | Mandatory |
| `violation` (array of 3) | 20 | Mandatory |
| `check` (flat string) | 20 | Mandatory |
| `fix` | 25 | Mandatory |
| `exceptions` (array) | 8 | Mandatory |
| `tags` (array of 3) | 10 | Mandatory |
| **Total mandatory** | **121** | |

| Optional field | Avg Tokens | Status |
|---|---|---|
| `rationale` | 20 | Optional |
| `applies_to` | 8 | Optional |
| `see_also` | 5 | Optional |
| `source` (per-rule) | 8 | Optional |

| Scenario | Tokens/Rule | 16 Rules | 15 Rules | 12 Rules |
|---|---|---|---|---|
| Mandatory only | 121 | 1,936 | 1,815 | 1,452 |
| + rationale (all) | 141 | 2,256 | 2,115 | 1,692 |
| + rationale (50% of rules) | 131 | 2,096 | 1,965 | 1,572 |

### 4.3 Total Projection: All 12 Rule Files

Assuming average 15 rules per file:

| Scenario | Lines/File | Lines Total | Tokens Total | Within 12K Budget? |
|---|---|---|---|---|
| **Current schema** | 550 | 6,600 | ~19,800 | **No** (165%) |
| **Optimized (mandatory only)** | 270 | 3,240 | ~9,720 | Yes (81%) |
| **Optimized (mandatory + 50% rationale)** | 300 | 3,600 | ~10,800 | Yes (90%) |
| **Optimized (mandatory + all optional)** | 400 | 4,800 | ~14,400 | **No** (120%) |

### 4.4 Savings Summary

| Change | Tokens Saved (12 files, 180 rules) |
|---|---|
| Remove `title` | ~900 |
| Remove `category` | ~900 |
| Flatten `check` (remove `automated`) | ~2,700 |
| Make `rationale` optional (50% usage) | ~1,800 |
| Move `source` to file-level only | ~1,800 |
| Remove file-level `description`, `schema_note` | ~400 |
| **Total potential savings** | **~8,500 tokens** |

The optimized schema fits within the 12,000 token full-skill budget. The current schema does not.

---

## 5. Retrieval Optimization

### 5.1 How Agents Actually Retrieve Rules

1. Agent loads index.json
2. Index maps task → rule file paths
3. Agent loads exact rule files specified
4. Agent reads all rules in the loaded files
5. If the agent needs supplementary rules, it checks `tags` to identify which additional files to load

**Key insight:** The agent never searches for individual rules. It always loads entire files. Therefore, per-rule fields that serve only discovery (title, category) are wasted.

### 5.2 Tag Strategy

`tags` is the ONLY field used for cross-domain rule discovery. It must be:
- Present on every rule
- Consistent across domains (same vocabulary)
- Sufficiently granular for filtering

Example tag vocabulary (frozen):
- Component tags: `game.php`, `actions`, `states`, `managers`, `models`, `notifications`, `client`, `database`, `engine`
- Concern tags: `architecture`, `security`, `correctness`, `safety`, `testing`, `framework`, `data`, `process`
- Action tags: `undo`, `replay`, `zombie`, `reconnect`, `spectator`, `i18n`, `performance`
- Violation tags: `circular`, `layer`, `authority`, `ownership`, `idempotency`

### 5.3 Index Optimization

No per-rule fields belong in the index. The index maps tasks to files, not to individual rules. Rule IDs are cross-referenced within rule files (via `see_also`) and from prompts (via inline references like "Follow ARCH-001 through ARCH-012").

---

## 6. LLM Reasoning Optimization

### 6.1 How LLMs Consume Rules

LLMs process rule files as contiguous text. They do not "search" within a file — they read it sequentially. The structure of each rule object affects how well the LLM retains and applies it.

### 6.2 Optimal Rule Structure for LLMs

Research on LLM behavior with JSON suggests:

1. **Most important fields first.** The LLM pays most attention to the beginning of each object.
2. **Concrete patterns over abstract descriptions.** LLMs excel at pattern matching. `violations` (concrete examples) are more effective than abstract rule text.
3. **Actionable verbs.** `fix` should start with a verb: "Move...", "Add...", "Create..."
4. **One concept per sentence.** Shorter sentences in the `rule` field improve retention.
5. **Fewer fields per rule.** Each additional field dilutes attention. 7-10 fields is optimal.

### 6.3 Proposed Field Order (Optimized for LLM Attention)

```
1. id         — identity (short, low attention cost)
2. priority   — importance (short, low attention cost)
3. rule       — THE GUIDANCE (max attention here)
4. violation  — what wrong looks like (pattern matching)
5. check      — how to verify (actionable)
6. fix        — how to correct (actionable)
7. exceptions — when not to apply (guardrail, rules at end)
8. tags       — cross-ref (metadata, minimal attention)
```

This order puts the critical guidance first, actionable details second, and metadata last. The LLM reads the rule sequentially and builds understanding before encountering administrative fields.

---

## 7. Frozen Canonical Schema

### 7.1 File Structure

```json
{
  "domain": "constitution",
  "version": "1.0.0",
  "last_updated": "2026-07-29",
  "source": "ai-os/bga-senior-engineer-doctrine.md sections 2, 12, 15",
  "rules": [
    {
      "id": "CORE-001",
      "priority": 1,
      "rule": "Server owns all truth. Every game state mutation must originate from server-side PHP. Client state is a read-only cache.",
      "violation": [
        "Client JS mutates state without server action",
        "Server trusts client-supplied value without re-validating"
      ],
      "check": "Verify every DB write, Deck move, and globals set originates from PHP. Client JS never calls these directly.",
      "fix": "Move all state mutation into server-side Manager methods. Client sends action name and parameters only.",
      "exceptions": [],
      "tags": ["server", "authority", "security"]
    }
  ]
}
```

### 7.2 Mandatory Fields

| # | Field | Type | Description | Example |
|---|---|---|---|---|
| 1 | `id` | String | Unique rule identifier. Format: DOMAIN-NNN. Never renumbered. | `"CORE-001"` |
| 2 | `priority` | Integer | 1-5. 1=input law that overrides all. 5=strong guideline. Lower wins conflicts. | `1` |
| 3 | `rule` | String | The guidance. 1-2 sentences. Actionable. Under 250 characters. | `"Server owns all truth..."` |
| 4 | `violation` | Array[String] | Patterns showing what the rule looks like when broken. 1-5 entries. | `["Client JS mutates..."]` |
| 5 | `check` | String | How to verify compliance. Concrete: grep pattern, line count, assertion. | `"Verify every DB write..."` |
| 6 | `fix` | String | How to correct a violation. References pattern or example. | `"Move all state mutation..."` |
| 7 | `exceptions` | Array[String] | Conditions where this rule does not apply. Empty array = no exceptions. Each entry describes the circumstance and justification. | `["Irreversible random events..."]` |
| 8 | `tags` | Array[String] | Searchable keywords for cross-domain retrieval. 2-5 entries. | `["server", "authority", "security"]` |

### 7.3 Optional Fields

| # | Field | Type | Description | When to Include |
|---|---|---|---|---|
| 9 | `rationale` | String | Why this rule exists. One sentence explaining the failure mode prevented. | When the rule's purpose is not obvious from the rule text itself. |
| 10 | `applies_to` | Array[String] | Component types this rule applies to. | Only in cross-domain files (constitution.json). Omit from domain-specific files. |
| 11 | `see_also` | Array[String] | Related rule IDs. Navigation aid. | When another rule is directly relevant to this one. |
| 12 | `source` | String | Override the file-level source for this specific rule. | Only when this rule comes from a different document than the rest of the file. |

### 7.4 Removed Fields

| Field | Reason | Replacement |
|---|---|---|
| `title` | Redundant with `rule` | None — first sentence of `rule` is the title |
| `category` | Redundant with `tags` | `tags` — use a consistent tag vocabulary |
| `check.automated` | Agents do not execute shell commands | Removed. `check` is a single string. |
| `description` (file) | Redundant with `domain` name | None |
| `schema_note` (file) | Documentation, not runtime | Architecture doc section 5.3 |
| `remediation` | Renamed to match architecture | `fix` |

### 7.5 Field Order Convention

Every rule in every file must use this exact field order:

```
id
priority
rule
violation
check
fix
exceptions
tags
--- optional ---
rationale
applies_to
see_also
source
```

This order is frozen. No new fields may be inserted between mandatory fields. Optional fields always appear after mandatory fields, in the order specified.

### 7.6 Naming Conventions

| Convention | Rule | Example |
|---|---|---|
| Rule IDs | `DOMAIN-NNN` with zero-padded 3-digit number | `CORE-001`, `ARCH-012`, `ACTN-003` |
| Domain names | Lowercase, one word | `constitution`, `architecture`, `state-machine` |
| File names | `<domain>.json` | `constitution.json`, `state-machine.json` |
| Tags | Lowercase, kebab-case for multi-word | `hidden-information`, `game.php`, `circular` |
| Component names | Capitalized, same as class names | `Game.php`, `Actions`, `Managers` |
| Violation entries | Sentence fragments, no period | `"Client JS mutates state without server action"` |
| Check entries | Full imperative sentence | `"Verify every DB write originates from PHP..."` |
| Fix entries | Imperative sentence starting with verb | `"Move all state mutation into Manager methods..."` |

### 7.7 Schema Rules

1. Every rule MUST have all mandatory fields.
2. Optional fields MAY be omitted entirely (not just left empty).
3. Field order MUST follow section 7.5.
4. No new mandatory fields may be added without a MAJOR version bump.
5. No mandatory field may be removed without a MAJOR version bump.
6. Optional fields may be added with a MINOR version bump.
7. Every rule file MUST have file-level `domain`, `version`, `last_updated`, and `source`.
8. The `rules` array MUST contain at least one rule.
9. Rule IDs MUST be unique within a file and across all files.

---

## 8. File-Level Metadata Standard

### 8.1 Final Standard

```json
{
  "domain": "architecture",
  "version": "1.0.0",
  "last_updated": "2026-07-29",
  "source": "standards/domain-architecture.md, standards/project-architecture.md",
  "rules": []
}
```

### 8.2 Field Definitions

| Field | Required | Type | Description |
|---|---|---|---|
| `domain` | Yes | String | Domain identifier from the approved list (section 7). One word, lowercase. |
| `version` | Yes | String | Semantic version of this rule file. Format: MAJOR.MINOR.PATCH. |
| `last_updated` | Yes | String | ISO 8601 date of last modification. Format: YYYY-MM-DD. |
| `source` | Yes | String | Path(s) to source documentation this file was distilled from. Multiple sources separated by commas. |

---

## 9. Schema Migration from Constitution

### 9.1 Changes Required to constitution.json

| Field | Current | After Migration |
|---|---|---|
| `description` (file) | Present | Remove |
| `schema_note` (file) | Present | Remove |
| `title` (per-rule) | Present on all 16 rules | Remove |
| `category` (per-rule) | Present on all 16 rules | Remove |
| `remediation` (per-rule) | Present on all 16 rules | Rename to `fix` |
| `check` (per-rule) | Object `{method, automated}` | Flatten to string containing `method` content only |
| `rationale` (per-rule) | Present on all 16 rules | Make optional. Evaluate each rule: keep only where non-obvious. |
| `applies_to` (per-rule) | Present on all 16 rules | Make optional. Keep in constitution.json (cross-domain). |
| `source` (per-rule) | Present on all 16 rules | Move to file-level mandatory. Remove per-rule except where it differs. |

### 9.2 Token Impact of Migration on constitution.json

| Change | Lines Saved | Tokens Saved |
|---|---|---|
| Remove file-level description, schema_note | ~3 | ~20 |
| Remove `title` from 16 rules | ~16 | ~80 |
| Remove `category` from 16 rules | ~16 | ~80 |
| Flatten `check` from object to string | ~48 | ~240 |
| Remove `rationale` from 10/16 rules | ~40 | ~120 |
| Move `source` to file-level (reclaim per-rule space) | ~48 | ~160 |
| **Total savings** | **~171 lines** | **~700 tokens** |

**Resulting constitution.json:** ~413 lines, ~1,050 tokens (down from 584 lines, ~1,750 tokens).

---

## 10. Final Recommendations

### 10.1 Schema Actions

| Action | Fields | Impact |
|---|---|---|
| **REMOVE** | `title`, `category`, file-level `description`, file-level `schema_note`, `check.automated` | Saves ~5,000 tokens across all files |
| **RENAME** | `remediation` → `fix` | Aligns with architecture, saves tokens (shorter name) |
| **FLATTEN** | `check` from object to string | Saves ~2,700 tokens across all files |
| **MOVE** | `source` from per-rule mandatory to file-level mandatory | Saves ~1,800 tokens across all files |
| **MAKE OPTIONAL** | `rationale`, `applies_to`, `see_also`, `source` (per-rule) | Enables 40-60% token reduction when omitted |
| **KEEP AS-IS** | `id`, `priority`, `rule`, `tags` | Already aligned with architecture |
| **IMPROVE** | `violation` (keep plural array, rename to match architecture singular `violation`) | Acceptable divergence from architecture |

### 10.2 Priority of Changes

1. **Immediate** (apply before creating next rule file): Flatten `check`, remove `title`, remove `category`, rename `remediation` to `fix`
2. **Before Phase 2 complete**: Move `source` to file-level, make `rationale` optional, update constitution.json
3. **Before Phase 4 (prompts)**: Ensure field order is frozen so prompts reference correct fields

### 10.3 Risk Mitigation

| Risk | Mitigation |
|---|---|
| Removing `title` loses quick-scanning ability | The `rule` field IS the title. First sentence serves as label. |
| Removing `category` creates classification gap | Tags must cover all dimensions that category covered. Review tag vocabulary. |
| Making `rationale` optional loses context for some rules | Default to including rationale. Remove only when rule text is self-explanatory. |
| Flattening `check` loses machine-readability for CI/CD | Add back as optional sub-field if CI/CD integration is needed. Not needed for agent-only execution. |
| Renaming `remediation` to `fix` breaks prompts already written | Prompts are written in Phase 4 — after schema is frozen. No existing prompts to break. |

---

## Appendix A: Approved Tag Vocabulary

These tags are the single classification system (replacing both `category` and `tags`):

| Tag | Description | Used In |
|---|---|---|
| `server` | Server-side concerns | constitution |
| `authority` | Server authority/ownership | constitution |
| `security` | Information protection | constitution |
| `architecture` | System structure | constitution, architecture |
| `game.php` | Game.php orchestration | constitution, architecture |
| `actions` | Action handlers | constitution, actions |
| `states` | State machine | constitution, state-machine |
| `managers` | Domain managers | constitution, architecture |
| `models` | Data models | architecture |
| `notifications` | Notification system | constitution, notifications |
| `client` | Client-side architecture | constitution, client |
| `database` | Persistence | constitution, persistence |
| `engine` | Engine/flow | architecture |
| `undo` | Undo mechanics | constitution, undo-replay |
| `replay` | Replay determinism | constitution, undo-replay |
| `zombie` | Player disconnection | constitution, state-machine |
| `reconnect` | Reconnection | synchronization |
| `spectator` | Spectator mode | synchronization |
| `animations` | Animation system | animations |
| `testing` | Test coverage | constitution, testing |
| `migration` | Legacy migration | migration |
| `i18n` | Internationalization | notifications |
| `refactor` | Code refactoring | architecture, migration |
| `correctness` | Correctness guarantees | constitution |
| `maintainability` | Code maintenance | architecture, persistence |
| `performance` | Performance optimization | (various) |
| `expansion` | Expansion readiness | persistence |

---

## Appendix B: Frozen Schema Reference

The canonical rule schema is now frozen at version `1.0.0` as defined in section 7 of this document.

All future rule files (`architecture.json`, `actions.json`, `state-machine.json`, `persistence.json`, `notifications.json`, `client.json`, `synchronization.json`, `animations.json`, `testing.json`, `undo-replay.json`, `migration.json`) MUST use exactly this schema.

Any deviation requires updating this review document and all existing rule files before proceeding.
