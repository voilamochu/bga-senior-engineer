<?php
namespace AGR\Cards\B;
use AGR\Core\Globals;

class B127_Seducer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B127_Seducer';
    $this->name = clienttranslate('Seducer');
    $this->deck = 'B';
    $this->number = 127;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'When you play this card in round 5 or later, you can immediately pay 1 <STONE>, 1 <GRAIN>, 1 <VEGETABLE>, and 1 <SHEEP> to take a __Family Growth Even without Room__ action.'
      ),
    ];
    $this->players = '3+';
    $this->holder = true;	    
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onBuy($player)
  {
    $turn = Globals::getTurn();
    
	if ($turn >= 5) {
      return [
        'type' => NODE_SEQ,
        'optional' => true,
		'childs' => [
          $this->payNode([STONE => 1, GRAIN => 1, VEGETABLE => 1, SHEEP => 1]),
          [
            'action' => WISHCHILDREN,
            'args' => ['cardLocation' => $this->id],
            'source' => $this->name            
          ]
		]
	  ];
	}
  }  
}
