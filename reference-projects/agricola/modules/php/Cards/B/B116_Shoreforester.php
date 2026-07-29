<?php
namespace AGR\Cards\B;
use AGR\Managers\Meeples;

class B116_Shoreforester extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B116_Shoreforester';
    $this->name = clienttranslate('Shoreforester');
    $this->deck = 'B';
    $this->number = 116;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card and each time 1 <REED> is placed on an empty __Reed Bank__ accumulation space in the preparation phase, you get 1 <WOOD>.'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
  }

  public function onBuy($player)
  {
    return $this->gainNode([WOOD => 1]);
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && 
      $event['type'] == 'Preparation';
  }

  public function onPlayerPreparation($player, $event)
  {
    if (Meeples::getResourcesOnCard('ActionReedBank')->count() == 0) {
      return $this->gainNode([WOOD => 1]);
    }
  }
}
