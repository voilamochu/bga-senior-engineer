<?php
namespace AGR\Cards\D;
use AGR\Managers\Players;

class D68_SmallBasket extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D68_SmallBasket';
    $this->name = clienttranslate('Small Basket');
    $this->deck = 'D';
    $this->number = 68;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time after you use the __Reed Bank__ accumulation space, you can pay 1 <REED> to get 1 <VEGETABLE>. If you do in a game with 4+ players, place that 1 <REED> on the accumulation space.'
      ),
    ];
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];    
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer') &&
      $event['actionCardType'] == 'ReedBank';
  }
  
  public function onPlayerAfterPlaceFarmer($player, $event)
  {
    $playerCount = Players::count();
    $pay = ($playerCount >= 4) ? 
      $this->moveResourcesToSpaceNode([REED => 1], $event['actionCardId']) :
      $this->payNode([REED => 1]);

    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        $pay,
        $this->gainNode([VEGETABLE => 1]),
      ]
    ];
  }
}
