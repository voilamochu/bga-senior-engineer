<?php
namespace AGR\Cards\D;

class D52_RollingPin extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D52_RollingPin';
    $this->name = clienttranslate('Rolling Pin');
    $this->deck = 'D';
    $this->number = 52;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In the returning home phase of each round, if you have more <CLAY> than <WOOD> in your supply, you get 1 <FOOD>.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->prerequisite = clienttranslate('1 Occupation');
    $this->occupationPrerequisites = ['min' => 1];    
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'ReturnHome';
  }

  public function onPlayerReturnHome($player, $event)
  {
    $clay = $player->countReserveResource(CLAY);
    $wood = $player->countReserveResource(WOOD);
    
    if ($clay > $wood) {
      return $this->gainNode([FOOD => 1]);
    }
  }  
}
