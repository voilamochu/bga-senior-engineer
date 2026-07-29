<?php
namespace AGR\Cards\E;

use AGR\Managers\Meeples;

class E55_StoneWeir extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E55_StoneWeir';
    $this->name = clienttranslate('Stone Weir');
    $this->deck = 'E';
    $this->author = 'beso';
    $this->number = 55;
    $this->category = FOOD;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Fishing__ accumulation space, if there are 0/1/2/3 <FOOD> on the space, you get an additional 4/3/2/1 <FOOD> from the general supply.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      STONE => 1,
    ];
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'Fishing');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    $fishingFood = Meeples::getResourcesOnCard('ActionFishing', null, FOOD)->count();
    if ($fishingFood < 4){
      return $this->gainNode([FOOD => (4-$fishingFood)]);
    }
  }  
}
