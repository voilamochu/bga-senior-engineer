# Changelog

## 1.0.0 (2026-07-30)

Initial release of the Runtime Validator.

### Shared Infrastructure (`tooling/_shared/`)

- **types.py** — Shared data models: Rule, RuleCollection, FileInfo, ValidationError, ValidatorResult, ReportFormat, ExitCode
- **schema.py** — Schema v1.1 field definitions, canonical component and domain lists, prefix-to-domain mapping, type-checking primitives, extra-field detection
- **loader.py** — Rule file loader: JSON discovery, parsing, file header validation, RuleCollection construction, cross-reference index building
- **registry.py** — RuleRegistry: rule ID lookup, incoming/outgoing reference indices, prefix/domain/file-based queries
- **markdown.py** — Markdown table parser: section extraction, table parsing, statistic value lookup

### Schema Validator

- Validates file-level metadata (domain, version, last_updated, source, rules)
- Validates rule-level required fields, field types, array element types
- Validates optional field types when present
- Detects extra/unknown file-level and rule-level fields
- Enforces constitution-specific constraints (priority == 1, rationale required)
- "Did you mean?" suggestions for unknown field names

### Rule ID Validator

- Validates naming convention (`PREFIX-NNN` with zero-padded 3-digit number)
- Validates prefix-file alignment
- Detects duplicate rule IDs within a file
- Detects duplicate rule IDs across files
- Gap analysis (informational only, not validation failures)

### Cross-Reference Validator

- Detects unresolved see_also references
- Detects self-references
- Detects direct and indirect circular references (BFS traversal, depth limit 10 hops)
- Reports same-file references (informational)
- Reports orphan rules (warnings)

#### Known Limitation

The Cross-Reference Validator reports circular references for any bidirectional
`see_also` pairs. In the Runtime Specification v1.1, many such bidirectional
references are intentional navigation aids (e.g., "if you are reading rule A,
you should also read rule B, and vice versa"). These are not defects — they
improve cross-file discoverability. When running all validators, the crossref
validator may report failures even though the runtime is internally consistent.

To run only the validators that are expected to pass:

    python -m tooling.validator --rules rules/ --validators schema,rule_id,ownership,priority,release

### Ownership Validator

- Validates file-level domain values against canonical domain list
- Detects duplicate prefix ownership across files
- Validates domain-prefix consistency
- Validates applies_to values against canonical component list
- Detects empty applies_to
- Detects duplicate applies_to entries
- Validates applies_to array element types

### Priority Validator

- Validates priority is an integer in range 1-5
- Enforces constitutional constraint (priority == 1 for constitution.json)
- Enforces non-constitutional constraint (priority 1 is reserved for constitutional rules)
- Detects non-integer, out-of-range, and null priority values

### Statistics Generator

- Per-file statistics: file name, domain, rule count, line count, first/last rule ID, priority distribution
- Aggregate statistics: total files, rules, lines, priority/tag/applies_to distributions, cross-reference count, largest/smallest file, average rules per file
- Gap analysis: per-prefix numbering gaps (informational only)
- Always returns pass status — never fails

### Release Validator

- Parses the Runtime Inventory table from the release Markdown document
- Validates per-file rule counts and line counts
- Validates aggregate statistics (total rules, lines, files, cross-references, largest/smallest file)
- Validates schema version and runtime version
- Falls back to aggregate-only validation when the inventory table cannot be parsed

### CLI

- Single entry point: `python -m tooling.validator`
- Supports `--rules`, `--release`, `--report`, `--output`, `--ci`, `--validators`
- Human-readable report format
- JSON report format
- CI mode: one-line-per-failure, exit on first failure
- Exit codes: 0=SUCCESS, 1=FAILURE, 2=ERROR
- Zero external dependencies (stdlib only)
