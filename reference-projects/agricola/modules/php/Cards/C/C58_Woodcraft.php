<?php
namespace AGR\Cards\C;

class C58_Woodcraft extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C58_Woodcraft';
    $this->name = clienttranslate('Woodcraft');
    $this->deck = 'C';
    $this->number = 58;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use a wood accumulation space, if immediately afterward you have at most 5 <WOOD> in your supply, you get 1 <FOOD>.'
      ),
    ];
    $this->prerequisite = clienttranslate('1 Occupation');
    $this->occupationPrerequisites = ['min' => 1];
    $this->isCorbariusOrDulcinaria = true;

    $this->rulings = [
      clienttranslate('This effect is checked before cards that trigger “after”, e.g. __Tree Guard__ (C102).'),
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isCollectEvent($event, WOOD, true);
  }

  public function onPlayerImmediatelyAfterCollect($player, $event)
  {
    if ($player->countReserveResource(WOOD) <= 5) {
      return $this->gainNode([FOOD => 1]);
    }
  }
}
