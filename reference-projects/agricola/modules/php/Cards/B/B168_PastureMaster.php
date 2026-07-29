<?php
namespace AGR\Cards\B;

class B168_PastureMaster extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B168_PastureMaster';
    $this->name = clienttranslate('Pasture Master');
    $this->deck = 'B';
    $this->number = 168;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you renovate, you get 2 <FOOD> and 1 additional animal of the respective type in each of your pastures with stable.'
      ),
    ];
    $this->players = '4+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Renovation');
  }
  
  public function onPlayerAfterRenovation($player, $event)
  {
    $animals = $this->getAnimals($player);
    $gains = array_merge([FOOD => 2], $animals);
    
    return $this->gainNode($gains);
  }
    
  public function getAnimals($player)
  {
    $animals = [];
    $zones = $player->board()->getAnimalsDropZonesWithAnimals();
      
    foreach ($zones as $zone) {
      if ($zone['type'] != 'pasture' || count($zone['stables']) == 0) {
        continue;
      }

      foreach (ANIMALS as $type) {
        if ($zone[$type] > 0) {
          $animals[$type] = ($animals[$type] ?? 0) + 1;
        }
      }
    }
    
    return $animals;
  }
}
