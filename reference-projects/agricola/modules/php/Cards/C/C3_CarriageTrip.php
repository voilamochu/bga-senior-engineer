<?php

namespace AGR\Cards\C;

use AGR\Core\Globals;
use AGR\Models\MinorImprovement;
use const PLACE_FARMER;

class C3_CarriageTrip extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C3_CarriageTrip';
    $this->name = clienttranslate('Carriage Trip');
    $this->deck = 'C';
    $this->number = 3;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate('If you play this card in the work phase, you can immediately place another person.'),
    ];
    $this->passing = true;
    $this->prerequisite = clienttranslate('1 Person yet to Place');
    $this->bannedStrong1or2p = true;
    $this->bannedStrong3or4p = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if (!$player->hasFarmerAvailable()) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    if (Globals::isWorkPhase()) {
      return [
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
  }
}
