<?php

namespace ARK\Cards\Sponsors;

use ARK\Managers\Buildings;

class S265_FranchiseBusiness extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'S265_FranchiseBusiness';
    $this->number = 265;
    $this->name = clienttranslate('Franchise Business');
    $this->lvl = 4;
    $this->effects = [
      IMMEDIATE => [
        clienttranslate('You may place 1 kiosk on your zoo map for free.'),
        clienttranslate('The usual building rules apply, including the distance rule for kiosks.')
      ],
      PASSIVE => [clienttranslate('In the income phase of each break, gain 1 money per kiosk all other players have on their zoo maps, no matter how much income these kiosks generate for their owners.')],
      ENDGAME => [clienttranslate('Gain 1 conservation point if you have 5 or more kiosks in your zoo.')],
    ];
    $this->prerequisites = [
      KIOSK => 1
    ];
  }

  public function getImmediate()
  {
    return [[KIOSK => 1]];
  }

  public function getIncome()
  {
    $player = $this->getPlayer();
    $otherPlayersKiosks = Buildings::getSelectQuery()->where('type', KIOSK)->where('player_id', '<>', $player->getId())->count();
    return [[MONEY => $otherPlayersKiosks]];
  }

  public function score()
  {
    $nKiosks = $this->getPlayer()
      ->map()
      ->getBuildingsOfType(KIOSK)
      ->count();

    if ($nKiosks >= 5) {
      $this->scoreConservation(1);
    }
  }
}
