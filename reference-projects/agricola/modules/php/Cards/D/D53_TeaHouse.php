<?php
namespace AGR\Cards\D;
use AGR\Core\Globals;

class D53_TeaHouse extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D53_TeaHouse';
    $this->name = clienttranslate('Tea House');
    $this->deck = 'D';
    $this->number = 53;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Once per round, you can skip placing your second person and get 1 <FOOD> instead. (You can place the person later that round.)'
      ),
    ];
    $this->vp = 2;
    $this->cost = [
      WOOD => 1,
      STONE => 1,
    ];
    $this->prerequisite = clienttranslate('Play in Round 6 or Later');
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $turn = Globals::getTurn();
    if ($turn < 6) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function canBeActivated()
  {
    $player = $this->getPlayer();

    if ($this->isFlagged() || $player->countPlacedFarmers() != 1) {
      return false;
    }

    return true;
  }

  public function isListeningTo($event)
  {
    return ($this->isPlayerEvent($event) && $event['type'] == 'StartOfTurn');
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    return $this->unflagCardNode();
  }
}
