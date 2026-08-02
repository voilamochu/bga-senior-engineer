---
name: bga-senior-engineer
description: >-
  Engineering guidance for Board Game Arena (BGA) game implementations:
  state machines, action handlers, notifications, persistence, client
  code, undo/replay, testing, and legacy migration. Use when working on
  BGA game code — Game.php, states.inc.php, Managers, Notifications, or
  Dojo-era migrations — including review, debug, feature, and refactor
  work, even if the user does not say "BGA". Not for non-BGA projects or
  BGA studio setup.
license: MIT
compatibility: >-
  Any Agent Skills-compatible client (OpenCode, Claude Code, Agents). Read
  access to the target BGA project directory is required. The optional
  checker script needs Python 3.8+.
metadata:
  package-version: 1.1.0
  runtime-spec: v1.1
  rules: "185"
  source: https://github.com/voilamochu/bga-senior-engineer
---

# BGA Senior Engineer

## Purpose

This skill provides production-grade engineering guidance for Board Game
Arena (BGA) game implementations. It encodes the frozen Runtime
Specification v1.1 — 185 engineering rules across 12 domains — plus 13 task
procedures and 3 self-validation checklists. It turns BGA engineering
judgment into a loadable capability: you bring the project, the skill brings
the canon.

The skill is **read-only**. You never modify the skill's own files; all work
happens in the user's project.

## When to use this skill

Use this skill when the task involves **BGA game implementation code**:

- Implementing or reviewing `Game.php`, `states.inc.php`, action handlers,
  Managers, Notifications, or the client layer.
- Migrating Dojo-era code to modern BGA patterns (ES modules, BgaCards).
- Debugging BGA game logic, state transitions, or notification bugs.
- Adding a game feature, or refactoring a module to canonical standards.
- Running a full pre-release audit.

Use it even when the user does not name BGA, as long as they reference BGA
artifacts or outcomes: `Game.php`, `states.inc.php`, `notifyAllPlayers`,
`BgaCards`, `dbmodel.sql`, "the game's state machine", "extract a Manager
from Game.php".

## When NOT to use this skill

Do **not** use this skill for:

- Non-BGA projects (general web, mobile, or backend development).
- BGA studio administration: account setup, project creation, deployment,
  publishing, license decisions, or translations workflow.
- Game-design questions that do not touch implementation (rules balancing,
  player-count design).
- BGA framework questions answerable from platform documentation alone with
  no engineering judgment required.

If the request does not match, answer directly or tell the user what this
skill covers. Do not force the skill onto unrelated work.

## Universal engineering principles

Apply these on every task, before and while loading domain files:

1. **Constitution is law.** Load `references/rules/constitution.json` first,
   on every task, before any other file. Its rules are priority 1 and
   immutable; no runtime rule may weaken them.
2. **Rule IDs are citable.** Cite a rule ID (e.g., ARCH-001, PERS-003) for
   normative recommendations and for review findings. Ordinary explanation
   does not need a citation.
3. **Load only what is named.** The routing tables below name the exact
   files for each task. Load nothing else speculatively.
4. **One bundle at a time.** Never load all 12 rule files. If a task is
   phased, load only the current phase's files.
5. **The canon prevails.** If a task file, example, or reference conflicts
   with a rule, the rule wins. If a user's project conflicts with a rule,
   document it with the rule ID and flag it — do not silently bend the rule.

## Routing strategy

Classify the request by matching its terms against the trigger signals in
the family tables below. Route to the family that clearly dominates. If two
families remain genuinely ambiguous after classification, ask one concise
question naming them — "Migrate or review?" — and route on the answer. Only
when clarification would not materially improve routing (a generic BGA
audit), default to the **review-full** task. If even the domain is unclear,
ask which family applies.

Conventions for the tables: procedures live in `references/tasks/<task>.md`,
rules in `references/rules/<domain>.json`, checklists in
`assets/checklists/<name>.json`, examples in `assets/examples/<name>.json`.
Examples load before writing code; `check_project.py` runs after the work;
lazy rules load only when their stated condition occurs.

After routing, load the task procedure **before doing any work** — it
contains the step-by-step workflow, the exact rules to follow, and any phase
structure.

### Migrate

Use when extracting or converting legacy code to modern structure.

| Task | Signals | Procedure | Rule bundle | Checklist | Load only when |
|---|---|---|---|---|---|
| migrate-manager | extract, manager, legacy Game.php, Game.php refactor | migrate-manager.md | constitution, architecture | pre-commit | persistence (extracting SQL), migration (planning sequence); manager-example, model-example; script after extraction |
| migrate-state | states.inc.php, convert states, state migration | migrate-state.md | constitution, state-machine | pre-commit | migration (legacy state patterns); state-example |
| migrate-notifications | notifyAllPlayers, centralize notifications, extract notifications | migrate-notifications.md | constitution, notifications | pre-commit | migration (wrapping legacy notify calls); notification-example |
| migrate-client | dojo, es module, bgacards, client migration, javascript | migrate-client.md | constitution, client | pre-commit | migration (Dojo module conversion); client-manager-example |

### Review

Use when auditing code against the canon.

| Task | Signals | Procedure | Rule bundle | Checklist | Load only when |
|---|---|---|---|---|---|
| review-action | review, action, handler, audit, check | review-action.md | constitution, actions | pre-review | undo-replay (undoable-state mutations); action-example; script (mechanical scans) |
| review-manager | review, manager, class, audit | review-manager.md | constitution, architecture | pre-review | persistence (DB access patterns); manager-example |
| review-state-machine | review, state machine, transition, zombie | review-state-machine.md | constitution, state-machine | pre-review | state-example (state class structure) |
| review-notifications | review, notification, i18n, notify | review-notifications.md | constitution, notifications | pre-review | synchronization (spectator/reconnect paths); notification-example |
| review-persistence | database, schema, globals, sql, review | review-persistence.md | constitution, persistence | pre-review | undo-replay (undo log design); script (schema/SQL scans) |
| review-full | pre-release, full audit, complete review, release | review-full.md | constitution | pre-commit, pre-review, pre-release | follow the 6 phases in the task; load only each phase's rule files, examples, and references; script in the architecture and validation phases |

### Debug

Use when locating and fixing a defect.

| Task | Signals | Procedure | Rule bundle | Checklist | Load only when |
|---|---|---|---|---|---|
| debug-session | bug, fix, trace, debug, diagnose, error | debug-session.md | constitution | pre-review | the domain rule matching the localized failure — actions (handler bugs), notifications (delivery bugs), state-machine (transition bugs), architecture (boundary bugs), persistence (database bugs); script to verify the fix |

### Feature

Use when adding new game functionality end-to-end.

| Task | Signals | Procedure | Rule bundle | Checklist | Load only when |
|---|---|---|---|---|---|
| new-feature | add, feature, new, implement, create, phase | new-feature.md | constitution | pre-commit | the task's 4 phases — design (architecture, state-machine), implementation (persistence, actions), integration (notifications, client), undo (undo-replay); each phase's examples before writing code; framework-reference (framework API facts) |

### Refactor

Use when restructuring an existing module without changing behavior.

| Task | Signals | Procedure | Rule bundle | Checklist | Load only when |
|---|---|---|---|---|---|
| refactor-module | refactor, restructure, clean, rewrite, improve | refactor-module.md | constitution, architecture | pre-commit, pre-review | actions/notifications/persistence/state-machine when refactoring those areas; manager-example |

## Load protocol

1. **Constitution first.** Load `references/rules/constitution.json` at the
   start of every task — before the task file, before any domain rules.
2. **Task file next.** Load the routed `references/tasks/<task>.md` and
   follow it. It names the exact rule files, examples, and checklists.
3. **Load only what is named.** Never browse `references/` or `assets/`
   speculatively. If a task file does not name a file, do not load it.
4. **Phase discipline.** `review-full` and `new-feature` are phased: load
   only the current phase's rule files, then let them leave focus before
   loading the next phase.
5. **Conditional loads.** Load a lazy rule, example, or reference only when
   the task file or a routing table states the triggering condition (e.g.,
   "load `persistence` when extracting SQL"). If the condition is absent,
   skip it.
6. **Run, then validate.** Production work is not complete when the code is
   written; it is complete when the checklist passes (see Validation loop).

## Reference loading instructions

Load each file only when its condition holds. These are the only files the
skill may load:

| File | Load when |
|---|---|
| `references/rules/constitution.json` | Every task, first, unconditionally. |
| `references/rules/<domain>.json` | A routing table row or the task file names that domain. |
| `references/tasks/<task>.md` | Routing selects that task — before doing any work. |
| `references/framework-reference.json` | The task requires BGA framework API or lifecycle facts not covered by the rules. |
| `references/architecture-reference.json` | Deciding component boundaries or ownership for a migration or review. |
| `references/migration-reference.json` | Mapping a legacy construct to its modern equivalent. |
| `references/project-matrix.json` | Choosing which exemplar reference-project pattern to consult. |
| `assets/checklists/<name>.json` | A routing table row names it — run it after producing output. |
| `assets/examples/<name>.json` | A task file says "before writing code, load <name>" — never otherwise. |
| `scripts/check_project.py` | Mechanical verification is needed: line counts, SQL-in-Game.php scans, direct `notifyAllPlayers` scans, state-table sanity. Run `python3 scripts/check_project.py --help` first. |

## Validation loop

Every task ends with validation. Do not skip it.

1. Produce the work per the task procedure.
2. Run the checklist named in the routing table (load it now if not already
   loaded): `python3 scripts/check_project.py` for mechanical checks, then
   walk the checklist items.
3. Every checklist item must pass. On failure, fix the violation, cite the
   rule ID, and re-run the checklist.
4. Only when all items pass, declare the task complete.
5. If an item cannot pass without changing the user's design intent,
   document it as a finding with the rule ID and escalate (see Escalation).

## Stop conditions

Declare completion only when **both** the family stop condition and the
checklist pass:

- **Migrate:** source and target behavior are equivalent; no SQL or domain
  logic remains in `Game.php`; the checklist passes.
- **Review:** every finding is documented with rule ID, severity, and fix;
  the checklist passes; no unresolved critical finding remains un-escalated.
- **Debug:** root cause is identified and reproduced; the fix is applied;
  verification passes.
- **Feature:** all phases are complete; the feature is wired through
  notifications, client, and undo; the checklist passes.
- **Refactor:** the module matches its owning rules; behavior is preserved;
  both checklists pass.

## Common failure modes

- **Loading the whole canon.** All 12 rule files are never needed at once.
  If you have loaded more than the routing table names, stop and unload.
- **Writing code before the example.** If the task file names an example,
  load it before writing — the example is the canonical shape.
- **Skipping the constitution.** Every task starts with
  `references/rules/constitution.json`. There are no exceptions.
- **Treating optional as mandatory (or the reverse).** Lazy files load only
  on their stated condition; checklist items are never optional.
- **Mixing tasks.** Do not run two routing families in one load (e.g.,
  reviewing while migrating). Finish one task before starting another.
- **Editing the skill.** The skill's files are read-only. All edits target
  the user's project.
- **Declaring victory at code-complete.** The validation loop is the
  definition of done, not the last edited file.

## High-value gotchas

The corrections below are the ones agents get wrong most often. The rule
files are the authority; these are the reminders:

- `Game.php` stays under 300 lines, orchestration only — no SQL, no domain
  logic (ARCH-001).
- Notification payloads split public vs. private; never include hidden
  information in the public payload (NOTF rules).
- Every user-facing string passes through `clienttranslate()` (NOTF rules).
- State args are plain serializable data; every state needs a zombie-safe
  path (STAT rules).
- Action handlers validate input, then mutate inside a transaction — never
  in `Game.php` (ACTN rules).
- Undo works via log, checkpoint, and LIFO reversal — every mutation is
  logged (UNDO rules).
- All database access uses parameterized queries (PERS rules).
- Each table is owned by exactly one Manager (ARCH-003).
- Dojo-era code is migrated, not extended (MIGR rules).
- Never commit secrets, keys, or credentials (CORE-013).

## Escalation rules

- **No routing match:** list the five families (Migrate, Review, Debug,
  Feature, Refactor) and ask the user which applies.
- **Rule conflict:** the constitution (priority 1) prevails; record the
  conflict in your report.
- **Framework uncertainty:** load `references/framework-reference.json`; if
  still unresolved, ask rather than guess.
- **Intentional deviation:** the user's project deliberately violates a
  rule — document it with the rule ID and flag it for a human decision.
- **Security risk:** secrets, injection, or hidden-information leaks — stop
  work and escalate immediately.
- **Stale skill content:** if the skill appears outdated relative to the
  user's project, do not edit the skill; report it.
