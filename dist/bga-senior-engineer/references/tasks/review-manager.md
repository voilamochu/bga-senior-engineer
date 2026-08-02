---
task: review-manager
version: 1.0.0
last_updated: 2026-07-30
source: docs/ai-os/bga-senior-engineer-doctrine.md
required_rules:
  - references/rules/constitution.json
  - references/rules/architecture.json
lazy_rules:
  - references/rules/persistence.json
required_examples:
  - assets/examples/manager-example.json
required_checklists:
  - assets/checklists/pre-review.json
max_tokens: 3310
---

# Review a Manager Class

## Prerequisites

Before proceeding, confirm the following files are loaded:
- references/rules/constitution.json
- references/rules/architecture.json
- assets/checklists/pre-review.json

Do not continue until all files are confirmed loaded.

## Lazy-Load Rules

The following rule files are available for conditional loading. Load them only when the described situation occurs:

- references/rules/persistence.json — Load when reviewing Manager database access patterns. Check PERS-001 through PERS-014.

## Workflow

### Step 1: Load the Canonical Example

Load assets/examples/manager-example.json to see the standard Manager pattern. Review the annotations to understand why each element exists.

### Step 2: Verify Ownership

Confirm the Manager owns exactly one aggregate root following ARCH-005. Each database table must have exactly one Manager with exclusive write ownership following ARCH-006. Verify no other component writes to this Manager's tables.

### Step 3: Verify Method Structure

Confirm the Manager has a complete API following ARCH-005: getter methods, validate methods, execute methods, count methods, check methods. Read methods return Model objects, not raw arrays following ARCH-009.

### Step 4: Verify Game.php Delegation

Confirm Game.php delegates to this Manager for getAllDatas following ARCH-003. Confirm the action handler delegates to this Manager following ACTN-006. The Manager should contain domain logic — it should not be a thin pass-through.

### Step 5: Check Model Usage

Verify every read method returns a Model object following ARCH-011 through ARCH-013. Models wrap a single DB row, compute derived values, and have zero framework dependencies. No raw array returns.

### Step 6: Check Cross-Manager Communication

Verify the Manager never calls another Manager directly following ARCH-014. Cross-Manager coordination must happen through the action handler.

### Step 7: Check Size and Cohesion

Verify the Manager stays under 800 lines following ARCH-018. Code that changes together should be in the same module following ARCH-019. If the Manager exceeds 800 lines, recommend splitting by sub-domain.

### Step 8: Check Database Access

If the Manager contains SQL queries, load references/rules/persistence.json and check PERS-001 through PERS-014 for correct schema design, atomic UPDATE patterns, and proper transaction boundaries.

## Edge Cases

- Manager writes to a table owned by another Manager? This is a violation of ARCH-003. Move the write to the owning Manager and add a coordination method to the action.
- Manager has no Model classes? Create Model classes wrapping each DB row. No raw arrays. See ARCH-009 and ARCH-011.
- Manager contains framework API calls beyond DB access? If it calls Game methods not related to its aggregate, extract that concern. Managers should focus on their domain. See ARCH-010.
- Manager exceeds 800 lines? Split by sub-domain. Each new Manager gets its own file and owns its subset of tables. See ARCH-018.
- Manager has no getAllDatas method? Add one. Every Manager must expose its state for the reconnect protocol. See ARCH-003.

## Stop Conditions

This task is complete when:
- [ ] Manager owns exactly one aggregate root with exclusive write ownership
- [ ] Manager API includes getter, validate, execute, count, and check methods
- [ ] All read methods return Model objects, not raw arrays
- [ ] No cross-Manager calls exist — coordination happens in actions
- [ ] Game.php delegates correctly to the Manager
- [ ] Manager is under 800 lines or has a documented split recommendation
- [ ] assets/checklists/pre-review.json passes all items

## Escalation

Stop and ask the user when:

| Situation | Question to Ask |
|---|---|
| Manager's aggregate boundary is disputed | "This Manager accesses tables that overlap with another Manager's domain. How should I resolve the ownership boundary?" |
| Manager exceeds size limit but splitting is complex | "The Manager is over 800 lines but the concerns are tightly coupled. Should I split it or accept the size for now?" |
| Manager uses a pattern not covered by the rules | "The Manager uses a caching pattern not documented in the architecture rules. Should I standardize it or keep the custom approach?" |

## Self-Validation

Before declaring the task complete:

1. Run assets/checklists/pre-review.json
   - Load the checklist file if not already loaded
   - Verify every item passes
   - If any item fails, fix the violation and re-run

2. Verify each stop condition is met
   - Re-read the Stop Conditions section
   - Confirm every condition passes

3. If any lazy rules were loaded:
   - Verify the triggered concerns are correctly addressed
   - Confirm no rule violations were introduced

4. Re-read the modified files
   - Verify no debugging artifacts remain
   - Verify naming is consistent with codebase conventions

Do not declare the task complete until all validation steps pass.
