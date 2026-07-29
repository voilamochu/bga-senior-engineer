<?php

namespace ARK\Cards\FinalScoring;

use ARK\Managers\Players;

class F017_InternationalZoo extends \ARK\Models\FinalScoring
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'F017_InternationalZoo';
    $this->number = 17;
    $this->name = clienttranslate('International Zoo');
    $this->icon = 'ALL-CONTINENTS';
    $this->desc = clienttranslate(
      'Gain <CONSERVATION:1> for each **continent icon** of which you have more than the player before you in turn order. Icons on **your partner zoos** count **twice**. Max. <CONSERVATION:4>'
    );
    $this->scoreMap = null;
  }

  public function isSupported($players, $options)
  {
    return count($players) > 1 && parent::isSupported($players, $options);
  }

  public function getQuantity()
  {
    $player = $this->getPlayer();
    $playerIcons = $player->countCardIcons();
    // Partner zoos count twice
    foreach ($player->getPartnerZoos() as $mId => $partner) {
      $continent = explode('-', $partner['type'])[1];
      $playerIcons[$continent]++;
    }

    $rightPlayer = Players::getPrevious($player);
    $rightIcons = $rightPlayer->countCardIcons();

    $qty = 0;
    foreach (CONTINENTS as $type) {
      if ($playerIcons[$type] > $rightIcons[$type]) {
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
