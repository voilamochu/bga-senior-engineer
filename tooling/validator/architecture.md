# Runtime Validator — Architecture

## Module Structure

```
tooling/
  _shared/
    __init__.py
    loader.py              # Rule file loader — parses JSON, builds RuleCollection
    schema.py              # Schema v1.1 field definitions and validation primitives
    registry.py            # Rule ID registry, cross-reference index builder
    stats_engine.py        # Statistics engine — aggregates across rule collection
    report.py              # Report formatters (human, JSON, CI)
    cli.py                 # Shared CLI framework (argparse wrapper, exit codes)
    markdown.py            # Markdown table parser (for release doc parsing)
    types.py               # Shared data types (Rule, RuleCollection, ValidationError, etc.)
  validator/
    README.md              # Usage guide
    architecture.md        # This document
    validator-spec.md      # Validator specifications
    src/
      __init__.py
      __main__.py          # CLI entry point
      validators/
        __init__.py
        schema_validator.py
        rule_id_validator.py
        crossref_validator.py
        ownership_validator.py
        priority_validator.py
        stats_generator.py
        release_validator.py
```

## Data Flow

```
rules/*.json
     |
     v
  loader.py ──> RuleCollection (in-memory model)
     |
     +──> schema_validator      schema_errors[]
     +──> rule_id_validator     id_errors[]
     +──> crossref_validator    ref_errors[]
     +──> ownership_validator   ownership_errors[]
     +──> priority_validator    priority_errors[]
     +──> stats_generator       statistics{}
     |
     +──> report.py ──> human_report / json_report
```

## Core Data Model

### Rule

```python
@dataclass
class Rule:
    id: str                    # e.g. "ARCH-001"
    priority: int              # 1 through 5
    rule: str                  # Rule text
    violation: list[str]       # Violation examples
    check: str                 # Verification instruction
    fix: str                   # Correction instruction
    tags: list[str]            # Searchable tags
    applies_to: list[str]      # Target components
    exceptions: list[str]      # Optional exceptions
    see_also: list[str]        # Cross-references to other rule IDs
    rationale: str | None      # Constitutional rationale
    source: str | None         # Rule-level source override
    file_path: str             # Source file path
    file_domain: str           # Domain from file header
```

### RuleCollection

```python
@dataclass
class RuleCollection:
    rules: dict[str, Rule]     # rule_id -> Rule
    files: list[FileInfo]      # metadata per file
    crossref_index: dict       # rule_id -> list[referencing_rule_id]
    domain_index: dict         # domain -> list[rule_id]

@dataclass
class FileInfo:
    path: str
    domain: str
    version: str
    last_updated: str
    source: str
    rule_count: int
    line_count: int
```

## Loader

### Responsibilities

1. Accept a directory path to `rules/`
2. Discover all `*.json` files in the directory
3. Parse each file as JSON
4. Validate file-level metadata fields (domain, version, last_updated, source)
5. Extract each rule object, populating the Rule dataclass
6. Build cross-reference index (map each see_also target to its source)
7. Return a RuleCollection

### Error Handling

- File not found: raise FileNotFoundError with path
- Invalid JSON: raise ValueError with filename and parse error
- Missing file-level fields: collect as warning, continue
- Missing rule-level fields: collect as validation error, include rule ID in error

## Registry

The registry is built by the loader and provides:

1. **Rule ID lookup** — Given a rule ID string, return the Rule or None
2. **Cross-reference index** — For each rule ID, list all rules that reference it (incoming) and all rules it references (outgoing)
3. **Domain index** — For each domain prefix (ARCH, ACTN, etc.), list all rule IDs
4. **File index** — For each file, list all rule IDs it contains

## Report

### Human Report

Printed to stdout. Sections correspond to validator outputs. Each section shows:

- Validator name
- Status: PASS / FAIL
- Error count
- Detailed errors (if any)

### JSON Report

Serialized as:

```json
{
  "version": "1.0.0",
  "timestamp": "2026-07-29T12:00:00Z",
  "runtime_version": "1.1",
  "summary": {
    "total_validators": 7,
    "passed": 5,
    "failed": 2,
    "skipped": 0
  },
  "results": {
    "schema": {
      "status": "pass",
      "errors": []
    },
    "rule_id": {
      "status": "fail",
      "errors": [
        {"rule_id": "ARCH-023", "reason": "Undefined rule ID referenced in see_also"}
      ]
    }
  },
  "statistics": {
    "total_rules": 227,
    "total_files": 12,
    "total_lines": 4595
  }
}
```

### CI Output

CI mode suppresses all human-readable output except failure messages. Each failure prints one line:

```
FAIL validator=schema rule_id=ARCH-001 reason=missing required field 'check'
FAIL validator=crossref rule_id=ARCH-010 reason=unresolved reference UNDO-001
```

Exit code 1 on any FAIL, 0 on all PASS.

## Command-Line Parser

Implements the usage spec from README.md. Uses argparse with:

- Positional: none (all flags)
- --rules: required
- --schema: optional (embedded defaults used if omitted)
- --release: optional
- --report: optional, default "human"
- --output: optional, default stdout
- --ci: optional flag
- --validators: optional comma-separated list

## Package Entry Points

```
__main__.py:
    1. Parse CLI args
    2. Instantiate loader, load rules
    3. For each selected validator:
       a. Instantiate validator
       b. Run validator
       c. Collect results
    4. Generate report (human or JSON)
    5. Exit with appropriate code
```

## Implementation Notes

- All validators are pure functions operating on RuleCollection
- No validator modifies RuleCollection
- Validators may add error entries to a shared results list
- Report generation is separate from validation logic
- Statistics generator is always run (even when not selected) for other validators to reference
