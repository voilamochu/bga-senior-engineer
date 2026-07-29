<?php
namespace AGR\Cards\B;
use AGR\Managers\Farmers;

class B62_Pitchfork extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B62_Pitchfork';
    $this->name = clienttranslate('Pitchfork');
    $this->deck = 'B';
    $this->number = 62;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Grain Seeds__ action space, if the __Farmland__ action space is occupied you also get 3 <FOOD>.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'GrainSeeds');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    $farmersOnCard = Farmers::getOnCard('ActionFarmland');
    return $farmersOnCard->empty() ? null : $this->gainNode([FOOD => 3]);
  }
}
