<?php
namespace AGR\Cards\C;
use AGR\Managers\Players;

class C38_Christianity extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C38_Christianity';
    $this->name = clienttranslate('Christianity');
    $this->deck = 'C';
    $this->number = 38;
    $this->category = POINTS_PROVIDER;
    $this->desc = [clienttranslate('When you play this card, all other players get 1 <FOOD> each.')];
    $this->vp = 2;
    $this->prerequisite = clienttranslate('Exactly 1 Sheep');
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if ($player->countAnimalsOnBoard()[SHEEP] != 1) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    $flow = [];
    foreach (Players::getAll() as $pId2 => $player2) {
      if ($this->pId == $pId2) {
        continue;
      }
      $flow[] = $this->gainNode([FOOD => 1], $pId2);
    }

    if ($flow != []) {
      return ['type' => NODE_SEQ, 'childs' => $flow];
    }
  }
}
