<?php
namespace AGR\Cards\C;

class C10_BunkBeds extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C10_BunkBeds';
    $this->name = clienttranslate('Bunk Beds');
    $this->deck = 'C';
    $this->number = 10;
    $this->category = FARM_PLANNER;
    $this->desc = [clienttranslate('Once you have 4 rooms, your house can hold 5 people.')];
    $this->cost = [
      WOOD => '1',
    ];
    $this->prerequisite = clienttranslate('2 Major Improvements');
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if ($player -> getCards(MAJOR,true)->count() <2) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }
}