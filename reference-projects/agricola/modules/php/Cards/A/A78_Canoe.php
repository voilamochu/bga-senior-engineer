<?php
namespace AGR\Cards\A;

class A78_Canoe extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A78_Canoe';
    $this->name = clienttranslate('Canoe');
    $this->deck = 'A';
    $this->number = 78;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Fishing__ accumulation space, you get an additional 1 <FOOD> and 1 <REED>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 2,
    ];
    $this->prerequisite = clienttranslate('1 Occupation');
    $this->occupationPrerequisites = ['min' => 1];
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'Fishing');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    return $this->gainNode([REED => 1, FOOD => 1]);
  }
}
