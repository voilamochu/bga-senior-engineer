<?php
namespace AGR\Cards\E;

use AGR\Core\Globals;

class E65_Almsbag extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E65_Almsbag';
    $this->name = clienttranslate('Almsbag');
    $this->deck = 'E';
    $this->author = 'luki';
    $this->number = 65;
    $this->category = 'CROPS_-_GRAIN';
    $this->desc = [
      clienttranslate('When you play this card, you immediately get 1 <GRAIN> for every 2 completed rounds.'),
    ];
    $this->prerequisite = clienttranslate('No Occupations');
    $this->occupationPrerequisites = ['max' => 0];
    $this->implemented = true;
  }
  public function onBuy($player)
  {
    if(intdiv((Globals::getTurn()-1),2)>0){
	return $this->gainNode([GRAIN => intdiv((Globals::getTurn()-1),2)]);
    }
  }  
}
