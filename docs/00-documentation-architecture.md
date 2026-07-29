# BGA Senior Engineer — Documentation Architecture

**Purpose:** Define the long-term structure of the knowledge base so that content is discoverable, dependencies are clear, and the repository scales without becoming a flat dump of markdown files.

**Status:** Live document — amend when a new document is proposed or an existing one is split.

---

## Table of Contents

- [1. Folder Layout](#1-folder-layout)
- [2. Numbering Scheme](#2-numbering-scheme)
- [3. Document Type Definitions](#3-document-type-definitions)
- [4. Document Catalog](#4-document-catalog)
  - [4.1 Foundation](#41-foundation)
  - [4.2 Engineering Standards](#42-engineering-standards)
  - [4.3 Architecture](#43-architecture)
  - [4.4 Pattern Catalog](#44-pattern-catalog)
  - [4.5 Playbooks](#45-playbooks)
  - [4.6 Checklists](#46-checklists)
- [5. Dependency Graph](#5-dependency-graph)
- [6. Splitting Guidelines](#6-splitting-guidelines)
- [7. Directory Tree](#7-directory-tree)

---

## 1. Folder Layout

```
docs/
├── 00-documentation-architecture.md    ← this file
├── foundation/                          ← Canonical source documents (official framework + project analysis)
├── standards/                           ← Engineering standards derived from the references
├── architecture/                        ← System architecture documents
├── patterns/                            ← Reusable pattern descriptions
├── playbooks/                           ← Step-by-step procedures
└── checklists/                          ← Verifiable item lists
```

Each folder contains a `README.md` if the folder holds more than four documents, to serve as a local index.

---

## 2. Numbering Scheme

Only **standards** are numbered. Everything else uses descriptive filenames without numbers.

| Section | Numbered? | Rationale |
|---|---|---|
| `foundation/` | No | Stable reference material; ordering is by dependency, not sequence |
| `standards/` | Yes | Must be read in order — each standard builds on those before it |
| `architecture/` | No | One document per architectural concern; no strict sequence |
| `patterns/` | No | Independent documents; readers jump to the pattern they need |
| `playbooks/` | No | Task-specific; read only when performing the task |
| `checklists/` | No | Task-specific; used during specific phases |

**Standards numbering:**

```
01-project-setup-and-structure
02-notification-patterns
03-state-machine-patterns
04-database-patterns
05-action-and-flow-patterns
06-undo-patterns
07-client-architecture
08-reconnect-and-spectator
09-performance
10-debugging-and-testing
```

---

## 3. Document Type Definitions

| Type | Icon | Description | Length |
|---|---|---|---|
| **Reference** | REF | Authoritative description of an existing system or API. Assumes no prior knowledge but does not teach step by step. | 200-800 lines |
| **Engineering Standard** | STD | Prescriptive rules and conventions that a project must follow. Includes dos/don'ts, trade-off analysis, and a canonical approach. | 300-1500 lines |
| **Tutorial** | TUT | Step-by-step walkthrough building something from scratch. Used only for onboarding. | 200-600 lines |
| **Pattern Catalog** | PAT | A collection of reusable solutions to recurring problems. Each pattern has context, problem, solution, trade-offs, and reference implementations. | 50-200 lines per pattern |
| **Playbook** | PLY | A linear procedure to accomplish a specific task (e.g., "Publish a game to beta"). Minimal explanation, maximum action. | 100-300 lines |
| **Checklist** | CHK | Verifiable items to confirm before a milestone. Binary pass/fail per item. | 50-150 lines |

---

## 4. Document Catalog

### 4.1 Foundation

| # | Filename | Type | Purpose | Dependencies | Prereqs | Size | Audience | Expand? |
|---|---|---|---|---|---|---|---|---|
| F1 | `bga-developer-handbook.md` | REF | Official BGA framework reference covering the complete game lifecycle, API surface, and production requirements. | None | None | ~3000 | All | Should be split into a `foundation/handbook/` folder with one file per chapter |
| F2 | `bga-ai-implementation-reference.md` | REF | Engineering reference extracted from the official bot/AI documentation. Covers bot player architecture, API, limitations, and patterns. | None | F1 | ~500 | Backend devs | Single file |
| F3 | `reference-project-analysis.md` | REF | Comprehensive analysis of all four official BGA reference projects (Agricola, Ark Nova, Arnak, Earth). Subsystem ratings, architectural lineage, outdated patterns. | None | None | ~300 | All | Single file; serves as the master index into the reference projects |

### 4.2 Engineering Standards

| # | Filename | Type | Purpose | Dependencies | Prereqs | Size | Audience | Expand? |
|---|---|---|---|---|---|---|---|---|
| S1 | `project-setup-and-structure` | STD | Canonical project layout, file naming, namespace conventions, module organization, build tooling. | None | F1, F3 | ~400 | All | Single file |
| S2 | `notification-patterns` | STD | Complete notification architecture: public/private, naming, payload, i18n, sequencing, undo, reconnect, performance. | None | F1, F3 | ~1435 | All | Should be split: server-side patterns, client-side patterns, undo interactions → `standards/notifications/` |
| S3 | `state-machine-patterns` | STD | Game state machine design: state types, transitions, args, action methods, private states, flow engines, the `_no_notify` flag. | None | F1, F3 | ~800 | Backend, full-stack | Single file initially; may split if flow engine coverage grows |
| S4 | `database-patterns` | STD | DB schema design, custom tables vs. framework tables, query patterns, globals, JSON columns, migration strategy, caching. | None | F1 | ~500 | Backend | Single file |
| S5 | `action-and-flow-patterns` | STD | Action handler architecture, the Engine pattern (Seq/Or/Xor/Parallel/Leaf), atomic action classes, card listener hooks, flow injection. | S3 | F1, F3 | ~1000 | Backend | Should split: `engine-patterns` and `action-handler-patterns` when engine coverage exceeds 600 lines |
| S6 | `undo-patterns` | STD | Undo architecture: gamelog cancellation, command pattern, log table design, checkpoint/step granularity, partial-turn confirmation, cross-player invalidation. | S2, S4 | F1, F3 | ~800 | Backend | Single file |
| S7 | `client-architecture` | STD | Client-side code organization: Manager pattern, state classes, notification handlers, animation architecture, SCSS organization. | S2 | F3 | ~600 | Frontend | Single file |
| S8 | `reconnect-and-spectator` | STD | Reconnection flow, getAllDatas contract, notification replay, spectator data filtering, read-only mode. | S2 | F1, F3 | ~400 | Full-stack | Single file |
| S9 | `performance-patterns` | STD | Notification deltas, payload minimization, animation skipping, DB query optimization, archive storage considerations. | S2, S4 | F3 | ~400 | Backend | Single file |
| S10 | `debugging-and-testing` | STD | Debug traits, seed loading, studio tools, bug report automation, test strategy, replay testing. | None | F1 | ~400 | All | Single file |

### 4.3 Architecture

| # | Filename | Type | Purpose | Dependencies | Prereqs | Size | Audience | Expand? |
|---|---|---|---|---|---|---|---|---|
| A1 | `project-structure-overview` | REF | The high-level directory layout for a BGA project. Explains every file and folder that the framework expects. | None | F1 | ~200 | Newcomers | Single file |
| A2 | `engine-architecture` | REF | Deep dive into the custom Engine pattern (decision tree). Covers each node type, lifecycle, saving/loading, card integration. | S5 | F3 | ~500 | Backend | Single file |
| A3 | `client-data-model` | REF | How game data flows from DB → PHP → client. Covers getAllDatas, args, notification payloads, client-side caches. | A1 | F1, F3 | ~300 | Full-stack | Single file |

### 4.4 Pattern Catalog

| # | Filename | Type | Purpose | Dependencies | Prereqs | Size | Audience | Expand? |
|---|---|---|---|---|---|---|---|---|
| P1 | `card-implementation-patterns` | PAT | How to structure card definitions: per-class files vs. data-driven, card listeners, reaction hooks, stateless vs. stateful cards. | S5 | F3 | ~400 | Backend | Should become a `patterns/cards/` folder when >3 card patterns emerge |
| P2 | `simultaneous-turn-patterns` | PAT | Private state machines, action command queues, locking, cross-player invalidation. | S3, S5, S6 | F3 | ~500 | Backend | Single file |
| P3 | `multi-step-action-patterns` | PAT | Breaking complex actions into sequences, client states, intermediate confirmation, undo boundaries. | S5, S6 | F3 | ~350 | Backend | Single file |
| P4 | `draft-patterns` | PAT | Draft implementation patterns: simultaneous, async, snake, seeded, living hand. | S3 | F3 | ~300 | Backend | Single file |
| P5 | `spatial-board-patterns` | PAT | Grid systems, hex maps, coordinate systems, drop zones, placement validation. | S7 | F3 | ~400 | Frontend + backend | Should become a `patterns/spatial/` folder when board types multiply |
| P6 | `resource-management-patterns` | PAT | Resource tracking, income/expense, conversion mechanics, caps, overflow handling. | S4 | F3 | ~300 | Backend | Single file |
| P7 | `scoring-patterns` | PAT | Scoring architectures: incremental scoring, end-game scoring, tiebreakers, score breakdown display. | S2, S7 | F3 | ~350 | Full-stack | Single file |
| P8 | `expansion-integration-patterns` | PAT | Adding content without breaking core: feature flags, card type extension, map/board variants. | S5 | F3 | ~300 | Backend | Single file |

### 4.5 Playbooks

| # | Filename | Type | Purpose | Dependencies | Prereqs | Size | Audience | Expand? |
|---|---|---|---|---|---|---|---|---|
| PB1 | `new-project-from-template` | PLY | Creating a new BGA project from the skeleton template and configuring it for development. | None | F1 | ~150 | Newcomers | Single file |
| PB2 | `studio-deployment` | PLY | Deploying a game to BGA studio, managing versions, syncing files. | None | F1 | ~100 | All | Single file |
| PB3 | `production-release` | PLY | Steps to release from studio to production: beta, testing, bug fixing, deployment. | None | F1 | ~200 | Project leads | Single file |
| PB4 | `migrating-to-modern-framework` | PLY | Migrating from Dojo classes to ES modules, from Game.js to bga sub-components, from PHP arrays to JSON configs. | None | F1 | ~200 | All | Single file |

### 4.6 Checklists

| # | Filename | Type | Purpose | Dependencies | Prereqs | Size | Audience | Expand? |
|---|---|---|---|---|---|---|---|---|
| C1 | `pre-release-checklist` | CHK | Items to verify before releasing a game: notifications, undo, reconnect, spectators, performance, translations. | S2, S6, S8, S9 | F1 | ~100 | Project leads | Single file |
| C2 | `notification-audit-checklist` | CHK | Per-notification verification: i18n, private data, log message, payload design, handler idempotency. | S2 | S2 | ~50 | Backend | Single file |
| C3 | `state-machine-audit-checklist` | CHK | Per-state verification: transitions, args, action, zombie handling, _no_notify, description. | S3 | S3 | ~50 | Backend | Single file |

---

## 5. Dependency Graph

### 5.1 Reading Order

Documents should be read in the following dependency order. Arrows mean "depends on" or "prerequisite for."

```
F1 (Developer Handbook)
  │
  ├──→ F2 (AI Reference)
  │
  └──→ F3 (Project Analysis)
         │
         ├──→ A1 (Project Structure)
         │      │
         │      └──→ A3 (Client Data Model)
         │
         ├──→ S1 (Project Setup & Structure)
         │
         ├──→ S2 (Notification Patterns) ──→ S8 (Reconnect & Spectator)
         │      │                               │
         │      └──→ S9 (Performance)           └──→ C1 (Pre-Release Checklist)
         │
         ├──→ S3 (State Machine Patterns) ──→ S5 (Action & Flow)
         │      │                               │
         │      └──→ P2 (Simultaneous Turns)    ├──→ P1 (Card Implementation)
         │                                       ├──→ P3 (Multi-Step Actions)
         │                                       ├──→ P8 (Expansion Integration)
         │                                       └──→ A2 (Engine Architecture)
         │
         ├──→ S4 (Database Patterns) ──→ S6 (Undo Patterns)
         │                                │
         │                                └──→ P2 (Simultaneous Turns)
         │
         ├──→ S7 (Client Architecture) ──→ P5 (Spatial Boards)
         │
         └──→ S10 (Debugging & Testing)

P4 (Draft Patterns) ──→ independent; depends only on F3
P6 (Resource Management) ──→ independent; depends only on F3
P7 (Scoring Patterns) ──→ depends on S2, S7

PB1-PB4 ──→ independent; depend only on F1
C1 ──→ depends on S2, S6, S8, S9
C2 ──→ depends on S2
C3 ──→ depends on S3
```

### 5.2 Recommended Reading Tracks

| Role | Track | Documents |
|---|---|---|
| Newcomer (onboarding) | Foundation + Basics | F1, F3, A1, S1 |
| Backend developer | Full backend track | F3, S3, S4, S5, S6, S2, P1, P2, P3, P4, P8, A2 |
| Frontend developer | Client track | F3, S7, P5, P7 |
| Full-stack developer | Complete track | The full list in order |
| Reviewer / QA | Audit track | S10, C1, C2, C3 |
| Project lead | Management track | F1, F3, PB3, C1 |

---

## 6. Splitting Guidelines

### 6.1 When to Split

A document should be split when it exceeds approximately **1000 lines** of prose (excluding code blocks), or when a single document covers three or more independently maintainable concerns.

### 6.2 How to Split

**Case 1: Length** — split into a folder with an `index.md` and chapter files:

```
standards/02-notifications/
├── index.md               ← navigation + overview + canonical approach summary
├── 01-server-patterns.md
├── 02-client-patterns.md
├── 03-undo-interactions.md
├── 04-performance.md
└── 05-reconnect-and-spectator.md
```

The `index.md` should contain the document's original abstract, dependency metadata, and a table of contents linking to the chapters. Each chapter file should be 200-600 lines.

**Case 2: Multiple concerns** — split into separate documents under the same parent section:

```
standards/
├── 02-notification-patterns.md     ← (server + client patterns only)
├── 05-undo-patterns.md             ← (includes undo interactions)
└── 08-reconnect-and-spectator.md   ← (includes reconnect + spectator)
```

Remove cross-document duplication by referencing the standard that defines the shared concept.

### 6.3 Current Documents Nearing the Split Threshold

| Document | Lines | Recommendation |
|---|---|---|
| `standards/notification-patterns.md` | 1435 | Split into `standards/notifications/` folder with 5 chapter files (see §6.2 above) |
| `foundation/bga-developer-handbook.md` | ~3000 | Split into `foundation/handbook/` folder with one file per major chapter |
| `standards/action-and-flow-patterns.md` (future) | ~1000 est. | Split at 1000 lines: engine patterns → `architecture/engine-architecture.md`, action handlers → `standards/` |

---

## 7. Directory Tree

### 7.1 Immediate (Current State)

```
docs/
├── 00-documentation-architecture.md
├── foundation/
│   ├── bga-developer-handbook.md
│   ├── bga-ai-implementation-reference.md
│   └── reference-project-analysis.md
└── standards/
    └── notification-patterns.md
```

### 7.2 Target (Full Architecture)

```
docs/
├── 00-documentation-architecture.md
│
├── foundation/
│   ├── bga-developer-handbook.md              (→ split to foundation/handbook/ eventually)
│   ├── bga-ai-implementation-reference.md
│   └── reference-project-analysis.md
│
├── standards/
│   ├── 01-project-setup-and-structure.md
│   ├── notification-patterns.md               (→ split to standards/notifications/ eventually)
│   ├── 03-state-machine-patterns.md
│   ├── 04-database-patterns.md
│   ├── 05-action-and-flow-patterns.md          (→ split eventually)
│   ├── 06-undo-patterns.md
│   ├── 07-client-architecture.md
│   ├── 08-reconnect-and-spectator.md
│   ├── 09-performance-patterns.md
│   └── 10-debugging-and-testing.md
│
├── architecture/
│   ├── project-structure-overview.md
│   ├── engine-architecture.md
│   └── client-data-model.md
│
├── patterns/
│   ├── card-implementation-patterns.md
│   ├── simultaneous-turn-patterns.md
│   ├── multi-step-action-patterns.md
│   ├── draft-patterns.md
│   ├── spatial-board-patterns.md
│   ├── resource-management-patterns.md
│   ├── scoring-patterns.md
│   └── expansion-integration-patterns.md
│
├── playbooks/
│   ├── new-project-from-template.md
│   ├── studio-deployment.md
│   ├── production-release.md
│   └── migrating-to-modern-framework.md
│
├── checklists/
│   ├── pre-release-checklist.md
│   ├── notification-audit-checklist.md
│   └── state-machine-audit-checklist.md
│
└── README.md                                   ← Optional: top-level docs overview
```

### 7.3 Maturity Tiers

| Tier | Description | Folders Included |
|---|---|---|
| **Tier 1 — Foundation** | Must exist before any other work; covers what exists today | `foundation/`, this file |
| **Tier 2 — Standards** | Engineering standards that govern all implementation work | `standards/` |
| **Tier 3 — Architecture + Patterns** | Deeper reference material for complex subsystems | `architecture/`, `patterns/` |
| **Tier 4 — Operational** | Task-specific procedures and verification | `playbooks/`, `checklists/` |

Documents should be created in Tier order. No Tier 2 document should depend on a Tier 3 document. No Tier 4 document should be created before the standards it verifies are written.
