<?php
namespace AGR\Cards\E;

use AGR\Managers\Farmers;

class E20_IronHoe extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E20_IronHoe';
    $this->name = clienttranslate('Iron Hoe');
    $this->deck = 'E';
    $this->author = 'chris';
    $this->number = 20;
    $this->category = 'FARMYARD_-_PLOWING';
    $this->desc = [
      clienttranslate(
        'At the end of each work phase, if you occupy both the __Grain Seeds__ and __Vegetable Seeds__ action spaces, you can plow 1 field.'
      ),
    ];
    $this->vp = 0;
    $this->cost = [
      WOOD => 1,
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'EndWorkPhase' &&
      !Farmers::getOnCard('ActionGrainSeeds', $this->getPlayer()->getId())->empty() &&
      !Farmers::getOnCard('ActionVegetableSeeds', $this->getPlayer()->getId())->empty();
  }

  public function onPlayerEndWorkPhase($player, $event)
  {
    return [
      'action' => PLOW,
      'cardId' => $this->id,
      'optional' => true,
    ];
  }
}
