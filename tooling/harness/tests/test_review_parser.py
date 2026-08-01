"""Tests for manual-review.md parsing (MS-07, MVB-021)."""

from tooling.harness.review.kit import MANUAL_REVIEW_TEMPLATE
from tooling.harness.review.parser import (
    parse_category_table,
    parse_critical_codes,
    parse_reviewer,
)


def _md_with(category_rows=None, reviewer="evaluator-1",
             critical_codes=None, **kwargs):
    md = MANUAL_REVIEW_TEMPLATE.read_text(encoding="utf-8")
    md = md.replace("| Reviewer |  |", f"| Reviewer | {reviewer} |")
    for category in ("Correctness", "Architecture", "Framework Compliance",
                     "Maintainability", "Testing"):
        row = category_rows.get(category, {}) if category_rows else {}
        score = str(row.get("score", ""))
        citations = row.get("citations", "")
        comments = row.get("comments", "")
        deductions = row.get("deductions", "")
        uncertainty = row.get("uncertainty", "")
        flag = "yes" if row.get("critical") else "no"
        md = md.replace(
            f"| {category} |  |  |  |  |  | no |",
            f"| {category} | {score} | {citations} | {comments} | "
            f"{deductions} | {uncertainty} | {flag} |",
        )
    if critical_codes:
        md = md.replace("- none", "\n".join(f"- {code}" for code in critical_codes))
    return md


class TestCategoryTable:
    def test_scaffold_parses_to_five_empty_records(self):
        md = MANUAL_REVIEW_TEMPLATE.read_text(encoding="utf-8")
        records = parse_category_table(md)
        assert [r.category for r in records] == [
            "Correctness", "Architecture", "Framework Compliance",
            "Maintainability", "Testing",
        ]
        assert all(r.score is None for r in records)
        assert all(r.evidence == [] for r in records)
        assert all(not r.critical_failure for r in records)

    def test_filled_rows_parse(self):
        md = _md_with(
            category_rows={
                "Correctness": {
                    "score": 80,
                    "citations": "evidence/e1-transcript.txt; evidence/e8-diff-bundle/modules/php/Game.php",
                    "comments": "solid",
                    "deductions": "one missing edge case",
                    "uncertainty": "low",
                },
                "Testing": {"score": 75, "citations": "evidence/e1-transcript.txt",
                            "critical": True},
            }
        )
        records = {r.category: r for r in parse_category_table(md)}
        correctness = records["Correctness"]
        assert correctness.score == 80
        assert correctness.evidence == [
            "evidence/e1-transcript.txt",
            "evidence/e8-diff-bundle/modules/php/Game.php",
        ]
        assert correctness.comments == "solid"
        assert correctness.deductions == "one missing edge case"
        assert correctness.uncertainty == "low"
        assert correctness.critical_failure is False
        assert records["Testing"].critical_failure is True
        assert records["Architecture"].score is None

    def test_backticked_citations_are_cleaned(self):
        md = _md_with(category_rows={
            "Correctness": {"citations": "`evidence/e1-transcript.txt`,evidence/e2/subsystems.md"}
        })
        records = {r.category: r for r in parse_category_table(md)}
        assert records["Correctness"].evidence == [
            "evidence/e1-transcript.txt",
            "evidence/e2/subsystems.md",
        ]

    def test_dash_score_means_empty(self):
        md = _md_with(category_rows={"Correctness": {"score": "-"}})
        records = {r.category: r for r in parse_category_table(md)}
        assert records["Correctness"].score is None

    def test_non_integer_score_is_not_recorded(self):
        md = _md_with(category_rows={"Correctness": {"score": "about 80"}})
        records = {r.category: r for r in parse_category_table(md)}
        assert records["Correctness"].score is None


class TestCriticalCodes:
    def test_scaffold_yields_no_codes(self):
        md = MANUAL_REVIEW_TEMPLATE.read_text(encoding="utf-8")
        assert parse_critical_codes(md) == []

    def test_bulleted_codes_are_captured(self):
        md = _md_with(critical_codes=["C4 hidden information leak", "C6 ordering"])
        assert parse_critical_codes(md) == ["C4", "C6"]

    def test_instruction_prose_is_not_captured(self):
        # the template instructions mention `- C4` as an example; prose
        # lines must never be parsed as entries
        md = MANUAL_REVIEW_TEMPLATE.read_text(encoding="utf-8")
        md = md.replace("- none", "- C4 real finding")
        assert parse_critical_codes(md) == ["C4"]


class TestReviewer:
    def test_reviewer_field(self):
        assert parse_reviewer(_md_with(reviewer="evaluator-7")) == "evaluator-7"

    def test_empty_reviewer_is_none(self):
        md = MANUAL_REVIEW_TEMPLATE.read_text(encoding="utf-8")
        assert parse_reviewer(md) is None
