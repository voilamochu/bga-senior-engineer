<?php
namespace AGR\Cards\A;

class A4_Baseboards extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A4_Baseboards';
    $this->name = clienttranslate('Baseboards');
    $this->deck = 'A';
    $this->number = 4;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'You immediately get 1 <WOOD> for each room you have. If you have more rooms than people, you get 1 additional <WOOD>.'
      ),
    ];
    $this->passing = true;
    $this->isArtifexOrBubulcus = true;
	$this->costs = [[FOOD => 2], [GRAIN => 1]];
  }
  
  public function onBuy($player)
  {
	$rooms = $player->countRooms();
	
	return $this->gainNode([WOOD => $rooms + ($rooms > $player->countFarmers())]);		
  }  
}
