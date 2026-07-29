<?php
namespace AGR\Cards\A;

class A117_WoodCarrier extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A117_WoodCarrier';
    $this->name = clienttranslate('Wood Carrier');
    $this->deck = 'A';
    $this->number = 117;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate('When you play this card, you immediately get 1 <WOOD> for each improvement in front of you.'),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onBuy($player)
  {
    $n = $player->countAllImprovements();
	if ($n > 0) {
	  return $this->gainNode([WOOD => $n]);
	}
  }  
}
