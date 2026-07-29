<?php
namespace AGR\Cards\A;

class A57_MilkingParlor extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A57_MilkingParlor';
    $this->name = clienttranslate('Milking Parlor');
    $this->deck = 'A';
    $this->number = 57;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, if you have at least 1/3/4 <SHEEP>, you immediately get 2/3/4 <FOOD>. The same applies if you have at least 1/2/3 <CATTLE>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 2,
    ];
    $this->prerequisite = clienttranslate('At Least 4 Unused Farmyard Spaces');
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $unusedSpaces = count($player->board()->getFreeZones());

    if ($unusedSpaces < 4) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    $sheepFood = [0, 2, 2, 3, 4];
    $cattleFood = [0, 2, 3, 4];
    $sheep = min($player->countAnimalsOnBoard()[SHEEP], 4);
    $cattle = min($player->countAnimalsOnBoard()[CATTLE], 3);

    $n = $sheepFood[$sheep] + $cattleFood[$cattle];
    
    if ($n > 0) {
      return $this->gainNode([FOOD => $n]);
    }
  }
}
