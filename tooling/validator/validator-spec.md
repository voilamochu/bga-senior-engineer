# Runtime Validator — Validator Specifications

---

## 1. Schema Validator

**Module:** `validators/schema_validator.py`
**CLI name:** `schema`

### Purpose

Verify every rule file and every rule within it conforms to Schema v1.1.

### File-Level Checks

| Check | Rule | Error If |
|---|---|---|
| domain present | Required field | Missing or empty |
| version present | Required field | Missing or empty |
| last_updated present | Required field | Missing or empty |
| source present | Required field | Missing or empty |
| rules present | Required field | Missing or empty array |
| version format | MAJOR.MINOR.PATCH | Does not match `\d+\.\d+\.\d+` |
| last_updated format | ISO 8601 (YYYY-MM-DD) | Does not match `\d{4}-\d{2}-\d{2}` |
| domain value | Must be string | Non-string type |

### Rule-Level Checks

| Check | Required | Type | Error If |
|---|---|---|---|
| id | Yes | string | Missing, empty, or wrong type |
| priority | Yes | integer | Missing, wrong type, or out of range 1-5 |
| rule | Yes | string | Missing, empty, or wrong type |
| violation | Yes | array of strings | Missing, wrong type, non-string elements, or empty array |
| check | Yes | string | Missing, empty, or wrong type |
| fix | Yes | string | Missing, empty, or wrong type |
| tags | Yes | array of strings | Missing, wrong type, or non-string elements |
| applies_to | Yes | array of strings | Missing, wrong type, or non-string elements |
| exceptions | No | array of strings | Present but not an array or non-string elements |
| see_also | No | array of strings | Present but not an array or non-string elements |
| rationale | No | string | Present but not a string |
| source | No | string | Present but not a string |

### Constitutional-Specific Checks

For rules in `constitution.json` (domain == "constitution"):

| Check | Error If |
|---|---|
| priority == 1 | priority != 1 |
| rationale present | rationale missing or empty |

### Extra-Field Detection

Flag any field present in a rule object that is not in the Schema v1.1 defined fields list. This catches typos and schema drift.

### Error Output

```
FAIL validator=schema file=architecture.json rule_id=ARCH-005 reason=missing required field 'violation'
FAIL validator=schema file=constitution.json rule_id=CORE-001 reason=constitutional rule missing 'rationale'
FAIL validator=schema file=actions.json rule_id=ACTN-003 reason=unknown field 'exeptions' (did you mean 'exceptions'?)
```

---

## 2. Rule ID Validator

**Module:** `validators/rule_id_validator.py`
**CLI name:** `rule_id`

### Purpose

Verify all rule IDs are unique, follow the naming convention, have no gaps within a prefix, and have no duplicate IDs.

### Naming Convention

Each rule ID must match `^(CORE|ARCH|STAT|ACTN|PERS|NOTF|CLNT|SYNC|UNDO|TEST|ANIM|MIGR)-\d{3}$`

Example valid: `ARCH-001`, `UNDO-014`
Example invalid: `ARCH-1`, `UNDO-14`, `ARCH-01`, `CUSTOM-001`

### Prefix-to-File Mapping

| Prefix | File |
|---|---|
| CORE | constitution.json |
| ARCH | architecture.json |
| STAT | state-machine.json |
| ACTN | actions.json |
| PERS | persistence.json |
| NOTF | notifications.json |
| CLNT | client.json |
| SYNC | synchronization.json |
| UNDO | undo-replay.json |
| TEST | testing.json |
| ANIM | animations.json |
| MIGR | migration.json |

### Checks

| Check | Error If |
|---|---|
| Uniqueness | Two rules share the same rule ID |
| Prefix convention | Rule ID does not match `PREFIX-\d{3}` |
| Prefix-file alignment | Rule prefix does not match the file it appears in |
| Numeric ordering | Numbers are not required to be sequential (gaps are acceptable) |
| Duplicate within file | Same ID appears more than once in the same file |
| Leading zeros | Number is not zero-padded to 3 digits (e.g. `ARCH-01` instead of `ARCH-001`) |

### Gap Handling

Gaps in numbering are acceptable and are NOT validation errors. For example `ARCH-001`, `ARCH-002`, `ARCH-005` is valid. The statistics generator counts gaps for informational purposes only.

### Duplicate Detection

Check both within-file and cross-file. Since prefixes are file-specific, cross-file duplicates should not occur, but the validator checks anyway.

### Error Output

```
FAIL validator=rule_id rule_id=ARCH-01 reason=ID does not match naming convention (expected ARCH-001)
FAIL validator=rule_id rule_id=STAT-001 reason=duplicate rule ID in state-machine.json (line 1 and line 300)
FAIL validator=rule_id rule_id=ACTN-001 reason=prefix ACTN in architecture.json (expected in actions.json)
FAIL validator=rule_id rule_id=ARCH-001 reason=duplicate rule ID across files (architecture.json and constitution.json)
```

---

## 3. Cross-Reference Validator

**Module:** `validators/crossref_validator.py`
**CLI name:** `crossref`

### Purpose

Verify every `see_also` reference resolves to an existing rule ID. Detect self-references and circular reference chains.

### Checks

| Check | Error If |
|---|---|
| Unresolved reference | `see_also` entry does not match any rule ID in the registry |
| Self-reference | Rule references itself in `see_also` |
| Circular reference (direct) | Rule A references rule B and rule B references rule A |
| Circular reference (indirect) | Rule A → B → C → A (depth-limited to 10 hops) |
| Same-file reference | Flagged when a rule references another rule in the same file (informational, not an error) |
| Orphan rule | Rule is never referenced by any other rule's `see_also` and is not in any other file's cross-reference index |

### Circular Reference Detection Algorithm

```
Input: crossref_index (dict[source_id, list[target_id]])

For each rule_id in crossref_index:
    visited = set()
    queue = [(rule_id, [rule_id])]
    while queue:
        current, path = queue.pop(0)
        if current in visited:
            continue
        visited.add(current)
        for target in crossref_index.get(current, []):
            if target == rule_id and len(path) > 1:
                report CIRCULAR: path + [target]
            elif target not in visited:
                queue.append((target, path + [target]))
```

Depth limit: 10 hops. Any chain longer than 10 is reported as "possible circular reference (depth limit exceeded)" rather than a definite cycle.

### Self-Reference Detection

A rule referencing itself in `see_also` is always an error. A rule should not need to reference itself.

### Unresolved Reference Handling

If a `see_also` target does not exist in the registry, it is an error. This includes:

- Typo in the rule ID (e.g. `ARCH-0001` instead of `ARCH-001`)
- Deleted rule ID that was not removed from references
- Forward reference to a file that was never created

### Orphan Rule Definition

A rule is orphaned if:

1. No other rule in any file references it in `see_also`
2. It is not mentioned in the release document or certification reports

This is an informational warning, not a hard error. Some rules may legitimately have no incoming references.

### Error Output

```
FAIL validator=crossref reason="ARCH-023 referenced by ACTN-005 does not exist"
FAIL validator=crossref reason="CORE-007 references itself in see_also"
FAIL validator=crossref reason="circular reference: ARCH-005 -> ARCH-006 -> ARCH-005"
WARN validator=crossref rule_id=PERS-012 reason="no incoming references (orphan rule)"
```

---

## 4. Ownership Validator

**Module:** `validators/ownership_validator.py`
**CLI name:** `ownership`

### Purpose

Verify that `applies_to` values reference valid architectural components, that no two files claim ownership of the same rule prefix, and that domain values are consistent.

### Checks

| Check | Error If |
|---|---|
| Invalid applies_to value | `applies_to` entry is not in the canonical component list |
| Duplicate prefix ownership | Two different files contain rules with the same prefix |
| Invalid domain value | File-level `domain` field is not in the canonical domain list |
| Domain-file mismatch | File-level `domain` does not match expected domain for the prefix |
| Empty applies_to | `applies_to` array is empty |
| Duplicate applies_to entries | `applies_to` array contains duplicate values |

### Canonical Component Values

```
Game.php
Actions
States
Managers
Models
Notifications
Client
Database
Engine
All components
```

These are the ONLY valid values for `applies_to` entries.

### Canonical Domain Values

```
constitution
architecture
state-machine
actions
persistence
notifications
client
synchronization
undo-replay
testing
animations
migration
```

These are the ONLY valid values for file-level `domain` fields.

### Prefix-to-Domain Mapping

| Prefix | Expected Domain |
|---|---|
| CORE | constitution |
| ARCH | architecture |
| STAT | state-machine |
| ACTN | actions |
| PERS | persistence |
| NOTF | notifications |
| CLNT | client |
| SYNC | synchronization |
| UNDO | undo-replay |
| TEST | testing |
| ANIM | animations |
| MIGR | migration |

### Error Output

```
FAIL validator=ownership file=undo-replay.json rule_id=UNDO-004 reason=invalid applies_to value 'Persistence' (expected canonical value)
FAIL validator=ownership file=migration.json rule_id=MIGR-005 reason=invalid applies_to value 'Globals' (expected canonical value)
FAIL validator=ownership file=migration.json reason=domain 'migration' does not match expected for prefix MIGR
FAIL validator=ownership file=architecture.json rule_id=ARCH-001 reason=applies_to array is empty
```

---

## 5. Priority Validator

**Module:** `validators/priority_validator.py`
**CLI name:** `priority`

### Purpose

Verify every rule's `priority` field is a valid integer in the range 1-5 and that constitutional rules have priority 1.

### Checks

| Check | Error If |
|---|---|
| Valid range | priority is not an integer in range 1-5 |
| Constitutional constraint | Any rule in constitution.json has priority != 1 |
| Constitutional constraint | Any rule outside constitution.json has priority == 1 |
| Non-integer priority | priority is a float, string, or other non-int type |

### Priority Scale Reference

| Priority | Meaning | Applies To |
|---|---|---|
| 1 | Immutable law — never violated | constitution.json only |
| 2 | Hard architectural constraint | Rules with architectural impact |
| 3 | Strong pattern requirement | Rules with correctness impact |
| 4 | Best practice with documented exception | Rules with quality impact |
| 5 | Style preference / convention | Migration rules, naming preferences |

### Error Output

```
FAIL validator=priority rule_id=ARCH-001 reason=priority 0 (out of range 1-5)
FAIL validator=priority rule_id=CORE-001 reason=constitutional rule must have priority 1 (got 2)
FAIL validator=priority rule_id=CLNT-007 reason=priority 1 is reserved for constitutional rules
FAIL validator=priority rule_id=TEST-010 reason=priority "4" is a string, not integer
```

---

## 6. Statistics Generator

**Module:** `validators/stats_generator.py`
**CLI name:** `stats`

### Purpose

Generate aggregate statistics from the runtime rule set. This validator never fails — it always produces data. It is always run even when other validators are selected, because other validators reference its output.

### Output Fields

#### File-Level Statistics

```json
{
  "files": [
    {
      "file": "architecture.json",
      "domain": "architecture",
      "rule_count": 22,
      "line_count": 615,
      "rules_from": "ARCH-001",
      "rules_to": "ARCH-022",
      "priorities": {"2": 14, "3": 5, "4": 3}
    }
  ]
}
```

#### Aggregate Statistics

```json
{
  "total_files": 12,
  "total_rules": 227,
  "total_lines": 4595,
  "priority_distribution": {
    "1": 16,
    "2": 45,
    "3": 40,
    "4": 107,
    "5": 19
  },
  "tag_distribution": {
    "architecture": 22,
    "testing": 17,
    "undo": 14
  },
  "applies_to_distribution": {
    "Managers": 60,
    "Actions": 45,
    "All components": 30
  },
  "cross_reference_count": 185,
  "largest_file": "architecture.json (615 lines)",
  "smallest_file": "undo-replay.json (255 lines)",
  "average_rules_per_file": 18.9
}
```

#### Gap Analysis

```json
{
  "gaps": {
    "ARCH": [],
    "UNDO": ["UNDO-013", "UNDO-014"],
    "MIGR": ["MIGR-008", "MIGR-009", "MIGR-010"]
  }
}
```

Note: Gaps are informational only. Since the concept map originally had gaps in the migration ID sequence (MIGR-008 through MIGR-013 were added after MIGR-014 was created), gaps are listed but not flagged as errors.

### Implementation

The statistics generator counts:

1. Rules per file — count of rule objects in each file
2. Lines per file — `wc -l` equivalent
3. Priority distribution — count of rules at each priority level
4. Tag distribution — count of rules tagged with each tag value
5. Applies_to distribution — count of rules with each applies_to value
6. Cross-reference count — total number of `see_also` entries across all files
7. ID gaps — missing numbers in each prefix sequence
8. Rules from/to — first and last rule ID in each file (by numeric order)

---

## 7. Release Validator

**Module:** `validators/release_validator.py`
**CLI name:** `release`

### Purpose

Verify that the published release document statistics match the actual runtime files. This prevents the release document from drifting out of sync with the rules.

### Prerequisites

Requires `--release` flag pointing to the release Markdown document. Also requires `--rules` flag for the runtime files.

### Checks

| Check | What It Compares |
|---|---|
| Total rule count | Release doc total vs actual |
| Total file count | Release doc file count vs actual |
| Per-file rule counts | Release doc table entries vs actual per-file |
| Per-file line counts | Release doc table entries vs actual per-file |
| Total line count | Release doc total vs actual |
| Schema version | Release doc schema version vs actual (always 1.1) |
| Release version | Release doc must say "1.1" |

### Release Document Parsing

The validator parses the release document's "Runtime Inventory" table (Markdown table format) to extract per-file rule counts and line counts. The table format is:

```
| File | Domain | Rules | Lines | ... |
|---|---|---|---|---|
| constitution.json | Constitutional law | 16 | 487 | ... |
```

The parser finds the table by looking for the "Runtime Inventory" section header and the first Markdown table after it.

If the table cannot be parsed, the validator emits a warning and falls back to checking only the aggregate statistics in the "Implementation Statistics" section.

### Error Output

```
FAIL validator=release reason="release doc says constitution.json has 16 rules, actual has 15"
FAIL validator=release reason="release doc says total rules is 227, actual is 228"
FAIL validator=release reason="release doc says total files is 12, actual is 13"
FAIL validator=release reason="release doc says schema version is 1.0, actual is 1.1"
```

---

## 8. Report Formats

### Human Report Format

```
=== Runtime Specification Validator ===
Version: 1.0.0
Runtime: v1.1
Timestamp: 2026-07-29T12:00:00Z

--- Schema Validation: PASS (0 errors) ---
--- Rule ID Validation: PASS (0 errors) ---
--- Cross-Reference Validation: FAIL (3 errors) ---
  ERROR: ARCH-023 referenced by ACTN-005 does not exist
  ERROR: CORE-007 references itself in see_also
  ERROR: circular reference: ARCH-005 -> ARCH-006 -> ARCH-005
--- Ownership Validation: PASS (0 errors) ---
--- Priority Validation: PASS (0 errors) ---

=== Statistics ===
Total files: 12
Total rules: 227
Total lines: 4,595
Cross-references: 185

=== Result: FAIL (1 of 6 validators failed) ===
```

### JSON Report Format

As defined in architecture.md §Report.

### CI Output Format

One line per failure. No headers, no summaries, no statistics. Only the first 3 errors per validator are printed to avoid overwhelming output.

```
FAIL validator=crossref detail="ARCH-023 referenced by ACTN-005 does not exist"
FAIL validator=crossref detail="CORE-007 references itself in see_also"
FAIL validator=crossref detail="circular reference: ARCH-005 -> ARCH-006 -> ARCH-005"
```

---

## Validator Implementation Contract

Every validator module must expose:

```python
def validate(rules: RuleCollection) -> ValidatorResult:
    """
    Run validation.

    Args:
        rules: Loaded rule collection with full cross-reference index.

    Returns:
        ValidatorResult with status and error list.
    """
```

```python
@dataclass
class ValidatorResult:
    name: str                       # Validator name (matches CLI name)
    status: str                     # "pass" or "fail"
    errors: list[ValidationError]   # Empty if pass

@dataclass
class ValidationError:
    validator: str                  # Validator name
    rule_id: str | None             # Affected rule ID, if applicable
    file: str | None                # Affected file, if applicable
    reason: str                     # Human-readable error description
    severity: str                   # "error" or "warning"
```

The `validate` function must be stateless — it should not modify any global state, cache, or the RuleCollection. All state required for validation must be derivable from the RuleCollection parameter.

For validators that require persistent state (e.g. tracking which rules have been seen), use local state within the function scope. Do not use module-level or class-level state.
