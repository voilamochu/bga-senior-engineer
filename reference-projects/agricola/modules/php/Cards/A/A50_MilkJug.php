<?php
namespace AGR\Cards\A;
use AGR\Managers\Players;

class A50_MilkJug extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A50_MilkJug';
    $this->name = clienttranslate('Milk Jug');
    $this->deck = 'A';
    $this->number = 50;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time any player (including you) uses the __Cattle Market__ accumulation space, you get 3 <FOOD>, and each other player gets 1 <FOOD>.'
      ),
    ];
    $this->cost = [
      CLAY => 1,
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'CattleMarket', null);
  }

  protected function onAction($player, $event)
  {
    $flow = [];
    foreach (Players::getAll() as $pId2 => $player2) {
      $n = $this->pId == $pId2 ? 3 : 1;
      $flow[] = $this->gainNode([FOOD => $n], $pId2);
    }

    return ['type' => NODE_SEQ, 'childs' => $flow];
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    return $this->onAction($player, $event);
  }

  public function onOpponentPlaceFarmer($player, $event)
  {
    return $this->onAction($player, $event);
  }
}
