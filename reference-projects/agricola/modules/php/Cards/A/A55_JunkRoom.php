<?php
namespace AGR\Cards\A;

class A55_JunkRoom extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A55_JunkRoom';
    $this->name = clienttranslate('Junk Room');
    $this->deck = 'A';
    $this->number = 55;
    $this->category = FOOD_PROVIDER;
    $this->desc = [clienttranslate('Each time after you build an improvement, including this one, you get 1 <FOOD>.')];
    $this->cost = [
      WOOD => 1,
      CLAY => 1,
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isDuringActionEvent($event, 'Improvement');
  }

  public function onPlayerImprovement($player, $event)
  {
    return $this->gainNode([FOOD => 1]);
  }
}
