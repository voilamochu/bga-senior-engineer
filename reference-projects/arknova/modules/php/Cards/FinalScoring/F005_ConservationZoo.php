<?php

namespace ARK\Cards\FinalScoring;

class F005_ConservationZoo extends \ARK\Models\FinalScoring
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [BASE];
    $this->id = 'F005_ConservationZoo';
    $this->number = 5;
    $this->name = clienttranslate('Conservation Zoo');
    $this->icon = 'PLACE-CUBE';
    $this->desc = clienttranslate('Gain <CONSERVATION> for **supporting conservation projects**.');
    $this->scoreMap = [3 => 1, 4 => 2, 5 => 3, 6 => 4];
  }

  public function getQuantity()
  {
    $player = $this->getPlayer();
    $spaces = $player->map()->getBonusSpaces();
    $removed = array_diff(array_keys($spaces), $player->getOccupiedBonusesSpaces());

    return count($removed);
  }
}
