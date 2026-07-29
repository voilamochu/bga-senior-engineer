<?php
namespace AGR\Cards\A;

class A8_FoodBasket extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A8_FoodBasket';
    $this->name = clienttranslate('Food Basket');
    $this->deck = 'A';
    $this->number = 8;
    $this->category = CROP_PROVIDER;
    $this->desc = [clienttranslate('You immediately get 1 <GRAIN> and 1 <VEGETABLE>.')];
    $this->passing = true;
    $this->cost = [
      REED => 1,
    ];
    $this->prerequisite = clienttranslate('2 Occupations and 2 Improvements');
    $this->occupationPrerequisites = ['min' => 2];
    $this->improvementPrerequisites = ['min' => 2];	
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onBuy($player)
  {
	return $this->gainNode([GRAIN => 1, VEGETABLE => 1]);		
  }    
}
