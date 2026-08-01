"""Shared deterministic fixtures for MS-06 validation tests.

Every fixture is self-contained: a scratch git repository stands in for
``bga-mercurio`` (the reference repository is never used by tests), and
the run is built through the real MS-01/02/03/05 modules with the
evidence collected and *marked* frozen — without the filesystem-level
chmod pass, so pytest's tmp-path teardown stays fast.  The frozen-state
markers (catalog ``frozen``, root hash, manifest freeze) are identical
to what ``collect --freeze`` produces.
"""

from __future__ import annotations

import json
from pathlib import Path

from tooling.harness.evidence.collect import (
    collect_evidence,
    evidence_root_hash,
    write_evidence_catalog,
)
from tooling.harness.runtime.manifest import new_run_manifest
from tooling.harness.runtime.run_dir import create_run_dir
from tooling.harness.runtime.status import RunStatus
from tooling.harness.safety.baseline import capture_baseline, save_baseline
from tooling.harness.util.hash import sha256_text

AT = "2026-07-31T12:00:00Z"
REFERENCE_HEAD = "a" * 40

# ----------------------------------------------------------------------
# Deterministic submissions
# ----------------------------------------------------------------------

# Fixture A: a NOT-02 submission satisfying every §3.11 criterion — four
# consolidation helpers, each notification type sent from exactly one
# method, expected call-site counts (4/2/2/3), no duplicated blocks, and
# no private payload keys.
PASSING_GAME_PHP = """<?php

class Game
{
    public function stPlayerTurn($player, $state)
    {
        $this->notifyLabOutputActivated($player, $state, 1);
    }

    public function actBuyResource($player, $state)
    {
        $this->notifyLabOutputActivated($player, $state, 2);
        $this->notifyMarketMilestone($player, $state);
    }

    public function actSellResource($player, $state)
    {
        $this->notifyMarketMilestone($player, $state);
    }

    public function applyBeam($player, $state)
    {
        $this->notifyLabOutputActivated($player, $state, 3);
        $this->notifySynergyMilestone($player, $state);
    }

    public function applyTap($player, $state)
    {
        $this->notifyLabOutputActivated($player, $state, 4);
        $this->notifySynergyMilestone($player, $state);
    }

    public function actChooseKeep($player, $state)
    {
        $this->notifyCardKept($player, $state, 1);
        $this->notifyCardKept($player, $state, 2);
        $this->notifyCardKept($player, $state, 3);
    }

    private function notifyLabOutputActivated($player, $state, $level)
    {
        $this->notifyAllPlayers('labOutputActivated', clienttranslate('${player_name}\\'s laboratory outputs activated'), [
            'player_name' => $player->name,
            'player_id' => $player->id,
            'level' => $level,
            'outputIndices' => $player->labOutputs,
        ]);
    }

    private function notifyMarketMilestone($player, $state)
    {
        $newPosition = $player->marketMarkerPosition;
        if (isset($state->marketMilestones[(string)$newPosition])) {
            switch ($newPosition) {
                case 2:
                    $this->notifyPlayer($player->id, 'reputationUpdate', '', [
                        'marketMarkerPosition' => $player->marketMarkerPosition,
                    ]);
                    break;
                case 3:
                    $this->notifyAllPlayers('influenceChanged', clienttranslate('${player_name} gained influence from market activity'), [
                        'player_name' => $player->name,
                        'influence' => $player->influence,
                    ]);
                    break;
                case 4:
                    $this->notifyPlayer($player->id, 'governorRewardGranted', '', [
                        'offeredTileCount' => 3,
                    ]);
                    break;
            }
        }
    }

    private function notifySynergyMilestone($player, $state)
    {
        $synergyPos = $player->synergyTrackPosition;
        if (isset($state->synergyMilestones[(string)$synergyPos])) {
            switch ($synergyPos) {
                case 4:
                    $this->notifyAllPlayers('influenceChanged', clienttranslate('${player_name} gained influence from synergy'), [
                        'player_name' => $player->name,
                        'influence' => $player->influence,
                    ]);
                    break;
                case 8:
                    $this->notifyAllPlayers('synergyMilestoneReached', clienttranslate('${player_name} reached a synergy milestone'), [
                        'player_name' => $player->name,
                        'position' => $synergyPos,
                    ]);
                    break;
            }
        }
    }

    private function notifyCardKept($player, $state, $path)
    {
        $this->notifyAllPlayers('cardKept', clienttranslate('${player_name} drew and kept a card'), [
            'player_name' => $player->name,
            'player_id' => $player->id,
            'keptCardId' => $player->keptCardId,
        ]);
    }
}
"""

# Fixture B: the same submission with a duplicated ``labOutputActivated``
# block reintroduced inline in ``stPlayerTurn`` (a verbatim copy of the
# helper's construction) — the single-source and duplication checks must
# fail (evaluation spec §3.11 failure conditions, "helpers added but
# call sites left inline").
DUPLICATED_GAME_PHP = PASSING_GAME_PHP.replace(
    """    public function stPlayerTurn($player, $state)
    {
        $this->notifyLabOutputActivated($player, $state, 1);
    }
""",
    """    public function stPlayerTurn($player, $state)
    {
        $level = 1;
        $this->notifyLabOutputActivated($player, $state, 1);
        $this->notifyAllPlayers('labOutputActivated', clienttranslate('${player_name}\\'s laboratory outputs activated'), [
            'player_name' => $player->name,
            'player_id' => $player->id,
            'level' => $level,
            'outputIndices' => $player->labOutputs,
        ]);
    }
""",
)

# Eval spec Appendix A inventory table declaring the changed file.
PASSING_SUBSYSTEMS_MD = """# Modified Subsystems

| File | Status (A/M/D) | Subsystem | Purpose |
|---|---|---|---|
| modules/php/Game.php | M | Game.php | Consolidate duplicated notification blocks into private helper methods |
"""

# A private payload key leak for the V9 scan.
LEAKY_GAME_PHP = PASSING_GAME_PHP.replace(
    """            'keptCardId' => $player->keptCardId,
        ]);""",
    """            'keptCardId' => $player->keptCardId,
            'hand' => $player->hand,
        ]);""",
)

# A valid, changed JS file for the B2 gate.
CHANGED_JS = "export function formatName(name) {\n    return name.trim();\n}\n"

# A malformed JSON artifact for the B3 gate.
MALFORMED_JSON = '{"broken": true,}\n'

EXTRA_FILE_PHP = "<?php\n// undeclared changed file\n"

GAMELOG_TEXT = "move 1: player P1 drew 3 cards\n"


def passing_submission(*, leaky: bool = False) -> dict:
    """Fixture A (and V9-leak variant) work/ files."""
    files = {
        "changes/modules/php/Game.php": PASSING_GAME_PHP if not leaky else LEAKY_GAME_PHP,
        "subsystems.md": PASSING_SUBSYSTEMS_MD,
    }
    return files


def duplicated_submission() -> dict:
    """Fixture B work/ files (duplicated notification block)."""
    return {
        "changes/modules/php/Game.php": DUPLICATED_GAME_PHP,
        "subsystems.md": PASSING_SUBSYSTEMS_MD,
    }


def undeclared_file_submission() -> dict:
    """Fixture C work/ files (changed file outside the declared inventory)."""
    files = passing_submission()
    files["changes/modules/php/extra.php"] = EXTRA_FILE_PHP
    return files


# ----------------------------------------------------------------------
# Frozen run builder
# ----------------------------------------------------------------------

def build_frozen_run(
    tmp_path,
    *,
    reference_repo: Path,
    work_files: dict[str, str],
    run_task: str = "NOT-02",
    model: str = "demo-model",
) -> tuple:
    """Build a run at the P4 frozen stage with the given submission.

    Returns ``(run, manifest, status)``.  The run's manifest and status
    carry completed P0-P3 phases, protocol artifacts, the safety
    baseline of *reference_repo*, the submission in ``work/``, and a
    collected, frozen evidence catalog (markers only — no chmod).
    """
    run = create_run_dir(run_task, model, tmp_path / "runs")
    manifest = new_run_manifest(run.run_id, run_task, model_id=model, started_at=AT)
    manifest.end_phase("p0", at=AT)
    for phase in ("p1", "p2", "p3"):
        manifest.start_phase(phase, at=AT)
        manifest.end_phase(phase, at=AT)
    manifest.update(
        prompt_bundle_sha256=sha256_text("bundle"),
        network="disabled",
        versions={**manifest.versions, "reference_head": REFERENCE_HEAD},
        submission_status="complete",
        execution={
            "platform": "stub", "agent_id": "stub", "model": model,
            "started_at": AT, "ended_at": AT, "duration_seconds": 1.0,
            "exit_status": "completed", "exit_code": 0, "restarts": 0,
            "session_hashes": {},
        },
    )
    protocol = run.root / "protocol"
    protocol.mkdir(parents=True, exist_ok=True)
    (protocol / "prompt-bundle.txt").write_text("bundle", encoding="utf-8")
    (protocol / "environment.json").write_text(
        json.dumps({
            "tools": [], "validator_version": "v1",
            "reference_head": REFERENCE_HEAD, "reference_status": "",
            "os": {"platform": "Linux", "release": "x", "architecture": "x"},
            "network": "disabled", "dependencies": [],
        }),
        encoding="utf-8",
    )
    session_dir = protocol / "session"
    session_dir.mkdir(parents=True, exist_ok=True)
    (session_dir / "transcript.txt").write_text("session transcript\n", encoding="utf-8")
    (session_dir / "raw-response.txt").write_text("raw\n", encoding="utf-8")
    (session_dir / "session.json").write_text(
        json.dumps({
            "platform": "stub", "exit_status": "completed", "exit_code": 0,
            "artifacts": {
                "transcript": {"sha256": sha256_text("session transcript\n")},
                "command_log": {"sha256": sha256_text("")},
            },
        }),
        encoding="utf-8",
    )
    (protocol / "command.log").write_text(
        json.dumps({"command": "true", "exit_code": 0, "stdout": "",
                    "stderr": "", "wall_time": 0.1}) + "\n",
        encoding="utf-8",
    )
    baseline = capture_baseline(reference_repo)
    save_baseline(baseline, run.baseline / "safety-baseline.json")
    (run.baseline / "workspace-baseline.diff").write_text("", encoding="utf-8")

    work = run.workspace_work
    for relpath, content in work_files.items():
        path = work / relpath
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(content, encoding="utf-8")
    (work / "reasoning.md").write_text("# Reasoning\n", encoding="utf-8")
    (work / "architecture.md").write_text("# Architecture\n", encoding="utf-8")
    (work / "testing-evidence.md").write_text("# Testing\n", encoding="utf-8")
    (work / "validation-evidence.md").write_text("# Validation\n", encoding="utf-8")
    (work / "declaration.json").write_text(
        json.dumps({
            "task_id": run_task, "status": "complete",
            "self_reported_time": "1h", "artifacts": [],
        }),
        encoding="utf-8",
    )

    status = RunStatus(run_id=run.run_id, updated_at=AT)
    status.transition("READY", checkpoint="p1", at=AT)
    status.transition("RUNNING", checkpoint="p2", at=AT)
    status.transition("COMPLETED", checkpoint="p3", at=AT)
    manifest.save(run.manifest_path)
    status.save(run.status_path)

    catalog = collect_evidence(run, manifest, status)
    catalog["frozen"] = True
    catalog["root_hash"] = evidence_root_hash(catalog)
    write_evidence_catalog(run, catalog)
    (run.evidence / "reruns").mkdir(parents=True, exist_ok=True)
    manifest.update(evidence_root_hash=catalog["root_hash"])
    manifest.freeze()
    manifest.save(run.manifest_path)
    return run, manifest, status


def load_validation(run) -> dict:
    """Load ``validation/validation.json`` of a gated run."""
    path = run.root / "validation" / "validation.json"
    return json.loads(path.read_text(encoding="utf-8"))
