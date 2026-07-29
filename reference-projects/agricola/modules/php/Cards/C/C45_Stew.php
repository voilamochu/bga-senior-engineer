<?php
namespace AGR\Cards\C;

class C45_Stew extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C45_Stew';
    $this->name = clienttranslate('Stew');
    $this->deck = 'C';
    $this->number = 45;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Day Laborer__ action space, also place 1 <FOOD> on each of the next 4 round spaces. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->cost = [
      CLAY => 1,
    ];
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'DayLaborer');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    return $this->futureMeeplesNode([FOOD => 1], 4);
  }
}
