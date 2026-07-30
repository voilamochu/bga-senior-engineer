# Runtime Validator

Validates that Runtime Specification v1.1 JSON rule files remain internally consistent.

Part of the **Runtime Tooling Platform** — see `docs/ai-os/runtime-tooling-architecture.md` for the full platform architecture.

---

## Installation

```bash
# Clone the repository
git clone <repository-url>
cd bga-senior-engineer

# No dependencies required — stdlib only (Python 3.10+)
python -m tooling.validator --help
```

---

## Quick Start

```bash
# Validate all rules (human-readable report)
python -m tooling.validator --rules bga-senior-engineer-skill/rules/

# Validate with JSON output
python -m tooling.validator --rules bga-senior-engineer-skill/rules/ --report json

# JSON report written to file
python -m tooling.validator --rules bga-senior-engineer-skill/rules/ --report json --output report.json

# CI mode — one line per failure, exit on first error
python -m tooling.validator --rules bga-senior-engineer-skill/rules/ --ci

# Select specific validators
python -m tooling.validator --rules bga-senior-engineer-skill/rules/ --validators schema,priority

# Validate against the release document
python -m tooling.validator --rules bga-senior-engineer-skill/rules/ --validators release --release docs/ai-os/runtime-v1.1-release.md
```

---

## CLI Usage

```
usage: runtime_validator [-h] --rules RULES [--schema SCHEMA]
                         [--release RELEASE] [--report {human,json}]
                         [--output OUTPUT] [--ci] [--validators LIST]

Runtime Specification Validator

options:
  --rules RULES          Path to rules/ directory
  --schema SCHEMA        Path to schema definition (optional, embedded defaults)
  --release RELEASE      Path to release document for release validation
  --report {human,json}  Output format (default: human)
  --output OUTPUT        Write report to file (default: stdout)
  --ci                   CI mode: exit 1 on first failure, no report
  --validators LIST      Comma-separated list of validators to run (default: all)
                         Options: schema, rule_id, crossref, ownership,
                         priority, stats, release
```

---

## Exit Codes

| Code | Meaning |
|------|---------|
| 0 | All selected validators pass |
| 1 | Validation failure (any validator fails) |
| 2 | Runtime error (file not found, parse error, invalid arguments) |

---

## Validators

| Validator | CLI Name | Description |
|-----------|----------|-------------|
| Schema | `schema` | Required fields, field types, optional fields, extra-field detection |
| Rule ID | `rule_id` | Naming convention (`PREFIX-NNN`), uniqueness, prefix-file alignment |
| Cross-Reference | `crossref` | Unresolved references, self-references, circular references, orphan rules |
| Ownership | `ownership` | Valid domains, valid applies_to, duplicate prefix ownership, domain-prefix consistency |
| Priority | `priority` | Integer range 1-5, constitutional constraints |
| Statistics | `stats` | Aggregate statistics (always passes, always computed) |
| Release | `release` | Release document statistics match runtime |

---

## Report Formats

### Human (default)

```
=== Runtime Specification Validator ===
Version: 1.0.0
Runtime: v1.1
Timestamp: 2026-07-30T12:00:00Z

--- Schema Validation: PASS (0 errors) ---
--- Rule ID Validation: PASS (0 errors) ---
--- Cross-Reference Validation: FAIL (3 errors) ---
  ERROR: circular reference: ARCH-005 -> ARCH-006 -> ARCH-005
--- Ownership Validation: PASS (0 errors) ---
--- Priority Validation: PASS (0 errors) ---

=== Statistics ===
Total files: 12
Total rules: 185
Total lines: 4,600
Cross-references: 378

=== Result: FAIL (1 of 5 validators failed) ===
```

### JSON

```json
{
  "version": "1.0.0",
  "timestamp": "2026-07-30T12:00:00Z",
  "runtime_version": "1.1",
  "summary": {
    "total_validators": 5,
    "passed": 4,
    "failed": 1,
    "skipped": 0
  },
  "results": {
    "schema": { "status": "pass", "errors": [] },
    "crossref": {
      "status": "fail",
      "errors": [
        {"rule_id": null, "file": null, "reason": "circular reference: ...", "severity": "error"}
      ]
    }
  },
  "statistics": {
    "total_rules": 185,
    "total_files": 12,
    "total_lines": 4600
  }
}
```

### CI Mode

One line per failure, first 3 errors per validator, no summaries:

```
FAIL validator=crossref detail="circular reference: ARCH-005 -> ARCH-006 -> ARCH-005"
FAIL validator=crossref detail="circular reference: CORE-006 -> CORE-007 -> CORE-006"
```

---

## Current Behaviour — Cross-Reference Validator

The Cross-Reference Validator reports **circular references** for any bidirectional
`see_also` pair. In the Runtime Specification v1.1, many bidirectional references are
**intentional navigation aids** — they are placed so that an engineer reading one rule
is directed to a related rule, and vice versa. These improve cross-file discoverability
and are not defects.

When running all validators, `crossref` will report failures. To run only the
validators that are expected to pass against the v1.1 runtime:

```bash
python -m tooling.validator --rules bga-senior-engineer-skill/rules/ \
    --validators schema,rule_id,ownership,priority,release
```

---

## Extension Guide

### Adding a New Validator

1. Create `tooling/validator/src/validators/<name>.py`
2. Implement `validate(rules: RuleCollection) -> ValidatorResult`
3. Add the validator name to `VALIDATOR_NAMES` and `_VALIDATOR_MODULES` in `__main__.py`
4. Add to the `--validators` help text in `build_parser()`
5. Add tests in `tooling/validator/tests/`

### Adding a New Shared Component

1. Create `tooling/_shared/<component>.py`
2. Define the public API
3. Import in validator modules as needed
4. Update `docs/ai-os/runtime-tooling-architecture.md`

### Validator Contract

Every validator must expose:

```python
def validate(rules: RuleCollection) -> ValidatorResult:
    """Run validation.

    Args:
        rules: Loaded rule collection with full cross-reference index.

    Returns:
        ValidatorResult with status and error list.
    """
```

Validators must be **stateless** — no module-level state, no mutation of the
`RuleCollection`. All state must be local to the `validate` function.

---

## Architecture Overview

```
tooling/
  _shared/
    types.py          Data models (Rule, RuleCollection, ValidationError, etc.)
    schema.py         Schema v1.1 definitions, canonical lists, type checks
    loader.py         Rule file loader, RuleCollection builder
    registry.py       RuleRegistry with cross-reference and prefix indices
    markdown.py       Markdown table parser for release documents
  validator/
    __main__.py       CLI entry point
    src/
      __main__.py     CLI orchestration (argparse, dispatch, reports)
      validators/
        schema_validator.py
        rule_id_validator.py
        crossref_validator.py
        ownership_validator.py
        priority_validator.py
        stats_generator.py
        release_validator.py
    tests/
      fixtures/       Test data files
      test_*.py       Test suites
```

---

## Dependencies

- **Python 3.10+**
- **Standard library only** — `json`, `re`, `pathlib`, `dataclasses`, `argparse`, `collections`, `datetime`

Zero external dependencies.
