---
task: migrate-client
version: 1.0.0
last_updated: 2026-07-30
source: docs/ai-os/bga-senior-engineer-doctrine.md
required_rules:
  - rules/constitution.json
  - rules/client.json
lazy_rules:
  - rules/migration.json
required_examples:
  - examples/client-manager-example.json
required_checklists:
  - checklists/pre-commit.json
max_tokens: 2650
---

# Convert Legacy Dojo Client to ES Modules

## Prerequisites

Before proceeding, confirm the following files are loaded:
- rules/constitution.json
- rules/client.json
- checklists/pre-commit.json

Do not continue until all files are confirmed loaded.

## Lazy-Load Rules

The following rule files are available for conditional loading. Load them only when the described situation occurs:

- rules/migration.json — Load when handling Dojo-to-ES module conversion. Check MIGR-006.

## Workflow

### Step 1: Load the Canonical Example

Load examples/client-manager-example.json to see the standard client-side Manager pattern.

### Step 2: Create Client Managers

Organize client code into Manager classes mirroring the server-side Manager structure following CLNT-001 and CLNT-002. Each client Manager constructor receives the game view reference and dependent Manager interfaces.

### Step 3: Migrate to ES Modules

Convert legacy Dojo classes to ES module structure following CLNT-010. Each module file exports one class. Use import/export syntax. TypeScript is preferred for new code following CLNT-012.

If handling Dojo-specific migration patterns, load rules/migration.json and check MIGR-006.

### Step 4: Wire Notification Handlers

Create notification handlers named notif_<camelCase> matching the server notification type following CLNT-005. Register all handlers in setupPromiseNotifications following CLNT-006. Every handler must be idempotent following CLNT-007.

### Step 5: Wire Action Buttons

Wire all server actions through bga.actions.performAction following CLNT-008. Action buttons are wired through the client Manager that owns the action domain following CLNT-013. Never mutate DOM before the server confirms the action following CLNT-009.

### Step 6: Integrate BgaCards

For card games, replace legacy ebg/stock with BgaCards for all card rendering following CLNT-003. BgaCards handles the deck, hand, discard, and board areas.

### Step 7: Clean Up Legacy

Remove all Dojo widget references. Remove legacy ebg/stock code. Replace any remaining Dojo class syntax with ES module syntax.

## Edge Cases

- Legacy code uses Dojo dom manipulation? Replace with standard DOM APIs or framework methods. Never bypass the notification handler pipeline. See CLNT-009.
- Legacy code has inline event handlers? Move all event wiring to the client Manager. Handlers belong in setupPromiseNotifications. See CLNT-006.
- Client makes direct server state assumptions? Every state change must go through bga.actions.performAction. Never assume client-side state is authoritative. See CLNT-008.
- Migration affects multiple files? Extract one client Manager at a time. Follow the same extraction pattern as server-side Managers.
- Animations need to be preserved? Wire BgaAnimations.Manager in the main game setup following CLNT-004. Pass it to client Managers that need animation support.

## Stop Conditions

This task is complete when:
- [ ] Client Managers mirror server Manager structure
- [ ] All notification handlers use notif_<camelCase> naming and are registered in setupPromiseNotifications
- [ ] Every handler is idempotent
- [ ] All server actions use bga.actions.performAction
- [ ] Legacy Dojo classes are replaced with ES modules
- [ ] Legacy ebg/stock is replaced with BgaCards for card games
- [ ] checklists/pre-commit.json passes all items

## Escalation

Stop and ask the user when:

| Situation | Question to Ask |
|---|---|
| Client Manager boundary is unclear | "The legacy client code is monolithic. How should I split it into Manager classes?" |
| Animation behavior must be preserved | "The legacy code has custom animation logic. Should I preserve it as-is or migrate to BgaAnimations?" |
| Third-party Dojo plugins are used | "The legacy code uses a Dojo plugin that has no ES module equivalent. Should I find a replacement or build a custom solution?" |

## Self-Validation

Before declaring the task complete:

1. Run checklists/pre-commit.json
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
