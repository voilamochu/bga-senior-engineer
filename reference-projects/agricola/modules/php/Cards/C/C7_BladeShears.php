<?php
namespace AGR\Cards\C;

class C7_BladeShears extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C7_BladeShears';
    $this->name = clienttranslate('Blade Shears');
    $this->deck = 'C';
    $this->number = 7;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate('You immediately get your choice of 3 <FOOD>, or 1 <FOOD> for each sheep you have. (Keep the sheep.)'),
    ];
    $this->passing = true;
    $this->cost = [
      WOOD => 1,
    ];
    $this->prerequisite = clienttranslate('1 Pasture');
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $pastures = count($player->board()->getPastures(true));
    if ($pastures == 0) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }
  
  public function onBuy($player)
  {
    $sheep = $player->countAnimalsOnBoard()[SHEEP];

    return [
      'type' => NODE_XOR,
      'childs' => [
        $this->gainNode([FOOD => 3]),
        $this->gainNode([FOOD => $sheep]),
      ]
    ];
  }
}
