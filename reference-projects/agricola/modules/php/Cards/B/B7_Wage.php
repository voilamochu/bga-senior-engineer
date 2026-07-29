<?php
namespace AGR\Cards\B;

class B7_Wage extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B7_Wage';
    $this->name = clienttranslate('Wage');
    $this->deck = 'B';
    $this->number = 7;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'You immediately get 2 <FOOD> and 1 additional <FOOD> for each major improvement you have from the bottom row of the supply board.'
      ),
    ];
    $this->passing = true;
    $this->isArtifexOrBubulcus = true;
  }

  public function onBuy($player)
  {  
	$bonus = 0;
	
	foreach ($player->getCards(MAJOR, true) as $major) {
      if (in_array(
            $major->getId(),
            ['Major_ClayOven','Major_StoneOven','Major_Joinery','Major_Pottery','Major_Basket']
          ))
      {
	    $bonus++;  
      }
	}
	
	return $this->gainNode([FOOD => 2+$bonus]);
  }
}
