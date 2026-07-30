# Runtime Validator

Validates that Runtime Specification v1.1 JSON rule files remain internally consistent.

Part of the Runtime Tooling Platform. See `docs/ai-os/runtime-tooling-architecture.md` for the platform architecture.

## Quick Start

```
python -m tooling.validator --rules ../../bga-senior-engineer-skill/rules/ --report human
python -m tooling.validator --rules ../../bga-senior-engineer-skill/rules/ --report json --output report.json
python -m tooling.validator --rules ../../bga-senior-engineer-skill/rules/ --ci
```

## Exit Codes

| Code | Meaning |
|---|---|
| 0 | All validators pass |
| 1 | Validation failure (any validator fails) |
| 2 | Runtime error (file not found, parse error) |

## Usage

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

## Validators

| Validator | Checks |
|---|---|
| schema | Required fields, field types, optional fields, schema version |
| rule_id | Uniqueness, naming convention, gaps, duplicates |
| crossref | Unresolved references, self-references, circular references, orphan rules |
| ownership | Duplicate ownership, invalid applies_to, invalid domain values |
| priority | Valid range (1-5), constitutional constraints, illegal values |
| stats | Generate aggregate statistics from runtime files |
| release | Verify release document statistics match runtime |

## CI Integration

```yaml
# GitHub Actions example
- name: Validate runtime specification
  run: |
    python -m tooling.validator \
      --rules bga-senior-engineer-skill/rules/ \
      --ci
```

The CI mode runs all validators, exits on first failure, and prints only the failing validator name and reason.

## Dependencies

- Python 3.10+
- Standard library only (json, re, pathlib, dataclasses)

No external dependencies.
