# Runtime Tooling Platform — Architecture

**Type:** Platform architecture document
**Version:** 1.0
**Status:** ADOPTED
**Date:** 2026-07-29
**Runtime Specification:** v1.1 (frozen)

---

## 1. Overview

### Purpose

The Runtime Tooling Platform is a suite of tools that operate on the Runtime Specification v1.1 rule files. These tools validate, analyze, transform, synchronize, and publish the runtime specification. The platform is designed for extensibility: future tools can be added without restructuring existing ones.

### Design Principles

| Principle | Rationale |
|---|---|
| **Modular by tool** | Each tool is independently installable, runnable, and versioned. |
| **Shared core** | Common infrastructure (loading, parsing, reporting) lives in a shared module and is reused by every tool. |
| **No cyclic dependencies** | Shared modules depend on nothing. Tools depend on shared modules. Tools never depend on other tools. |
| **Runtime JSON files are immutable inputs** | No tool writes to runtime JSON files. Tools read only. Output goes to reports, artifacts, or planning documents. |
| **Deterministic output** | Same runtime files always produce identical tool output. |
| **CI-native** | Every tool supports human-readable and JSON output with proper exit codes. |

---

## 2. Directory Structure

```
tooling/
  _shared/                    # Shared infrastructure (reusable across tools)
    __init__.py
    loader.py                 # Rule file loader — parses JSON, builds RuleCollection
    schema.py                 # Schema v1.1 field definitions and validation primitives
    registry.py               # Rule ID registry, cross-reference index builder
    stats_engine.py           # Statistics engine — aggregates across rule collection
    report.py                 # Report formatters (human, JSON, CI)
    cli.py                    # Shared CLI framework (argparse wrapper, exit codes)
    markdown.py               # Markdown table parser (for release doc parsing)
    types.py                  # Shared data types (Rule, RuleCollection, ValidationError, etc.)

  validator/                  # TOOL 1: Runtime Validator
    README.md                 # Usage guide
    architecture.md           # Validator-specific architecture
    validator-spec.md         # Validator specifications (7 validators)
    src/
      __init__.py
      __main__.py             # CLI entry point
      validators/
        __init__.py
        schema_validator.py
        rule_id_validator.py
        crossref_validator.py
        ownership_validator.py
        priority_validator.py
        stats_generator.py
        release_validator.py

  audit/                      # TOOL 2: (reserved) Runtime Auditor
                              # Deep cross-file consistency auditing
                              # Generates certification-grade reports

  publisher/                  # TOOL 3: (reserved) Release Publisher
                              # Generates release artifacts from runtime files
                              # Validates release document statistics

  synchronizer/               # TOOL 4: (reserved) Documentation Synchronizer
                              # Detects drift between planning docs and runtime
                              # Suggests updates to planning documents

  generator/                  # TOOL 5: (reserved) Rule Generator
                              # Scaffolds new rule files from templates
                              # Validates schema compliance during generation

  migration/                  # TOOL 6: (reserved) Migration Assistant
                              # Analyzes legacy BGA code and suggests extraction targets
                              # Maps to MIGR-011 threshold rules

  utilities/                  # TOOL 7: (reserved) Utility Suite
    runtime-diff.py           # Diff two versions of the runtime specification
    graph-visualizer.py       # Generate dependency graph (DOT/PlantUML) from cross-references
    schema-migration.py       # Migrate rule files between schema versions
    stats-report.py           # Generate standalone statistics report
```

---

## 3. Tool Specifications

### 3.1 Validator

**Purpose:** Verify runtime rule files are internally consistent and conform to Schema v1.1.

**Status:** Design complete. Ready for implementation.

**Responsibilities:**
- Schema validation (required fields, field types, optional fields)
- Rule ID validation (uniqueness, naming convention, prefix-file alignment)
- Cross-reference validation (unresolved references, circular references, self-references)
- Ownership validation (applies_to canonical values, domain alignment)
- Priority validation (valid range 1-5, constitutional constraints)
- Statistics generation (rule counts, line counts, distributions)
- Release validation (release document statistics match actual runtime)

**Future scope:**
- Integration with CI pipelines
- Pre-commit hook support
- IDE plugin integration
- Watch mode (re-validate on file change)

### 3.2 Audit (reserved)

**Purpose:** Perform deep cross-file consistency audits of the runtime specification.

**Distinction from Validator:** The validator checks structural correctness (schema, IDs, references). The auditor checks semantic consistency (rule wording conflicts, ownership boundary violations, concept drift across files). The auditor generates the kind of report produced by the final certification audit.

**Expected responsibilities:**
- Detect rule wording contradictions across files
- Flag ownership boundary violations between domains
- Identify concept drift (same concept described differently in different files)
- Generate certification-grade audit reports
- Track finding resolution across audit runs

### 3.3 Publisher (reserved)

**Purpose:** Generate and validate release artifacts from the runtime rule files.

**Expected responsibilities:**
- Extract release statistics (rule counts, line counts) from runtime files
- Validate release document statistics match runtime
- Generate release summary in multiple formats (Markdown, JSON)
- Track release history and changelog
- Verify release document references resolve

### 3.4 Synchronizer (reserved)

**Purpose:** Detect and report drift between planning documents and the runtime rule files.

**Expected responsibilities:**
- Parse planning documents for rule ID references, counts, and statistics
- Compare planning document values against actual runtime
- Generate diff reports showing what changed
- Suggest updates to planning documents that are out of sync
- Track synchronization status across the document set

### 3.5 Generator (reserved)

**Purpose:** Scaffold new rule files and rules from templates.

**Expected responsibilities:**
- Generate new rule files following Schema v1.1
- Generate new rules within existing files following domain conventions
- Validate generated output against Schema v1.1 before writing
- Assign next available rule ID within the prefix sequence
- Update cross-reference index scaffolding

### 3.6 Migration Assistant (reserved)

**Purpose:** Analyze legacy BGA code against runtime rules and suggest extraction targets.

**Expected responsibilities:**
- Parse legacy Game.php for extraction signals (MIGR-011 thresholds)
- Identify Manager boundaries based on table ownership
- Suggest extraction order following MIGR-014 sequence
- Generate migration task tickets
- Track migration completeness against completion criteria (MIGR-019)

### 3.7 Utilities (reserved)

**runtime-diff:** Compare two rule sets (e.g. v1.0 vs v1.1) and report added, removed, or changed rules.

**graph-visualizer:** Generate a DOT or PlantUML graph of the cross-reference dependency graph. Useful for understanding coupling between files and identifying clusters.

**schema-migration:** Migrate rule files between schema versions. For example, if Schema v1.2 adds a new field, this tool adds the field to every rule with a default value.

**stats-report:** Standalone statistics report generator. Produces the same statistics as the validator's stats_generator but as a standalone tool for CI dashboards.

---

## 4. Shared Infrastructure

### 4.1 Component Map

```
                    ┌──────────────────────────────────────────────────┐
                    │                  CLI (cli.py)                    │
                    │  argparse wrapper, exit codes, output routing    │
                    └────────────┬─────────────────────────┬───────────┘
                                 │                         │
                    ┌────────────▼─────────┐   ┌───────────▼───────────┐
                    │   Loader (loader.py) │   │  Report (report.py)   │
                    │  File discovery,     │   │  Human, JSON, CI      │
                    │  JSON parsing,       │   │  formatters           │
                    │  RuleCollection      │   └───────────────────────┘
                    └────────────┬─────────┘
                                 │
                    ┌────────────▼─────────┐
                    │ Schema (schema.py)   │
                    │ Field definitions,   │
                    │ type validators      │
                    └────────────┬─────────┘
                                 │
                    ┌────────────▼─────────┐
                    │ Registry (registry.py)│
                    │ ID index,             │
                    │ cross-ref index,      │
                    │ domain index          │
                    └────────────┬─────────┘
                                 │
                    ┌────────────▼──────────────┐
                    │ Stats Engine (stats.py)   │
                    │ Aggregations,             │
                    │ distributions, gaps       │
                    └───────────────────────────┘
```

### 4.2 Component Specifications

#### types.py

Shared data types used by all components and all tools.

| Type | Description |
|---|---|
| Rule | Dataclass holding all fields of a single rule |
| RuleCollection | Dataclass holding all loaded rules, file metadata, and indices |
| FileInfo | Dataclass holding file-level metadata (path, domain, version, etc.) |
| ValidationError | Dataclass for a single validation error (validator, rule_id, file, reason, severity) |
| ValidatorResult | Dataclass for validator output (name, status, errors) |
| ReportFormat | Enum: HUMAN, JSON, CI |
| ExitCode | Constants: SUCCESS=0, FAILURE=1, ERROR=2 |

#### loader.py

Loads and parses rule files into a RuleCollection.

- Accepts a directory path or list of file paths
- Discovers all *.json files
- Parses each file as JSON
- Extracts file-level metadata
- Extracts each rule into a Rule dataclass
- Builds cross-reference index
- Builds domain index
- Returns RuleCollection

**Reusable by:** All tools. Every tool needs to load runtime files.

#### schema.py

Schema v1.1 field definitions and validation primitives.

- Defines required fields, optional fields, and field types per Schema v1.1
- Provides type-checking functions (is_string, is_integer, is_array_of_strings)
- Provides the extra-field detection function
- Provides the canonical component and domain lists

**Reusable by:** Validator (schema checks), Generator (output validation), Migration Assistant (target pattern validation).

#### registry.py

Builds and queries indices over the RuleCollection.

- Rule ID → Rule lookup
- Rule ID → incoming references (which rules reference this ID)
- Rule ID → outgoing references (this rule's see_also)
- Prefix → list of rule IDs
- Domain → list of rule IDs
- File → list of rule IDs

**Reusable by:** All tools that need to navigate the rule set.

#### stats_engine.py

Aggregates statistics from a RuleCollection.

- Per-file rule and line counts
- Priority distribution
- Tag distribution
- Applies_to distribution
- Cross-reference count
- ID gap analysis

**Reusable by:** Validator (stats generator), Publisher (release statistics), Synchronizer (drift detection).

#### report.py

Formats validation and analysis results for output.

- `format_human(results: list[ValidatorResult]) -> str`: Formatted human-readable report
- `format_json(results: list[ValidatorResult], statistics: dict) -> str`: JSON report string
- `format_ci(results: list[ValidatorResult]) -> str`: One-line-per-failure CI output
- `write_report(report: str, output: str | None)`: Write to file or stdout

**Reusable by:** All tools. Every tool produces output.

#### cli.py

Shared CLI framework.

- `build_parser(description: str, tools: list[ToolInfo]) -> ArgumentParser`: Build CLI parser with common arguments
- `parse_args(parser: ArgumentParser) -> Namespace`: Parse arguments
- `handle_exit(exit_code: int)`: Handle exit with proper code
- Common arguments: `--report`, `--output`, `--ci`

**Reusable by:** All CLI tools.

#### markdown.py

Markdown parsing utilities.

- `parse_table(markdown_text: str, table_header: str) -> list[dict]`: Parse a Markdown table into row dictionaries
- `find_section(markdown_text: str, section_header: str) -> str`: Extract a section from a Markdown document
- `extract_stat(markdown_text: str, label: str) -> str`: Extract a statistic value by label

**Reusable by:** Publisher (release doc parsing), Synchronizer (planning doc parsing).

### 4.3 Dependency Graph

```
types.py ──→ (no dependencies)
schema.py ──→ types.py
cli.py ──→ (no dependencies)
markdown.py ──→ (no dependencies)
loader.py ──→ types.py, schema.py
registry.py ──→ types.py
stats_engine.py ──→ types.py
report.py ──→ types.py

Validator tool ──→ loader.py, registry.py, schema.py, stats_engine.py, report.py, cli.py, types.py
Audit tool ──→ loader.py, registry.py, stats_engine.py, report.py, cli.py, types.py
Publisher tool ──→ loader.py, stats_engine.py, report.py, cli.py, markdown.py, types.py
Synchronizer tool ──→ loader.py, registry.py, stats_engine.py, report.py, cli.py, markdown.py, types.py
Generator tool ──→ loader.py, registry.py, schema.py, report.py, cli.py, types.py
Migration Assistant ──→ loader.py, registry.py, stats_engine.py, report.py, cli.py, types.py
Utilities ──→ loader.py, registry.py, stats_engine.py, report.py, types.py
```

No cyclic dependencies. The graph is strictly layered: types at the bottom, shared modules in the middle, tools at the top.

---

## 5. Extension Rules

### Adding a New Tool

1. Create `tooling/<tool-name>/` directory
2. Add tool-specific documentation (README, architecture, spec)
3. Create `tooling/<tool-name>/src/` with `__main__.py`
4. Import shared components from `tooling/_shared/`
5. Register tool in any orchestration layer (CI, launcher script)

**No existing tool or shared component needs modification.**

### Adding a New Shared Component

1. Create `tooling/_shared/<component>.py`
2. Define the component's public API
3. Update this architecture document's shared infrastructure section
4. Update the dependency graph

**No existing tool needs modification unless it uses the new component.**

### Adding a New Validator

1. Create `tooling/validator/src/validators/<name>.py`
2. Implement the `validate(rules: RuleCollection) -> ValidatorResult` contract
3. Add the validator name to the validators registry in __main__.py
4. Update `tooling/validator/validator-spec.md`

**No other tool or shared component needs modification.**

### Versioning

- Shared components follow the platform version
- Individual tools may have their own version independent of the platform
- Breaking changes to shared components require a platform major version bump
- New tools do not require a platform version bump

---

## 6. Reserved Tool Architectures

### 6.1 Audit Tool

```
tooling/audit/
  README.md
  architecture.md
  src/
    __main__.py
    auditors/
      contradiction_auditor.py    # Detect rule wording contradictions
      boundary_auditor.py         # Flag ownership boundary violations
      drift_auditor.py            # Detect concept drift across files
    report/
      audit_report.py             # Certification-grade report generator
```

### 6.2 Publisher Tool

```
tooling/publisher/
  README.md
  src/
    __main__.py
    extractor.py                  # Extract statistics from runtime
    validator.py                  # Validate release doc against runtime
    generator.py                  # Generate release artifacts
```

### 6.3 Synchronizer Tool

```
tooling/synchronizer/
  README.md
  src/
    __main__.py
    parser.py                     # Parse planning documents
    comparer.py                   # Compare against runtime
    differ.py                     # Generate diff reports
    updater.py                    # Suggest planning doc updates
```

### 6.4 Generator Tool

```
tooling/generator/
  README.md
  src/
    __main__.py
    scaffolder.py                 # Generate new rule files
    rule_generator.py             # Generate new rules within files
    id_assigner.py                # Assign next available rule ID
```

### 6.5 Migration Assistant

```
tooling/migration/
  README.md
  src/
    __main__.py
    legacy_parser.py              # Parse legacy codebase
    threshold_analyzer.py         # Identify extraction signals
    manager_identifier.py         # Identify Manager boundaries
    task_generator.py             # Generate migration tasks
```

---

## 7. Validator Relocation

The Runtime Validator has been moved from its temporary location to its permanent home.

| Artifact | Old Location | New Location |
|---|---|---|
| README.md | automation/runtime-validator/README.md | tooling/validator/README.md |
| architecture.md | automation/runtime-validator/architecture.md | tooling/validator/architecture.md |
| validator-spec.md | automation/runtime-validator/validator-spec.md | tooling/validator/validator-spec.md |

The temporary `automation/` directory has been removed.

### Updated References

The README examples were updated to reflect the new relative path to the rules directory:

```
# Old (automation/runtime-validator/):
python -m runtime_validator --rules ../../bga-senior-engineer-skill/rules/

# New (tooling/validator/):
python -m runtime_validator --rules ../../bga-senior-engineer-skill/rules/
```

The relative path is the same (`../../`) since the depth is the same (`tooling/validator/` vs `automation/runtime-validator/`). No other link updates were required.

---

## 8. Implementation Order

Recommended implementation sequence for the Runtime Tooling Platform:

| Order | Component | Rationale |
|---|---|---|
| 1 | types.py | Foundation data types. No dependencies. |
| 2 | schema.py | Field definitions. Depends only on types.py. |
| 3 | cli.py | CLI framework. No dependencies on other shared modules. |
| 4 | loader.py | Rule file loading. Depends on types.py and schema.py. |
| 5 | registry.py | Index building. Depends on types.py. |
| 6 | stats_engine.py | Statistics. Depends on types.py. |
| 7 | report.py | Output formatting. Depends on types.py. |
| 8 | markdown.py | Markdown parsing. No shared dependencies. |
| 9 | validator tool | First consumer. Validates all shared components. |
| 10+ | Future tools | Add as needed. No restructuring required. |

---

*End of tooling architecture. This document is part of the Runtime Specification v1.1 artifact set.*
