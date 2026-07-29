<?php
namespace AGR\Cards\B;

class B57_Scullery extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B57_Scullery';
    $this->name = clienttranslate('Scullery');
    $this->deck = 'B';
    $this->number = 57;
    $this->category = FOOD_PROVIDER;
    $this->desc = [clienttranslate('At the start of each round, if you live in a wooden house, you get 1 <FOOD>.')];
    $this->cost = [
      WOOD => 1,
      CLAY => 1,
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) &&
      $event['type'] == 'StartOfTurn' &&
      $this->getPlayer()->getRoomType() == 'roomWood';
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    return $this->gainNode([FOOD => 1]);
  }
}
