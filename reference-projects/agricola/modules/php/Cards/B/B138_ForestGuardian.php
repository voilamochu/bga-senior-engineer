<?php
namespace AGR\Cards\B;
use AGR\Managers\Meeples;

class B138_ForestGuardian extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B138_ForestGuardian';
    $this->name = clienttranslate('Forest Guardian');
    $this->deck = 'B';
    $this->number = 138;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 2 <WOOD>. Each time before another player takes at least 5 <WOOD> from an accumulation space, they must first pay you 1 <FOOD>.'
      ),
    ];
    $this->players = '3+';
    $this->isArtifexOrBubulcus = true;
  }

  public function onBuy($player) 
  {
    return $this->gainNode([WOOD => 2]);
  }

  public function isListeningTo($event)
  {
    return $this->isBeforeCollectEvent($event, WOOD, 'opponent') &&
      Meeples::getResourcesOnCard($event['actionCardId'], null, WOOD)->count() >= 5;
  }

  public function onOpponentPlaceFarmer($player, $event)
  {
    return $this->payNode([FOOD => 1], null, 1, $this->getPlayer()->getId(), $player->getId());
  }  
}
