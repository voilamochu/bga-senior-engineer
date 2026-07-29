<?php
namespace AGR\Cards\B;

class B105_CaseBuilder extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B105_CaseBuilder';
    $this->name = clienttranslate('Case Builder');
    $this->deck = 'B';
    $this->number = 105;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 good of each of the following types, if you have at least 2 of that good in your supply already: <FOOD>, <GRAIN>, <VEGETABLE>, <REED>, <WOOD>.'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onBuy($player)
  {
    $resources = [FOOD, GRAIN, VEGETABLE, REED, WOOD];
	
	$args = [];
	foreach ($resources as $res) {
      if ($player->countReserveResource($res) >= 2) {
        $args[$res] = 1;
      }
    }
	
	if ($args != []) {
	  return $this->gainNode($args);
    }
  }  
}
