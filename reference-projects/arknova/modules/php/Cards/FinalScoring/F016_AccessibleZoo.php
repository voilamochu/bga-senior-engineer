<?php

namespace ARK\Cards\FinalScoring;

class F016_AccessibleZoo extends \ARK\Models\FinalScoring
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'F016_AccessibleZoo';
    $this->number = 16;
    $this->name = clienttranslate('Accessible Zoo');
    $this->icon = 'CONDITION';
    $this->desc = clienttranslate('Gain <CONSERVATION> for **conditions on cards** in your zoo.');
    $this->scoreMap = [4 => 1, 7 => 2, 10 => 3, 12 => 4];
  }

  public function getQuantity()
  {
    $player = $this->getPlayer();
    $conditions = 0;
    $cards = $player->getPlayedCards()->filter(function ($card) {
      return $card->getLocation() != 'rescueStation'; // MAP10
    });
    foreach ($cards as $card) {
      foreach ($card->getPrerequisites() as $cat => $n) {
        if (in_array($cat, [REPUTATION, APPEAL])) {
          $conditions++;
        } else {
          $conditions += $n;
        }
      }
    }

    return $conditions;
  }
}
