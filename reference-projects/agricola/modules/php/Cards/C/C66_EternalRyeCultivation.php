<?php
namespace AGR\Cards\C;

class C66_EternalRyeCultivation extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C66_EternalRyeCultivation';
    $this->name = clienttranslate('Eternal Rye Cultivation');
    $this->deck = 'C';
    $this->number = 66;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'After each harvest in which you have 2 or 3+ <GRAIN> in your supply, you get 1 <FOOD> or 1 additional <GRAIN>, respectively.'
      ),
    ];
    $this->prerequisite = clienttranslate('1 Grain Field');
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $grainFields = $player->board()->getGrainFields();
    if (count($grainFields) < 1) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }
  
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'AfterHarvest';
  }

  public function onPlayerAfterHarvest($player)
  {
    $gain = [];
    $grain = $player->countReserveResource(GRAIN);
    
    if ($grain == 2) { $gain[FOOD] = 1; }
    if ($grain >= 3) { $gain[GRAIN] = 1; }
    
    if ($gain != []) {
      return $this->gainNode($gain);
    }
  }  
}
