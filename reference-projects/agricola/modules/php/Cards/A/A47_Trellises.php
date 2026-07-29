<?php
namespace AGR\Cards\A;
use AGR\Managers\Fences;

class A47_Trellises extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A47_Trellises';
    $this->name = clienttranslate('Trellises');
    $this->deck = 'A';
    $this->number = 47;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Immediately place 1 <FOOD> on each of the next round spaces, up to the number of fences you have built. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onBuy($player) {
	$pId = $player->getId();
	$n = Fences::getOnBoard($pId)->count();
	
    return $this->futureMeeplesNode([FOOD => 1], $n);
  }
}
