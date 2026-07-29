<?php
namespace AGR\Cards\E;

class E43_BarnCats extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E43_BarnCats';
    $this->name = clienttranslate('Barn Cats');
    $this->deck = 'E';
    $this->author = 'beso';
    $this->number = 43;
    $this->category = 'FOOD_-_FUTURE_ROUND_SPACES';
    $this->desc = [
      clienttranslate(
        'If you have 1/2/3/4 stables, place 1 <FOOD> on each of the next 2/3/4/5 round spaces. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->vp = 1;
    $this->prerequisite = clienttranslate('1 Stable');
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $stables = $player->board()->countStablesForCards();
    if ($stables==0){
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    $stables = $player->board()->countStablesForCards();
    if ($stables==1){
      return $this->futureMeeplesNode([FOOD => 1],2);
    }
    elseif ($stables==2){
      return $this->futureMeeplesNode([FOOD => 1],3);
    }
    elseif ($stables==3){
      return $this->futureMeeplesNode([FOOD => 1],4);
    }
    elseif ($stables==4){
      return $this->futureMeeplesNode([FOOD => 1],5);
    }
  }
}
