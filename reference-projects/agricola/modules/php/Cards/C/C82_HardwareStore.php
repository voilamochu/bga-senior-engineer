<?php
namespace AGR\Cards\C;
use AGR\Helpers\CardRulings;

class C82_HardwareStore extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C82_HardwareStore';
    $this->name = clienttranslate('Hardware Store');
    $this->deck = 'C';
    $this->number = 82;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time after you use the __Day Laborer__ action space, you can pay 2 <FOOD> total to buy 1 <WOOD>, 1 <CLAY>, 1 <REED>, and 1 <STONE>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 1,
      CLAY => 1,
    ];
    $this->isCorbariusOrDulcinaria = true;

    $this->rulings = CardRulings::fromKeys([
      'HAPPENS_AFTER_COTTAGER',
    ]);
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer') && $event['actionCardType'] == 'DayLaborer';
  }

  public function onPlayerAfterPlaceFarmer($player, $args)
  {
    return $this->payGainNode([FOOD => 2], [WOOD => 1, CLAY => 1, REED => 1, STONE => 1]);
  }  
}
