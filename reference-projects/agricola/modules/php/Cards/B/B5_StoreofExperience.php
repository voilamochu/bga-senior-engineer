<?php
namespace AGR\Cards\B;

class B5_StoreofExperience extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B5_StoreofExperience';
    $this->name = clienttranslate('Store of Experience');
    $this->deck = 'B';
    $this->number = 5;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate('If you have 0-4/5/6/7 occupations left in hand, you immediately get 1 <STONE>/<REED>/<CLAY>/<WOOD>.'),
    ];
    $this->passing = true;
    $this->isArtifexOrBubulcus = true;
    $this->bannedLiving = true;
  }
  
  public function onBuy($player)
  {
	$rewards = [STONE, STONE, STONE, STONE, STONE, REED, CLAY, WOOD];
    $occs = count($player->getHand(OCCUPATION));
	
	$args[$rewards[$occs]] = 1;
	
    return $this->gainNode($args);
  }
}
