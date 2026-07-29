<?php
namespace AGR\Cards\A;

class A7_GardenersKnife extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A7_GardenersKnife';
    $this->name = clienttranslate("Gardener's Knife");
    $this->deck = 'A';
    $this->number = 7;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'You immediately get 1 <FOOD> for each grain field you have and 1 <GRAIN> for each vegetable field you have.'
      ),
    ];
    $this->passing = true;
    $this->cost = [
      WOOD => 1,
    ];
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onBuy($player)
  {
    $grainFields = count($player->board()->getGrainFields());	
    $vegFields = count($player->board()->getVegetableFields());
	
    $args = ['pId' => $this->pId];
    if ($grainFields == 0 && $vegFields == 0) {
      return;
    }

    if ($grainFields != 0) {
      $args[FOOD] = $grainFields;
    }

    if ($vegFields != 0) {
      $args[GRAIN] = $vegFields;
    }

    return $this->gainNode($args);
  }  
}
