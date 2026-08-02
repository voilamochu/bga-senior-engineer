---
task: review-persistence
version: 1.0.0
last_updated: 2026-07-30
source: docs/ai-os/bga-senior-engineer-doctrine.md
required_rules:
  - references/rules/constitution.json
  - references/rules/persistence.json
lazy_rules:
  - references/rules/undo-replay.json
required_checklists:
  - assets/checklists/pre-review.json
max_tokens: 2640
---

# Audit Database Schema and Globals

## Prerequisites

Before proceeding, confirm the following files are loaded:
- references/rules/constitution.json
- references/rules/persistence.json
- assets/checklists/pre-review.json

Do not continue until all files are confirmed loaded.

## Lazy-Load Rules

The following rule files are available for conditional loading. Load them only when the described situation occurs:

- references/rules/undo-replay.json — Load when reviewing undo log table design. Check UNDO-001 through UNDO-005.

## Workflow

### Step 1: Verify Schema Design

Verify the database schema follows PERS-001 through PERS-003. Table names use plural snake_case. Primary keys use identity columns. All game tables use ENGINE=InnoDB with foreign key constraints on identity columns.

### Step 2: Check Atomic Updates

Verify all state-changing queries use atomic conditional UPDATE patterns following PERS-004. The WHERE clause must include the expected current state to prevent race conditions. Use advisory locks or conditional UPDATE for concurrent operation safety following PERS-005.

### Step 3: Check Globals Usage

Verify global_variables are used only for cross-turn configuration and transient flow state following PERS-006. Entity data — cards, resources, tokens — must never be stored in global_variables following PERS-008.

### Step 4: Check Data-Driven Configuration

Verify capacities, costs, ratios, and game limits are stored in the database or material.inc.php following PERS-009 and PERS-010. No hardcoded numeric literals in PHP code.

### Step 5: Verify Scoring

Scores must be stored on the player table following PERS-007. Use player_score for the primary score and player_score_aux for auxiliary scores. Scores are maintained incrementally following PERS-011.

### Step 6: Check Query Patterns

Verify no N+1 query patterns exist. Batch database operations using WHERE IN for lookups by ID list following PERS-013.

### Step 7: Check Transaction Boundaries

Verify explicit transaction boundaries around multi-table mutations following PERS-014. Begin a transaction, perform all writes, commit. Roll back entirely on failure.

### Step 8: Check Undo Log

If reviewing undo log table design, load references/rules/undo-replay.json and check UNDO-001 through UNDO-005 for correct log table structure, LIFO reversal, and checkpoint boundaries.

## Edge Cases

- Entity data found in global_variables? Move to dedicated game tables. Globals are for flow state, not game data. See PERS-008.
- Hardcoded cost or ratio found in PHP? Move to material.inc.php or the database. See PERS-010.
- Schema uses MyISAM instead of InnoDB? Change to InnoDB for transaction support and FK constraints. See PERS-003.
- Score computed from scratch on each access? Change to incremental scoring. Store the running total and update on each scoring event. See PERS-011.
- Query inside a loop? Batch with WHERE IN. See PERS-013.
- No transaction wrapping for multi-table writes? Add explicit BEGIN/COMMIT. See PERS-014.

## Stop Conditions

This task is complete when:
- [ ] All game tables use InnoDB with FK constraints
- [ ] All state-changing queries use atomic conditional UPDATE patterns
- [ ] No entity data stored in global_variables
- [ ] No hardcoded game constants in PHP code
- [ ] Scores are stored incrementally on the player table
- [ ] No N+1 query patterns exist
- [ ] Multi-table writes use explicit transaction boundaries
- [ ] assets/checklists/pre-review.json passes all items

## Escalation

Stop and ask the user when:

| Situation | Question to Ask |
|---|---|
| Schema change would break live games | "The schema needs a migration that is not backward compatible. How should I handle live games?" |
| Data-driven configuration is split across locations | "Game constants are split between material.inc.php and the database. Should I consolidate them in one location?" |
| Performance optimization conflicts with schema rules | "The correct schema design requires a join, but the query volume suggests denormalization. Which takes priority?" |

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
