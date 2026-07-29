<?php
namespace AGR\Cards\A;
use AGR\Managers\ActionCards;
use AGR\Managers\Meeples;

class A107_Catcher extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A107_Catcher';
    $this->name = clienttranslate('Catcher');
    $this->deck = 'A';
    $this->number = 107;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you place your 1st/2nd/3rd person in a round on a building resource accumulation space with exactly 5/4/3 building resources, you get 1 <FOOD>.'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
    $this->bannedWeak1or2p = true;
    $this->bannedWeak3or4p = true;
  }

  public function isListeningTo($event)
  {
    foreach ([WOOD, CLAY, REED, STONE] as $type) {
      if ($this->isBeforeCollectEvent($event, $type)) {
        return true;
      }
    }
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    $placed = $player->countPlacedFarmers();
    $n = 0;

    $meeples = Meeples::getResourcesOnCard($event['actionCardId']);
    foreach ($meeples as $meeple) {
      if (in_array($meeple['type'], [WOOD, CLAY, REED, STONE])) {
        $n++;
      }
    }

    if (($placed == 1 && $n == 5) ||
        ($placed == 2 && $n == 4) ||
        ($placed == 3 && $n == 3))
    {
      return $this->gainNode([FOOD => 1]);
    }
  }
}
