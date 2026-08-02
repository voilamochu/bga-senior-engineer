# BGA Senior Engineer — Packaging Report

**Phase:** 3 — Build the distributable skill package
**Date:** 2026-08-02
**Artifact:** `dist/bga-senior-engineer/` (42 files, ~504 KB)
**Design authority:** `docs/skill-packaging-plan.md` (v2), `docs/skill/SKILL.md`, `docs/skill/SKILL_REVIEW.md`

---

## 1. What was built

```
dist/bga-senior-engineer/
├── SKILL.md                        # authored in Phase 2, copied verbatim (leakage scan: clean)
├── LICENSE                         # MIT, verbatim from repo root
├── references/
│   ├── rules/                      # 12 frozen rule files, byte-identical copies
│   ├── tasks/                      # 13 task procedures (see §3 for the one deviation)
│   ├── architecture-reference.json # byte-identical copy
│   ├── framework-reference.json    # copy of bga-framework-reference.json, renamed
│   ├── migration-reference.json    # byte-identical copy
│   └── project-matrix.json         # NEW, distilled from docs/foundation/reference-project-analysis.md
│                                   # (18 problem-keyed entries; every file path verified against the
│                                   #  reference projects before shipping)
├── assets/
│   ├── checklists/                 # 3 frozen checklists, byte-identical
│   └── examples/                   # 7 frozen examples, byte-identical
└── scripts/
    └── check_project.py            # NEW runtime capability: mechanical rule checks (std-lib only)
```

## 2. New files (the only authored content)

| File | What it is | Verification |
|---|---|---|
| `references/project-matrix.json` | 18 problem-keyed entries: problem → best project → file → backup → notes, per the schema in `runtime-skill-architecture.md` §5.7 | Every `file` path checked against the reference-projects tree before shipping |
| `scripts/check_project.py` | Implements the mechanical halves of rule checks named in SKILL.md: Game.php line count (ARCH-001), SQL-in-Game.php scan, direct `notifyAllPlayers`/`notifyPlayer` scan (NOTF), states.inc.php sanity (STAT) | Smoke-tested against `reference-projects/arnak` (legacy: 4/4 FAIL, correct) and `earth` (modern: correct candidates surfaced); JSON output, exit codes, `--help`, `--list`, `--check`, `--limit` all exercised |

The checker is a **mechanical aid**: its findings are candidates for the agent to reconcile against the rule files, not verdicts (e.g., it flags Earth's command-pattern notify placement, which the rules evaluate in context).

## 3. Packaging deviations from byte-identical copying

One deviation, required to make the package functional:

- **Task file path rewrites.** The 13 frozen prompts reference the old Mercurio layout (`rules/…`, `examples/…`, `checklists/…`). In the dist those files live at `references/rules/…`, `assets/examples/…`, `assets/checklists/…`. On copy, paths were mechanically rewritten (`rules/` → `references/rules/`, `examples/` → `assets/examples/`, `checklists/` → `assets/checklists/`). Verified: no old-layout path, double prefix, or parent-path reference remains in any shipped `.md`. No rule, checklist, example, or reference content was edited.

## 4. Runtime verification results

| Check | Result |
|---|---|
| Every path referenced in SKILL.md exists | PASS (28 references incl. 10 file entries in the loading table; 0 missing) |
| Template references expand to shipped files | PASS — 13 tasks, 7 examples, 3 checklists, 12 rules: shipped sets == referenced sets |
| Every packaged file is referenced by the skill | PASS — 0 unreferenced files (SKILL.md + task files are the reference surface) |
| Script paths valid | PASS — `scripts/check_project.py` runs from the skill root and from an installed location |
| Directory name matches skill name | PASS — `bga-senior-engineer` == frontmatter `name` |
| OpenCode naming rules | PASS — lowercase-hyphen, no leading/trailing/consecutive hyphens, ≤ 64 chars |
| Frontmatter validity | PASS — exactly the 5 recognized fields; `name` + `description` present; `metadata` is a string-string map; description 435 chars (≤ 1,024) |
| All JSON parses | PASS — 27 JSON files |
| No build artifacts in dist | PASS — no `__pycache__`, `.pyc`, archives, or manifests |
| Symlink install | PASS — `ln -s dist/bga-senior-engineer .opencode/skills/` + `find -L` discovers `SKILL.md` |

## 5. Final packaging review (success criteria)

| Criterion | Verdict |
|---|---|
| Follows OpenCode Agent Skills recommendations | PASS — standard layout, valid frontmatter, discovery-compatible, `skill` tool loads name + description; permissions are platform-level, not baked in |
| Follows Vercel / Agent Skills philosophy | PASS — capability not documentation: references = knowledge, assets = templates/validation data, scripts = one executable capability; no README, no manifests, no scraped docs |
| Progressive disclosure preserved | PASS — SKILL.md (≤ 4.5K tokens) is the only auto-loaded file; every other file is behind a stated load condition |
| Runtime-only package | PASS — no evaluation pipeline, benchmark corpus, tooling, design/planning docs, or repo metadata shipped |
| No documentation bundle smell | PASS — 42 files, 504 KB; the largest components are the frozen canon (rules) and task procedures, all loadable on demand |
| Installable via copy or symlink into `.opencode/skills/` | PASS — tested via symlink; a plain copy is equivalent |

## 6. Residual notes

- `LICENSE` ships per the Agent Skills `license` field convention (MIT, `license: MIT` in frontmatter).
- `bga-framework-reference.json` was renamed to `framework-reference.json` to match the name SKILL.md routes on; the Mercurio package is untouched.
- The trigger description has not yet run the empirical ~20-query trigger eval; that is a post-install validation step, not a packaging defect.
