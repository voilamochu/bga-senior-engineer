<?php
namespace AGR\Cards\D;

class D5_FieldClay extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D5_FieldClay';
    $this->name = clienttranslate('Field Clay');
    $this->deck = 'D';
    $this->number = 5;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [clienttranslate('You immediately get 1 <CLAY> for each planted field you have.')];
    $this->passing = true;
    $this->cost = [
      FOOD => 1,
    ];
    $this->prerequisite = clienttranslate('1 Planted Field');
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $fields = count($player->board()->getPlantedFields());      
    if ($fields == 0) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }
  
  public function onBuy($player)
  {
    $fields = count($player->board()->groupFields($player->board()->getPlantedFields()));      
    return $this->gainNode([CLAY => $fields]);
  }
}
