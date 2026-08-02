# BGA Senior Engineer — Agent Skill Runtime Design (v2)

**Task:** Design `dist/bga-senior-engineer/` as an exemplary, first-class **Agent Skill** — a reusable capability, not a documentation bundle.

**Status:** DESIGN ONLY (v2). Nothing is packaged. `SKILL.md` is deliberately NOT written in this step. No runtime files are created, no files are moved, no implementation artifacts are modified.

**Architectural sources of truth (read before this design was written):**
- OpenCode — Agent Skills documentation (`https://opencode.ai/docs/skills/`): discovery locations, frontmatter contract, `skill` tool loading, name/description rules, per-agent permissions.
- Agent Skills specification (`https://agentskills.io/specification.md`): directory contract (`SKILL.md` + optional `scripts/` `references/` `assets/`), frontmatter fields, progressive disclosure (metadata ≈ 100 tokens → instructions < 5,000 tokens → resources on demand), file-reference conventions.
- Agent Skills — Best practices (`https://agentskills.io/skill-creation/best-practices.md`): coherent units, spending context wisely, moderate detail, defaults not menus, procedures over declarations, gotchas, validation loops, progressive-disclosure structure ("load X when Y").
- Agent Skills — Optimizing descriptions (`https://agentskills.io/skill-creation/optimizing-descriptions.md`): the `description` field is the sole trigger mechanism; imperative phrasing; user intent over implementation; erring toward trigger; near-miss negative testing.
- Agent Skills — Using scripts (`https://agentskills.io/skill-creation/using-scripts.md`): scripts must be non-interactive, self-documenting (`--help`), structured-output, idempotent, exit-coded.

**Content source:** the frozen, validator-passing Mercurio skill package at `bga-senior-engineer-skill/` (Runtime Specification v1.1: 12 rule files / 185 rules, 13 prompts, 3 checklists, 7 examples, 3 references). All measured sizes are from 2026-08-02 (bytes ÷ 4 ≈ tokens).

---

## 1. Design Goals (and what changed between v1 and v2)

| Goal | v1 plan | v2 design |
|---|---|---|
| Progressive disclosure | Three-tier Mercurio model re-encoded | Three-stage Agent Skills model: metadata → SKILL.md → on-demand resources, with explicit per-file load conditions |
| Minimal activation context | Activation = `skill.json` (~460 tokens) | Activation = description only (~150 tokens, replicated per agent); SKILL.md loads only when the skill is invoked |
| Excellent routing | `index.json` keyword map re-encoded as a table | Description-level trigger (task families + keywords) + SKILL.md router table; keyword-match algorithm preserved from `index.json` |
| Lazy loading | Examples/references lazy; rules task-loaded | Rules, tasks, examples, checklists, references ALL lazy — loaded only when the router or a prompt names them |
| High trigger reliability | Not designed (v1 never drafted the description) | Description drafted to best-practice spec (imperative, user-intent, keyword-front-loaded, boundary clauses, ≤ 300 chars) |
| Low token consumption | Honest measured corpus (~119K) but shipped 15K of distilled official docs | Corpus trimmed to the frozen canon; nothing shipped that is not loadable-on-demand guidance |
| Reusable cross-project deployment | Mercurio-flavored README, platform-specific assumptions | Platform-agnostic; installs into any skills location (OpenCode, Claude, Agents); zero absolute paths; zero project coupling |
| Maintainability | Dist mirrored repo layout | Dist derives from the frozen canon by copy; every artifact's origin and sync rule is explicit; frozen content is never edited in the dist |

---

## 2. The Central Decision: One Skill, Not a Documentation Bundle

### 2.1 What this skill IS

A **reusable capability** for working on BGA (Board Game Arena) game implementations: domain expertise (185 frozen engineering rules), repeatable workflows (13 task procedures), and self-validation (3 checklists + a mechanical checker script). It is portable knowledge + procedure, designed to be installed into any project that implements a BGA game — including, but not limited to, `bga-mercurio` — and invoked by any Agent Skills-compatible agent (`orchestrator`, `worker`, `firstmate`, future agents).

### 2.2 What this skill is NOT

- **Not project documentation.** It contains no description of any single game project, no repo map, no absolute paths.
- **Not a documentation mirror.** The 12 standards docs (~202K tokens), the 67 scraped official HTML files (~285K tokens), the planning/audit records (~88K tokens) are **source material**, not runtime content. The skill ships their *distillation* (the frozen rules/references), which already exists.
- **Not a platform manifest.** The Mercurio `skill.json`/`index.json` are replaced by `SKILL.md`. Mercurio remains a supported consumer of the *content*, but the OpenCode package is its own format.

### 2.3 Single skill vs. multiple skills

| Option | Verdict | Rationale |
|---|---|---|
| **One skill: `bga-senior-engineer`** | **ADOPTED** | The domain is one coherent unit of work ("engineer BGA games") — per best practices, coherent units compose well and route precisely. All 13 task families share one rule corpus (constitution + domain files), one validation culture, and one vocabulary; splitting would duplicate the corpus and risk conflicting instructions. One skill keeps the `<available_skills>` list small — important when `orchestrator`/`worker`/`firstmate` each carry every skill's description in context. |
| Several task-family skills (`bga-migrate`, `bga-review`, …) | Rejected | Forces multi-skill loads for single tasks (e.g., migrate touches review checklists), duplicates the constitution, fragments routing, and bloats per-agent discovery context. |
| One skill + subskills | Rejected | Adds cross-skill reference chains ("load skill X for this") — the spec explicitly warns against nested reference chains; and subskills would each need their own description budget. |

### 2.4 How the Agent Skills lifecycle maps onto this skill

1. **Discovery** — every agent sees only `name` + `description` (~150 tokens). The description must carry the entire trigger burden (§5).
2. **Activation** — on a matching task, the agent loads the full `SKILL.md` body (target ≤ 4,500 tokens): router, universal law, load protocol, validation loop, gotchas.
3. **Execution** — the router maps the request to one task; the load protocol names the exact files to read and **when**; the agent loads only those.

---

## 3. Final Architecture

```
dist/bga-senior-engineer/
├── SKILL.md                          # The skill: frontmatter + body (the only file ever auto-loaded)
│                                     #   frontmatter: name, description, license, compatibility, metadata
│                                     #   body: trigger guide · router table · universal law ·
│                                     #         load protocol · validation loop · gotchas · stop conditions
│
├── LICENSE                           # MIT (verbatim from repo root; frontmatter license: MIT)
│
├── references/                       # knowledge consulted on demand (never auto-loaded)
│   ├── rules/                        #   12 frozen rule files, verbatim (the canonical canon)
│   │   ├── constitution.json         #     immutable law — loaded on EVERY task
│   │   ├── architecture.json         #     …11 domain files, loaded per task by the router
│   │   ├── state-machine.json
│   │   ├── actions.json
│   │   ├── persistence.json
│   │   ├── notifications.json
│   │   ├── client.json
│   │   ├── synchronization.json
│   │   ├── animations.json
│   │   ├── testing.json
│   │   ├── undo-replay.json
│   │   └── migration.json
│   ├── tasks/                        #   13 task procedures, verbatim (loaded after routing)
│   │   ├── migrate-manager.md
│   │   ├── migrate-state.md
│   │   ├── migrate-notifications.md
│   │   ├── migrate-client.md
│   │   ├── review-action.md
│   │   ├── review-manager.md
│   │   ├── review-state-machine.md
│   │   ├── review-notifications.md
│   │   ├── review-persistence.md
│   │   ├── review-full.md
│   │   ├── debug-session.md
│   │   ├── new-feature.md
│   │   └── refactor-module.md
│   ├── framework-reference.json      #   verbatim lookup: BGA framework lifecycle/API facts
│   ├── architecture-reference.json   #   verbatim lookup: component-boundary rationale
│   ├── migration-reference.json      #   verbatim lookup: legacy → modern mapping
│   └── project-matrix.json           #   NEW distilled from docs/foundation/reference-project-analysis.md
│                                     #   (problem → exemplar project → file path → backup)
│
├── assets/                           # templates & structured validation data (loaded on demand)
│   ├── checklists/                   #   3 quality gates, verbatim (the validation templates)
│   │   ├── pre-commit.json
│   │   ├── pre-review.json
│   │   └── pre-release.json
│   └── examples/                     #   7 canonical code patterns, verbatim (code templates)
│       ├── manager-example.json
│       ├── action-example.json
│       ├── model-example.json
│       ├── notification-example.json
│       ├── state-example.json
│       ├── client-manager-example.json
│       └── undo-example.json
│
└── scripts/
    └── check_project.py             # NEW runtime capability: mechanical rule checks
                                     # (non-interactive, --help, JSON stdout, exit codes)
```

**Counts:** 44 files ≈ 445 KB ≈ 110K tokens of corpus — but the context window never sees more than one bundle at a time (§8). Frozen content (41 files, 413.8 KB) is copied verbatim; the additions are SKILL.md, `project-matrix.json`, `check_project.py`, and LICENSE.

---

## 4. Rationale for Every Directory and Every Major Artifact

### 4.1 `SKILL.md` — the skill itself

**Responsibilities (and only these):**
1. **Trigger:** the `description` frontmatter (see §5 for the draft). This is the *only* text every agent sees before activation.
2. **Identity:** `name: bga-senior-engineer` (must match the directory name), `license: MIT`, `compatibility`, `metadata` (runtime-spec version `v1.1`, source commit).
3. **Router:** a compact table mapping request signals → task → exact files to load (see §6).
4. **Universal law:** the mandate to load `references/rules/constitution.json` on every task, plus the load protocol (one bundle at a time, load files only when named, phased loading for `review-full`/`new-feature`).
5. **Validation loop:** do → run checklist → fix → re-run, until pass (per best-practices "validation loops").
6. **Gotchas:** a short, high-value list of corrections the agent will otherwise get wrong (e.g., "Game.php must stay under 300 lines; notification payloads must split public/private; always wrap strings in `clienttranslate()`"). Per best practices, gotchas live in SKILL.md — the agent reads them before encountering the situation.
7. **Stop conditions** per task family.

**Explicitly NOT in SKILL.md:** rule content (referenced by file), framework documentation (referenced), example code (referenced), the 13 task procedures (referenced). It is a router + protocol, not a condensation of the corpus.

**Size targets:** body ≤ 350 lines / ≤ 4,500 tokens (spec: keep SKILL.md under 500 lines and 5,000 tokens — the agent loads the whole file on activation). Description ≤ 300 characters (spec hard limit: 1,024; we target far lower because the description is replicated in every agent's context).

### 4.2 `references/` — knowledge, consulted on demand

The spec defines `references/` as "additional documentation that agents can read when needed … loaded on demand, so smaller files mean less use of context." This maps exactly onto the frozen canon.

| Artifact | Rationale |
|---|---|
| `references/rules/*.json` (12) | The canonical engineering canon. Kept **verbatim** and **JSON**: the frozen v1.1 schema, the validator, and the release process all depend on this byte-level format; reformatting would create a second source of truth. Split per domain (12 files, avg 5.4K tokens each) so a task loads 1–8 files, never the corpus. |
| `references/tasks/*.md` (13) | The task procedures. Kept **verbatim** Markdown (prompts are prose workflows; Markdown is the agent-native format). Loaded only after routing. Inlining them into SKILL.md would blow the 5K-token activation budget (~20K tokens); keeping them in one file each preserves per-task granularity. |
| `references/framework-reference.json`, `architecture-reference.json`, `migration-reference.json` | The existing lookup tables. Verbatim. Lazy — loaded only when a prompt names them. |
| `references/project-matrix.json` | **New (distilled)** — the one gap-fill the frozen architecture promised (`reference-project-matrix.json`) but never shipped. ~1.5K tokens: problem → best exemplar project → file → backup. It is the skill's pointer to reference-projects WITHOUT shipping them (§4.7). |

**Removed from v1 design — the `references/official/` chapter set (11 distilled BGA framework docs, ~15K tokens).** Rationale: this was documentation packaging, not skill content. The framework facts agents need are already distilled into `rules/` (with `source` citations) and `framework-reference.json`; anything deeper is better fetched from the platform docs at the moment of need. Per best practices: "add what the agent lacks, omit what it knows … would the agent get this wrong without this instruction?" — a third-party redistribution of scraped BGA docs is precisely the over-packaging the skill should avoid, and it carries the same redistribution exposure as the reference projects (§4.7). If a gap is found later, it is added as one focused reference file, not a chapter set.

### 4.3 `assets/` — templates and structured validation data

The spec defines `assets/` as "templates, images, data files, lookup tables, schemas."

| Artifact | Rationale |
|---|---|
| `assets/checklists/*.json` (3) | Quality gates = structured validation templates. Loaded when the router/prompt says "validate output with `pre-review.json`". They are the skill's **validator** in the runtime sense: a reference the agent checks its work against (best-practices "a reference document can also serve as the validator"). |
| `assets/examples/*.json` (7) | Canonical code patterns = code templates. Loaded only when a prompt explicitly names one ("before writing code, load `examples/manager-example.json`"). Never auto-loaded. |

### 4.4 `scripts/` — one runtime capability, no build tooling

| Artifact | Rationale |
|---|---|
| `scripts/check_project.py` | **New.** Implements the mechanical halves of the 185 rules' `check` fields against a user's BGA project (e.g., Game.php line count, SQL in Game.php, direct `notifyAllPlayers` calls, state-table sanity, `.action.php` handler shape). This is the canonical "bundle a script when the agent reinvents the same logic each run" case — without it, every agent re-writes the same grep/parse logic on every review task. Designed per the scripts guidance: non-interactive (no TTY prompts), `--help`, JSON to stdout, diagnostics to stderr, distinct exit codes, idempotent, output capped by default. Python std-lib only. |

**Removed from v1 design — `scripts/validate-dist.py` and `scripts/dist-manifest.json`:**
- `validate-dist.py` validated the *package* — a build-time concern. Package validation is the source repo's job (`tooling/validator` stays in the repo, untouched) and a build step that runs before the dist is published; it is not a runtime artifact the agent needs.
- `dist-manifest.json` was generated metadata (commit hash, hashes). Generated metadata belongs in the build pipeline, not the shipped skill. The version identity lives in `SKILL.md` frontmatter `metadata` instead.

### 4.5 What is NOT in the runtime (and why)

| Artifact | Disposition |
|---|---|
| `README.md` | **Removed from the skill directory.** The spec defines no README; every token/file in the skill competes for context. Human-facing install notes, if wanted, are a build-time byproduct *outside* the skill directory (e.g., `dist/README.md` as a sibling), never inside it. |
| `skill.json`, `index.json` (Mercurio) | **Replaced by `SKILL.md`.** Not copied: their `$schema` references are dangling (`../schemas/*` does not exist) and their role (manifest + task map) is absorbed by frontmatter + router. The Mercurio package remains the canonical content source in the repo. |
| `docs/**`, `official-docs/**` | Source material, not runtime content. Rules already carry `source` citations to them. |
| `reference-projects/**` | See §4.7. |
| `tooling/**` | Dev/eval infrastructure. The validator stays in the repo and gates the *build*; the skill ships zero Python packages, only the one std-lib script. |
| `docs/evaluation/**`, benchmark harness, `.github/**`, pytest/CI files, caches, `.venv/` | Never runtime. |

### 4.6 Everything shipped is a copy of a frozen artifact

Every file in the dist is either (a) a **byte-identical copy** of a frozen, validator-passing artifact from `bga-senior-engineer-skill/` (rules, tasks, checklists, examples, references) or the repo `LICENSE`, or (b) **one of two new files** (`project-matrix.json`, `check_project.py`) distilled from repo sources. The build rule is: copy, never edit. This is what makes the skill *maintainable* — when the canon changes, the dist is regenerated, not hand-edited.

### 4.7 Reference-project handling (unchanged from v1, reaffirmed)

- **Do NOT bundle** the four reference games: ~1.25M tokens of code, ~120 MB of images, and `LICENCE_BGA` restricts redistribution to the BGA studio platform. Shipping them is a licensing exposure and a token impossibility.
- **Do ship** `project-matrix.json`: problem → exemplar project → file path → backup. The agent gets the pointer; the platform provides the code.

---

## 5. Routing Strategy — the Trigger Description

Per OpenCode, the agent sees available skills only as `name` + `description` in the `skill` tool's `<available_skills>` list; per the Agent Skills guidance, the description "carries the entire burden of triggering." Routing therefore has two layers:

### 5.1 Layer 1 — Description (trigger) [draft — design artifact, not yet written to SKILL.md]

```
description: >
  Production-grade engineering guidance for Board Game Arena (BGA) game
  implementations — state machines, action handlers, notifications,
  persistence, client architecture, undo/replay, animations, testing,
  and legacy migration. Use this skill when working on BGA game code:
  implementing or reviewing Game.php / states.inc.php / action handlers /
  Managers / Notifications, fixing BGA bugs, adding game features, or
  migrating Dojo-era code to modern BGA patterns — even if the user does
  not say "BGA". Not for non-BGA projects or for BGA studio
  setup/publishing procedures.
```

Design rationale (per optimizing-descriptions guidance):
- **Imperative**: "Use this skill when…"
- **User intent, not implementation**: mentions the artifacts a user would name (`Game.php`, `states.inc.php`) and the outcomes they want (fixing, adding, migrating, reviewing).
- **Errs toward triggering**: the explicit "even if the user does not say 'BGA'" clause covers indirect requests.
- **Boundary clauses** ("not for non-BGA projects…") to suppress near-miss false triggers.
- **≤ 300 chars** — kept short because the description is replicated into every agent's context (`orchestrator`, `worker`, `firstmate`, future).
- Validation plan (design-time): a ~20-query trigger eval set (10 should-trigger with varied phrasing/detail, 10 should-not near-misses, e.g., "set up a BGA studio account", "write a React component"), run 3× per query, threshold 0.5, per the optimizing-descriptions loop — executed in the packaging step, before SKILL.md is finalized.

### 5.2 Layer 2 — Router table (inside SKILL.md)

On activation, the agent classifies the request. The router is a compact table — task families, representative signals, and the exact bundle to load — derived from `index.json`'s keyword algorithm (highest keyword count wins; ties to first; unknown → clarify with the user or default to the full review task):

| Family | Tasks | Signals | Bundle loaded |
|---|---|---|---|
| Migrate | migrate-manager, migrate-state, migrate-notifications, migrate-client | extract, convert, migrate, legacy, Dojo, states.inc.php | task file + constitution + domain rules + pre-commit checklist |
| Review | review-action, review-manager, review-state-machine, review-notifications, review-persistence, review-full | review, audit, check, pre-release | task file + constitution + domain rules + checklist(s) |
| Debug | debug-session | bug, fix, trace, debug, diagnose | task file + constitution + pre-review checklist (rules lazy per failure type) |
| Feature | new-feature | add, feature, implement, phase | task file + constitution + phased rule bundles |
| Refactor | refactor-module | refactor, restructure, clean | task file + constitution + architecture + checklists |

**Why families:** per best practices, "too many options presented without a clear default" wastes turns; a 5-family router with a default is a decision aid, not a menu. The task files carry the fine-grained steps.

**Per-agent routing notes (design intent):**
- `orchestrator` (delegating): uses the router to pick the task family and dispatch; loads only the router + bundle.
- `worker` (executing): loads the assigned task bundle; follows the load protocol; runs the validation loop.
- `firstmate`/general assistants: triggered by the description on mixed BGA work; routes the same way.
- No agent-specific code paths exist in the skill — routing is uniform; this is what makes it reusable.

---

## 6. Loading Strategy — Progressive Disclosure, Stage by Stage

| Stage | Trigger | What enters context | Token cost |
|---|---|---|---|
| **0 — Discovery** | Session start (every agent) | `name` + `description` only | ~150 |
| **1 — Activation** | Task matches description | Full `SKILL.md` body: router, universal law, load protocol, validation loop, gotchas | ≤ 4,500 |
| **2 — Task load** | Router selects a task | `references/tasks/<task>.md` + `references/rules/constitution.json` + 1–4 domain rule files + 1 checklist | 8,000–16,000 |
| **3 — Lazy load** | A loaded file names another | examples, remaining references, `project-matrix.json`, `check_project.py` invocation | +1,000–3,500 per load |

**Discipline rules encoded in SKILL.md:**
1. Load `references/rules/constitution.json` on every task — it is the universal law; nothing else is universal.
2. Load exactly the files the task procedure names. Never browse `references/` speculatively.
3. One task bundle at a time. For `review-full` and `new-feature`, execute in the phases their procedures define (each phase ≈ 2–3 rule files) — never load all 12 rule files.
4. Load examples only when a task file says "before writing code, load…". Load `framework-reference.json`/`project-matrix.json` only for framework facts or exemplar lookup.
5. Gotchas and the validation loop run on every task regardless of family.

**Why this beats v1:** v1 kept Mercurio's "Tier 1 = rules load with every task" framing (index.json's `rules[]` arrays). The v2 design pushes even those loads behind the task file's explicit naming — the SKILL.md body is the only *automatic* load, everything else is conditional. This is the spec's "tell the agent when to load each file."

---

## 7. Expected Runtime Token Profile (measured)

Activation and per-task costs with actual artifact sizes (bytes ÷ 4):

| Load | Files | ~Tokens |
|---|---|---|
| **Stage 0 — Discovery** (every agent, every session) | 0 files; 1 description | **~150** |
| **Stage 1 — Activation** (SKILL.md body) | 1 | **≤ 4,500** (target ~3,500) |
| **Stage 2 — Task load (measured, worst member of each family)** | | |
| Migrate (migrate-manager: task + constitution + architecture + persistence + migration + checklist) | 6 | ~26,500 full / ~13,500 first phase |
| Review (review-full, phased: peak phase) | 3–4 | ~14,000–16,000 |
| Review (review-action) | 4 | ~13,200 |
| Debug (debug-session) | 3 | ~7,900 |
| Feature (new-feature, phased) | 3 per phase | ~13,000 per phase |
| Refactor (refactor-module) | 5 | ~16,300 |
| **Stage 3 — Lazy loads** | 1–3 | +1,000–3,500 each |
| **Corpus (total, never fully loaded)** | 44 | ~110,000 |

**Honest budget statement (replaces v1's and the frozen spec's 3K-per-task claim):** the 3,000-token per-task budget in `runtime-skill-architecture.md` §2 was planning-era arithmetic (it assumed ~300 tokens/rule file; measured average is 5,400). The v2 design does not promise 3K. It minimizes *automatic* cost (150 + ≤4.5K) and keeps *conditional* cost as low as the frozen canon allows by loading 3–6 files of 44 per task. Cutting rule content is not an option — the 12 rule files are frozen v1.1 law, verbatim by mandate.

---

## 8. Reusable, Cross-Project Deployment

- **Install locations (no code changes needed):** `.opencode/skills/bga-senior-engineer/` (project), `~/.config/opencode/skills/` (global), `.claude/skills/`, `.agents/skills/` — the same directory works for all because it is a spec-compliant skill, not a config plugin.
- **Zero environment coupling:** no absolute paths anywhere in the skill (the v1-era root `opencode.json` external-directory permission for a specific machine path is repo/session config, not skill content). All file references are relative to the skill root, one level deep, per the spec.
- **Project-agnostic instruction:** every task file already addresses "the BGA project under review" — they were written for arbitrary projects; the dist changes nothing about their wording. `bga-mercurio` is a *deployment target*, not a dependency.
- **Multi-agent:** `orchestrator`/`worker`/`firstmate` consume the same skill; the router is the shared interface. Per-agent permission control (e.g., OpenCode `permission.skill.*`) is platform config, applied at install time, not baked into the skill.
- **Upgrade path:** regenerating the dist from the source repo when the canon changes is a build action; installed copies are replaced wholesale.

---

## 9. Comparison with the Previous Design (v1 → v2)

### 9.1 Removed (over-packaging fixes)

| v1 artifact | v2 disposition | Why |
|---|---|---|
| `references/official/` — 11 distilled BGA docs (~15K tokens) | **Cut entirely** | Documentation packaging. Framework facts already live in `rules/` + `framework-reference.json`; deep docs belong at the platform, fetched on demand. Violates "moderate detail" and "add what the agent lacks." |
| `README.md` inside the skill | **Cut** (optional sibling doc at `dist/` level) | Spec defines no README; every shipped file competes for attention. |
| `scripts/validate-dist.py` | **Cut** (build step stays in repo) | Package self-validation is a build concern; the repo validator (untouched) gates builds. |
| `scripts/dist-manifest.json` | **Cut** | Generated metadata is build-pipeline output, not runtime. Identity moves to `SKILL.md` frontmatter `metadata`. |
| `assets/` holding rules/ and prompts/ | **Reorganized** | `rules/` and `tasks/` are *knowledge* → `references/`; `assets/` keeps only templates/validation data (checklists, examples) per spec vocabulary. |
| Skill.json/index.json re-encoding as "manifest" | **Replaced** | SKILL.md is instructions + router, not a manifest. Manifest concepts (identity, version) collapse into frontmatter. |

### 9.2 Kept (already strong)

| v1 element | Verdict |
|---|---|
| Frozen artifacts shipped byte-identical; zero drift from the certified canon | **Strong — kept.** |
| Reference projects excluded, licensing-driven | **Strong — kept and reaffirmed.** |
| `project-matrix.json` gap-fill | **Strong — kept** (the only distilled addition besides the checker script). |
| Honest, measured token reporting | **Strong — kept and refined** into stage-based loads. |
| Duplicate/merge findings (handbook ↔ official HTML ↔ framework reference; spec/impl reference-name drift; dangling `$schema`) | **Kept** (condensed in §11). |

### 9.3 What v1 got wrong at the philosophical level

v1 thought in terms of *packaging documentation*: it re-mapped a Mercurio package into an OpenCode directory and asked "which documents ship?" v2 thinks in terms of *designing a capability*: it asks "what must the agent know at each stage of disclosure, and what is the smallest artifact set that delivers it?" The strongest symptom of the v1 mindset was shipping 15K tokens of BGA documentation into a runtime skill; the strongest correction is that the skill's only automatic load is a ≤4.5K router whose every reference is conditional.

---

## 10. Mapping to OpenCode and Agent Skills Guidance

| Guidance (source) | Requirement | This design |
|---|---|---|
| OpenCode — frontmatter | `name` required, lowercase-hyphen, matches directory; `description` 1–1,024 chars; unknown fields ignored | `bga-senior-engineer` matches `dist/bga-senior-engineer/`; description ≤ 300 chars; only `name`/`description`/`license`/`compatibility`/`metadata` used |
| OpenCode — discovery | `**/SKILL.md` in `.opencode/skills`, `~/.config/opencode/skills`, `.claude/skills`, `.agents/skills` | Spec-conformant directory; identical package installs anywhere |
| OpenCode — tool description | `<available_skills>` shows name + description; that is the trigger surface | Description drafted as the trigger layer (§5.1) |
| OpenCode — permissions | `permission.skill.*` per agent | No baked-in permission logic; platform-level, applied at install |
| Spec — progressive disclosure | metadata ≈100 → instructions <5K → resources on demand | Stage 0/1/2/3 model (§6); SKILL.md ≤ 4.5K; every other file conditional |
| Spec — SKILL.md size | Under 500 lines / 5,000 tokens | Target 350 lines / 3.5K–4.5K tokens |
| Spec — references/ | Focused files, loaded on demand, smaller = better | 12 rule files + 13 task files + 4 lookup files, all conditional |
| Spec — scripts/ | Self-contained, non-interactive, helpful errors | `check_project.py` per agentic-script requirements (§4.4) |
| Spec — file references | Relative, from skill root, one level deep | All references relative and shallow |
| Best practices — coherent units | One capability per skill | Single `bga-senior-engineer` skill, 5 task families, one corpus |
| Best practices — context wisdom | "Add what the agent lacks, omit what it knows"; cut anything the agent wouldn't get wrong | Official-doc distillation cut; gotchas kept in SKILL.md |
| Best practices — defaults not menus | Pick a default, mention alternatives | Router families with default (full-review / clarify) |
| Best practices — validation loops | Do → validate → fix → re-validate | Checklists as validators + `check_project.py` as mechanical validator |
| Best practices — gotchas | Corrections the agent will otherwise make | Dedicated SKILL.md section (§4.1) |
| Best practices — templates | Long/conditional formats → `assets/`, referenced with conditions | Examples + checklists in `assets/`, named by task files |
| Optimizing descriptions — trigger eval | ~20 queries, 3 runs, threshold 0.5, train/validation split | Required step of the packaging phase (§5.1) |
| Using scripts — agentic design | Non-interactive, `--help`, JSON out, exit codes, idempotent, capped output | Contract for `check_project.py` |

---

## 11. Duplication and Hygiene Findings (runtime-relevant subset)

1. **Handbook ↔ official HTML ↔ `framework-reference.json`** — three condensations of the same BGA framework material. Runtime ships only the smallest: `framework-reference.json`. Handbook and HTML remain repo/ platform sources.
2. **Spec/implementation reference-name drift** — `runtime-skill-architecture.md` §7.1 names `reference-project-matrix.json` / `anti-patterns.json` / `migration-mapping.json`; the implemented package ships `architecture-reference` / `migration-reference` / `bga-framework-reference`. Runtime adopts the **implemented** names (release doc §8: runtime prevails over planning) and adds the missing `project-matrix.json`. `anti-patterns.json` is not created (no content exists).
3. **Dangling `$schema`** in Mercurio `skill.json`/`index.json` (`../schemas/*` does not exist) — irrelevant to the OpenCode package (frontmatter has no `$schema`); flagged for a future Mercurio-package patch, out of this task's constraints.
4. **Evaluation/planning doc clusters** (MS-10 reports, audit histories, implementation reports) — flagged in v1; they are Development/Evaluation Only and never enter the runtime. No action needed.

---

## 12. Verdicts

### 12.1 Verdict on the previous design (v1)

**Directionally correct, architecturally incomplete.** v1 correctly insisted on verbatim frozen content, measured token honesty, reference-project exclusion, and the `project-matrix.json` gap-fill. But it remained a *documentation packaging plan*: it shipped a 15K-token distillation of official BGA documentation, a README, a package validator, and generated metadata as runtime artifacts, and it treated `SKILL.md` as a manifest re-encode rather than as the skill's router and protocol. It optimized "which documents belong in the directory" instead of "what must the agent know at each stage of disclosure." Its token profile (§8.2) was honest about corpus size but did not separate the *automatic* activation cost from conditional loads.

### 12.2 Verdict on the redesigned architecture (v2)

**Exemplary first-class Agent Skill.** v2 is a capability design: one coherent skill; a description engineered for trigger reliability; a router organized by task family; a SKILL.md that is the only automatic load (≤4.5K tokens, well under the spec's 5K guidance); every other artifact conditional with explicit load conditions; `references/` carrying only distilled knowledge; `assets/` carrying only templates and validation data; `scripts/` carrying one genuine agent capability and zero build tooling; and no documentation shipped that the rules do not already distill. It deploys identically to OpenCode, Claude, and Agents-compatible clients, works across `orchestrator`/`worker`/`firstmate`/future agents, and is regenerable from the frozen canon by copy. Its honest token profile trades the fiction of a 3K budget for a real one: ~150 tokens discovery, ≤4.5K activation, 8–16K per task, corpus never fully loaded.

### 12.3 Remaining open design questions

1. **`check_project.py` scope and schedule** — implement the mechanical rule checks now (full) or ship a minimal version (Game.php/SQL/notify scans) in v1.1 and deepen later? Recommended: minimal for the first release, iterated via execution traces (per evaluating-skills guidance).
2. **Trigger description wording** — the draft in §5.1 must pass the ~20-query trigger eval before SKILL.md is written; wording will likely shift by one iteration.
3. **`project-matrix.json` schema** — entries keyed by problem (as the frozen architecture specified) vs. by project (easier to browse). Recommended: problem-keyed, with a project index inside the same file.
4. **Versioning identity** — `metadata` in frontmatter: dist version `1.1.0` mirroring the frozen runtime, plus source commit hash. Confirm this is the desired identity for the package.
5. **Build automation** — whether the dist is produced by a scripted build (copy + generate matrix + generate script) or by a documented manual procedure in the first release. Recommended: scripted build in the source repo, run in CI, publishing to `dist/`.

### 12.4 Confirmation

**No runtime packaging has occurred.** No `SKILL.md` was written, no `dist/` directory was created, no files were moved or modified, and no implementation, benchmark, validator, or evaluation artifact was touched. This document is design only; packaging proceeds only after this design is approved.
