<?php
namespace AGR\Cards\B;

class B6_ExcursiontotheQuarry extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B6_ExcursiontotheQuarry';
    $this->name = clienttranslate('Excursion to the Quarry');
    $this->deck = 'B';
    $this->number = 6;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [clienttranslate('You immediately get a number of <STONE> equal to the number of people you have.')];
    $this->passing = true;
    $this->cost = [
      FOOD => 2,
    ];
    $this->prerequisite = clienttranslate('1 Occupation');
    $this->occupationPrerequisites = ['min' => 1];		
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onBuy($player)
  {
	$farmers = $player->countFarmers();
	
	return $this->gainNode([STONE => $farmers]);		
  }    
}
