<?php
namespace AGR\Cards\D;

use AGR\Core\Engine;
use AGR\Core\Globals;
use AGR\Managers\PlayerCards;

class D161_CabbageBuyer extends \AGR\Models\Occupation
{
  private const TRACKER_KEY = 'cabbageBuyerTracker';

  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D161_CabbageBuyer';
    $this->name = clienttranslate('Cabbage Buyer');
    $this->deck = 'D';
    $this->number = 161;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time any player (including you) renovates and then builds no/1 minor/1 major improvement, you can buy 1 <VEGETABLE> for 3/2/1 <FOOD>.'
      ),
    ];
    $this->players = '4+';
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Renovation', null) ||
      $this->isActionEvent($event, 'Improvement', null);
  }

  // ── Listener methods ──────────────────────────────────────────────

  public function onAfterRenovation($player, $event)
  {
    if (!Globals::isWorkPhase()) {
      return $this->getVegetableOfferFlow(3);
    }

    $this->recordRenovation($event);
    $this->scheduleOffersForPlayer($event['pId']);
  }

  public function onAfterImprovement($player, $event)
  {
    $kind = $this->getImprovementKind($event['cardId'] ?? null);
    if (!$kind) {
      return;
    }

    $this->markLatestRenovation($event['pId'], $kind);
  }

  // ── SPECIAL_EFFECT entry point ────────────────────────────────────

  public function resolveEntry($entryId)
  {
    $tracker = $this->getTracker();
    $entry = $tracker['entries'][$entryId] ?? null;
    if (!$entry) {
      return;
    }

    $foodCost = ['minor' => 2, 'major' => 1][$entry['improvement']] ?? 3;

    // Remove this entry and any duplicates with same sig
    $sig = $entry['sig'] ?? '';
    $pId = $entry['pId'] ?? null;
    foreach ($tracker['entries'] as $id => $e) {
      if ($id == $entryId || (($e['pId'] ?? null) == $pId && ($e['sig'] ?? '') == $sig)) {
        unset($tracker['entries'][$id]);
      }
    }
    $this->setTracker($tracker);

    Engine::insertAsChild($this->getVegetableOfferFlow($foodCost));
  }

  // ── Tracker operations ────────────────────────────────────────────

  private function getTracker()
  {
    $t = $this->getExtraDatas(self::TRACKER_KEY);
    if (!is_array($t) || !isset($t['entries'])) {
      return ['nextId' => 1, 'entries' => []];
    }
    return $t;
  }

  private function setTracker($tracker)
  {
    $this->setExtraDatas(self::TRACKER_KEY, $tracker);
  }

  private function recordRenovation($event)
  {
    $pId = $event['pId'];
    $sig = $this->makeSignature($event);
    $tracker = $this->getTracker();

    // Dedup: same pId + sig = same renovation called again from getFlow()
    foreach ($tracker['entries'] as $entry) {
      if ($entry['pId'] == $pId && $entry['sig'] == $sig) {
        return;
      }
    }

    $nested = $this->isNestedInImprovementAction($pId);
    $entryId = $tracker['nextId']++;
    $tracker['entries'][$entryId] = [
      'pId' => $pId,
      'sig' => $sig,
      'improvement' => null,
      'nested' => $nested,
      'scheduled' => false,
    ];
    $this->setTracker($tracker);
  }

  /**
   * Mark the latest eligible renovation for this player with an
   * improvement kind. The SPECIAL_EFFECT reads improvement at
   * resolution time, so it's safe to mark after scheduling.
   */
  private function markLatestRenovation($pId, $kind)
  {
    $tracker = $this->getTracker();
    $ids = array_keys($tracker['entries']);
    for ($i = count($ids) - 1; $i >= 0; $i--) {
      $id = $ids[$i];
      $entry = $tracker['entries'][$id];
      if ($entry['pId'] != $pId) {
        continue;
      }
      if ($entry['nested'] ?? false) {
        continue;
      }
      if ($entry['improvement'] !== null) {
        continue;
      }

      $tracker['entries'][$id]['improvement'] = $kind;
      $this->setTracker($tracker);
      return;
    }
  }

  private function scheduleOffersForPlayer($pId)
  {
    $tracker = $this->getTracker();
    $toSchedule = [];

    foreach ($tracker['entries'] as $id => $entry) {
      if ($entry['pId'] != $pId) {
        continue;
      }
      if ($entry['scheduled'] ?? false) {
        continue;
      }

      $tracker['entries'][$id]['scheduled'] = true;
      $toSchedule[] = $id;
    }

    if (empty($toSchedule)) {
      return;
    }

    $this->setTracker($tracker);

    foreach ($toSchedule as $id) {
      Engine::insertAtRoot([
        'action' => SPECIAL_EFFECT,
        'pId' => $this->pId,
        'args' => [
          'cardId' => $this->id,
          'method' => 'resolveEntry',
          'args' => [$id],
        ],
      ]);
    }
  }

  // ── Helpers ───────────────────────────────────────────────────────

  private function getVegetableOfferFlow($foodCost)
  {
    return [
      'type' => NODE_SEQ,
      'pId' => $this->pId,
      'childs' => [
        [
          'type' => NODE_SEQ,
          'optional' => true,
          'forceConfirmation' => true,
          'pId' => $this->pId,
          'childs' => [
            $this->payNode([FOOD => $foodCost]),
            $this->gainNode([VEGETABLE => 1]),
          ],
        ],
      ],
    ];
  }

  private function makeSignature($event)
  {
    return implode('|', [
      $event['pId'] ?? 0,
      $event['oldRoomType'] ?? '',
      $event['newRoomType'] ?? '',
      $event['actionCardId'] ?? '',
    ]);
  }

  private function isNestedInImprovementAction($pId)
  {
    $node = Engine::getNextUnresolved();
    while (!is_null($node)) {
      if ($node->getAction() == IMPROVEMENT) {
        $nodePId = $node->getPId();
        if (is_null($nodePId) || $nodePId == $pId) {
          return true;
        }
      }
      $node = $node->getParent();
    }

    return false;
  }

  private function getImprovementKind($cardId)
  {
    if (!$cardId) {
      return null;
    }

    $card = PlayerCards::get($cardId);
    if ($card->hasType(MAJOR)) {
      return 'major';
    }

    if ($card->getType() == MINOR) {
      return 'minor';
    }

    return null;
  }
}
