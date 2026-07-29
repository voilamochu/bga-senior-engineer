<?php

namespace AGR\Cards\C;

use AGR\Core\Globals;
use AGR\Managers\Farmers;
use AGR\Managers\Meeples;
use AGR\Helpers\Utils;

class C22_BasketChair extends \AGR\Models\PlayerActionCard
{
  protected $type = MINOR;

  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C22_BasketChair';
    $this->name = clienttranslate('Basket Chair');
    $this->deck = 'C';
    $this->number = 22;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you can immediately move the first person you placed this work phase to this card (unless it is on __Meeting Place__). If you do, immediately afterward, you can place another person.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      REED => '1',
    ];
    $this->rulings = [
      clienttranslate(
        'If the first person you placed is on __Wish for Children__, only the adult is moved. The newborn stays on the space.'
      ),
    ];
    $this->isCorbariusOrDulcinaria = true;
    $this->privateSpace = true;
    $this->flow = [
        'type' => NODE_SEQ,
        'optional' => true,
        'childs' => [
          $this->setTurnIdNode(),
          [
            'action' => PLACE_FARMER,
            'args' => [],
            'source' => $this->name,
          ],
      ],
    ];
  }

  public function canBePlayed($player, $onlyCheckSpecificPlayer = null, $ignoreResources = false, $ignoreDoable = false)
  {
    $currentTurnId = Globals::getTurnId();
    $playedTurnId = $this->getExtraDatas('turnId') ?? '';

    // If we haven't recorded it yet, or it's a later round, disallow.
    if ($currentTurnId === '' || $playedTurnId === '' || $playedTurnId !== $currentTurnId) {
      return false;
    }

    return parent::canBePlayed($player, $onlyCheckSpecificPlayer, $ignoreResources);
  }

  public function onBuy($player)
  {
    if (!Globals::isWorkPhase()) {
      return;
    }

    $placed = Globals::getPlacedFarmers();
    $pId = $player->getId();

    if (!isset($placed[$pId]) || empty($placed[$pId])) {
      return;
    }

    $firstFarmerId = $placed[$pId][0] ?? null;
    if ($firstFarmerId === null) {
      return;
    }

    try {
      $farmer = Farmers::getMany($firstFarmerId)->first();
    } catch (\Exception $e) {
      return;
    }

    if ($farmer == null) {
      return;
    }

    $loc = $farmer['location'] ?? null;
    if ($loc == null) {
      return;
    }

    if (!Utils::isActionSpace($loc)) {
      return;
    }

    if (in_array($loc, ['ActionMeetingPlace', 'ActionMeetingPlaceSolo'])) {
      return;
    }

    // clean up Job Contract fake if we're moving Day Laborer + fake exists
    $needsJobContractCleanup = false;
    if ($loc === 'ActionDayLaborer') {
      $fakeId = Globals::getJobContractFake();
      if (!empty($fakeId)) {
        $meeples = Meeples::getMany($fakeId, false);
        if ($meeples->count() > 0) {
          $needsJobContractCleanup = true;
        }
      }
    }

    $childs = [];
    if ($needsJobContractCleanup) {
      $childs[] = [
        'action' => SPECIAL_EFFECT,
        'args' => [
          'cardId' => $this->id,
          'method' => 'cleanupJobContractFake',
        ],
      ];
    }

    $childs[] = [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'recordPlayedTurnId',
      ],
    ];

    $childs[] = $this->useActionSpaceNode($this->id, $farmer);

    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => $childs,
    ];
  }

  public function recordPlayedTurnId()
  {
    $this->setTurnIdMethod();
    $this->setExtraDatas('turnId', Globals::getTurnId());
  }

  public function cleanupJobContractFake()
  {
    Farmers::cleanupJobContractFake();
  }
}
