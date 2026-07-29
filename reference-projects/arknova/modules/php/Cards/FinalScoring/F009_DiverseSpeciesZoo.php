<?php

namespace ARK\Cards\FinalScoring;

use ARK\Managers\Players;

class F009_DiverseSpeciesZoo extends \ARK\Models\FinalScoring
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'F009_DiverseSpeciesZoo';
    $this->number = 9;
    $this->name = clienttranslate('Diverse species Zoo');
    $this->icon = 'ALL-ANIMALS';
    $this->desc = clienttranslate(
      'Gain <CONSERVATION:1> for each **animal category icon** of which you have more than the player before you in turn order. Max. <CONSERVATION:4>'
    );
    $this->scoreMap = null;
  }

  public function isSupported($players, $options)
  {
    return count($players) > 1;
  }

  public function getQuantity()
  {
    $player = $this->getPlayer();
    $playerIcons = $player->countCardIcons();

    $rightPlayer = Players::getPrevious($player);
    $rightIcons = $rightPlayer->countCardIcons();

    $qty = 0;
    foreach (ANIMAL_TYPES as $type) {
      if (($playerIcons[$type] ?? 0) > ($rightIcons[$type] ?? 0)) {
        $qty++;
      }
    }

    return $qty;
  }

  public function getScoreBonus()
  {
    $qty = $this->getQuantity();
    $bonus = min($qty, 4);

    if ($bonus != 0) {
      return [CONSERVATION => $bonus];
    } else {
      return null;
    }
  }
}
