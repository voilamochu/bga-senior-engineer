# SKILL.md Review — bga-senior-engineer

**Reviewed artifact:** `docs/skill/SKILL.md` (design artifact; not yet packaged)
**Review date:** 2026-08-02
**Reviewed against:** OpenCode Agent Skills documentation; Agent Skills specification; Agent Skills best-practices, optimizing-descriptions, and using-scripts guidance; `docs/skill-packaging-plan.md` (v2 design).

---

## 1. Verification summary (measured)

| Metric | Target | Actual | Status |
|---|---|---|---|
| Body lines | < 350 | **265** | PASS |
| Total tokens (chars ÷ 4) | < 4,500 | **~3,816** | PASS |
| Body tokens | < 4,500 | ~3,605 | PASS |
| Description length | ≤ 1,024 (spec); ≤ 300 (plan target) | **435** | PASS vs. spec; **deviation** from plan target (see §8.1) |
| Frontmatter fields | name, description, metadata, compatibility, license | All present; `name` matches directory naming rules (lowercase-hyphen, ≤ 64 chars) | PASS |
| Required body sections | 12 | All 12 present | PASS |
| External file references | Every one states a load condition | 10 files in the loading table + 27 references in routing tables, all conditional | PASS |

**Frontmatter compliance:** only the five recognized fields (`name`, `description`, `license`, `compatibility`, `metadata`) are used — OpenCode ignores unknown fields, so no experimental fields were introduced. `metadata` is a string-string map (package-version, runtime-spec, rules, source). `description` is a folded block scalar (valid YAML).

---

## 2. Self-review checklist

### 2.1 OpenCode guidance

| Check | Result | Notes |
|---|---|---|
| `name` valid and matches directory | PASS | `bga-senior-engineer` — will match `bga-senior-engineer/` at install; the design doc pins this. |
| `description` present, 1–1,024 chars, third-person trigger-oriented | PASS | 435 chars; imperative ("Use when working on…"); keyword-front-loaded. |
| Discovery-agnostic content | PASS | No `.opencode/`-specific paths or permissions baked in; installs to any skills location. |
| No platform config in the skill | PASS | `compatibility` declares requirements in prose; no config schema referenced. |

### 2.2 Agent Skills specification

| Check | Result | Notes |
|---|---|---|
| SKILL.md under 500 lines / 5,000 tokens | PASS | 265 / ~3,816. |
| Progressive disclosure honored | PASS | Stage 0 = description (frontmatter); Stage 1 = body; Stage 2 = conditional loads only. |
| Every file reference conditional | PASS | See §2.4 — no bare "see references/…" phrasing remains. |
| References one level deep, relative | PASS | All references are `references/…`, `assets/…`, `scripts/…` relative to skill root. |
| No inline examples / rule sets / reference content | PASS | Body contains zero code, zero rule text; gotchas are one-line pointers with rule IDs. |
| Scripts referenced by relative path | PASS | `python3 scripts/check_project.py --help` — script contract deferred to the packaging step, but the invocation pattern matches guidance. |

### 2.3 Vercel / Agent Skills best practices

| Check | Result | Notes |
|---|---|---|
| Coherent unit, not doc bundle | PASS | One skill, one domain, one canon; router prevents multi-skill fragmentation. |
| Context spent on what the agent lacks | PASS | Gotchas carry the non-obvious corrections; framework facts live in references, not the body. |
| Defaults, not menus | PARTIAL | The 5-family router is a decision aid, but 13 task rows is close to a menu; mitigated by families + default-to-review-full + clarify-on-ambiguity (see §4.2). |
| Procedures over declarations | PASS | Task procedures are referenced, not summarized; the body routes, it does not teach the craft. |
| Validation loop | PASS | Explicit do → validate → fix → re-validate loop with checklist + script. |
| Gotchas in SKILL.md (pre-trigger visibility) | PASS | §High-value gotchas; each ≤ 1 line and cites rule IDs. |
| Trigger description engineered | PASS | Draft follows imperative/intent/keyword/boundary guidance; still needs the empirical trigger eval (see §5.1). |

### 2.4 Required self-review dimensions

| Dimension | Finding |
|---|---|
| **Duplicated information** | One residual overlap: the "Reference loading instructions" table and the routing tables both state when to load examples/checklists. This is intentional (global map vs. per-task conditions) and the two use different granularity; accepted. No other duplication found. |
| **Unnecessary always-loaded content** | The body is the mandatory surface: purpose, use/not-use, principles, router, protocol, validation, stop conditions, failure modes, gotchas, escalation. Each was retained only if it changes agent behavior every run. Suspects checked and retained: gotchas (needed pre-trigger), failure modes (cheap and corrective), stop conditions (needed per family). |
| **Routing ambiguity** | Mitigated: unique signal sets per task within a family; cross-family collisions noted in §4.2 ("review" signals vs. "debug"/"feature" overlap) and handled by the "most matches wins, ties to first-listed" rule plus default. Residual ambiguity exists between review-action and review-full for generic "review the game" requests — documented in §8.2. |
| **Poor trigger wording** | Description passes the checklist-style review (imperative, intent-first, keywords, boundary clauses). It has NOT yet been empirically validated; the trigger eval is the packaging-phase gate (§5.1). |
| **Progressive disclosure violations** | None found. The only unconditional load is the constitution rule file (required by design — it is the universal law); everything else is conditional on a named task or stated condition. |

---

## 3. Strengths

1. **Token discipline.** 265 lines / ~3.8K tokens — comfortably inside both the spec (< 500 / < 5K) and the plan (< 350 / < 4.5K) limits, leaving headroom for empirical tuning.
2. **Router completeness.** Every task carries trigger signals, procedure, rule bundle, checklist, and conditional optional loads — mapped 1:1 from the frozen `index.json`, so routing fidelity to the certified canon is preserved.
3. **Conditional loading everywhere.** No file reference in the document lacks a "load when" condition; the consolidated loading table (§Reference loading instructions) and the per-task "Load also, only when" columns are complementary, not redundant.
4. **Read-only posture.** "The skill is read-only" is stated in Purpose and enforced in failure modes and escalation — protecting the dist from self-editing agents.
5. **Validation as definition of done.** The validation loop and family stop conditions close the "declared done at code-complete" failure mode.
6. **Gotchas grounded in the canon.** Each gotcha cites rule IDs (ARCH-001, NOTF, STAT, ACTN, UNDO, PERS, MIGR, CORE-013) — traceable to the rule files without inlining them.
7. **Escalation completeness.** Covers no-match, rule conflict, framework uncertainty, intentional deviation, security, and stale-skill cases — each with an actionable response.

---

## 4. Weaknesses

1. **No empirical trigger validation yet.** The description is drafted to guidance but has not run the ~20-query, 3×-per-query trigger eval the design mandates. Routing reliability is therefore *estimated*, not measured.
2. **Routing-table density.** Five tables with 13 rows and a 6-column layout is information-dense for a body whose job is routing, not teaching. Lines are terse, but a first-time reader needs a moment to parse the column conventions (rule bundles are shorthand file names; "Load also, only when" cells mix rules, examples, references, and the script).
3. **Cross-family signal collisions are documented but not eliminated.** "review" appears in Review signals and as an outcome word in Debug/Feature contexts; "migrate"/"refactor" share "legacy" vocabulary. The tie-break rule handles this deterministically, but a genuinely ambiguous request ("review this migration") may route to the wrong task.
4. **The script is promised but undefined.** `check_project.py` is referenced in three families and the validation loop, but its behavior, CLI, and rule coverage are deferred to the packaging step. Until it exists, the mechanical half of the validation loop is aspirational.
5. **Gotcha list is a snapshot.** Ten gotchas cannot cover 185 rules; the selection is heuristic. Rules added in future spec versions will require gotcha maintenance in the body — the highest-maintenance section by design.

---

## 5. Remaining concerns

### 5.1 Trigger reliability (pre-release gate)

Before the package ships, the description must pass the trigger eval defined in `docs/skill-packaging-plan.md` §5.1: ~20 queries (10 should-trigger across phrasing/detail axes; 10 near-miss negatives, e.g., "set up a BGA studio account", "fix a CSS bug in our web dashboard", "write a PHP unit test for a non-BGA app"), 3 runs each, threshold 0.5, train/validation split. Expectation: the draft's heavy keyword surface (13 capability words + 5 artifact names + 5 task verbs) should land ≥ 0.7 on should-trigger; the boundary clause ("Not for non-BGA projects or BGA studio setup") is the main defense against false positives. One iteration of wording is anticipated.

### 5.2 Routing fidelity in the wild

The keyword-matching algorithm is inherited from `index.json` and is deterministic in principle, but in practice the agent applies it as judgment. The five-family grouping is a compression of the original 13-task flat map; a mapping table back to the canonical task IDs is implicit in the "Procedure" column. If routing drift is observed in execution traces, the fix is to tighten signal columns, not the algorithm.

### 5.3 The constitution as the sole unconditional load

By design, `references/rules/constitution.json` loads on every task (~5.8K tokens). This is deliberate (it is the immutable law and the frozen spec mandates it), but it is the single largest always-payed cost per activation. Accepted; documented here for the record.

---

## 6. Estimated activation token count

| Stage | What enters context | Estimate |
|---|---|---|
| Discovery (every agent, every session) | `name` + `description` | **~115 tokens** (435-char description) |
| Activation (skill invoked) | Full SKILL.md body | **~3,600–3,900 tokens** |
| Task load (worst family member) | Task file + constitution + domain rules + checklist | ~7,900–26,500 (measured; migrate-manager is the heaviest at ~26.5K full, ~13.5K first phase) |
| Lazy loads | Per named example/reference/script | +1,000–3,500 each |

Total for the mandatory surface (discovery + activation): **~3,700–4,000 tokens** — inside the 4,500 budget with ~15% headroom.

---

## 7. Estimated routing reliability

Qualitative estimate pending empirical eval (see §5.1):

| Dimension | Estimate | Basis |
|---|---|---|
| Should-trigger (BGA engineering requests) | **High** (expected ≥ 0.7) | 13 capability keywords + 5 artifact names + 5 task verbs in the description; "even if the user does not say BGA" clause covers indirect phrasings. |
| Should-not-trigger (non-BGA work) | **High** (expected ≥ 0.8) | Explicit boundary clauses; BGA artifacts named in the description are a strong positive-only signature. |
| Intra-skill routing (correct task family once activated) | **Medium-high** | Deterministic tie-break rules + distinct per-task signals; residual collision risk between review-action/review-full and migrate/refactor documented in §8.2. |
| Cross-agent consistency (orchestrator/worker/firstmate) | **High** | Routing is uniform in the body; no agent-specific branches exist. |

---

## 8. Unresolved tradeoffs

### 8.1 Description length: 435 chars vs. the plan's 300-char target

The plan targeted ≤ 300 chars to minimize the description's replicated cost in every agent's context. 435 chars (~115 tokens per agent) buys a materially larger trigger surface (13 keywords vs. ~8). **Chosen: 435**, spec-compliant (≤ 1,024); the trigger eval decides whether the extra surface pays for itself or gets cut.

### 8.2 Generic "review" ambiguity

Requests like "review the game" or "audit this" can match both `review-full` (pre-release/full-audit signals) and family-level signals. The body resolves via signal-count tie-break and the default-to-review-full rule. Alternative considered: forcing a clarifying question for ambiguous review requests — rejected as it adds a turn for the common case where a full audit is wanted.

### 8.3 Menu density vs. routing precision

13 task rows is close to the "menus" anti-pattern from best practices. Compressing to families alone would lose per-task precision. **Chosen: 13 rows grouped in 5 families** — precision wins; density is mitigated by grouping and the default/clarify fallbacks.

### 8.4 Gotcha maintenance burden

Gotchas are the only body section that must be hand-synced with the rule canon. Tradeoff: keeping them in the body (visible pre-trigger, highest value) vs. moving to a reference (cheaper to maintain, but the agent may not load them). **Chosen: body**, per best-practices "keep gotchas in SKILL.md where the agent reads them before encountering the situation."

### 8.5 `check_project.py` is named but unspecified

The body commits the validation loop to a script that does not yet exist. Tradeoff: specifying the contract now (in the review) vs. letting the packaging step define it. **Recommended:** packaging step implements a minimal version (Game.php line count, SQL-in-Game.php scan, direct `notifyAllPlayers` scan) before the first release, per the plan's open-question #1.

---

## 9. Conclusion

`docs/skill/SKILL.md` is **ready for the packaging phase as the authoritative design** of the runtime skill body. It passes all hard constraints (frontmatter contract, size limits, conditional-load discipline, required sections), introduces no inline duplication of the canon, and its only open items are empirical (trigger eval) or deferred by design (script implementation, packaging).

**No runtime packaging has occurred.** `dist/` was not created, no files were copied or moved, and no implementation artifact was modified. The authored `SKILL.md` is a design artifact at `docs/skill/SKILL.md`, awaiting approval before it becomes the package's entry point.
