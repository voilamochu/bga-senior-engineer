<?php
namespace AGR\Cards\D;

class D77_RecycledBrick extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D77_RecycledBrick';
    $this->name = clienttranslate('Recycled Brick');
    $this->deck = 'D';
    $this->number = 77;
    $this->isCorbariusOrDulcinaria = true;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time any player (including you) renovates to stone, you get 1 <CLAY> for each newly renovated room.'
      ),
    ];
    $this->cost = [
      FOOD => 1,
    ];
    $this->prerequisite = clienttranslate('3 Occupations');
    $this->occupationPrerequisites = ['min' => 3];
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Renovation', null);
  }

  public function onAfterRenovation($player, $event)
  {
    $player = $this->getPlayer();
    if ($event['newRoomType'] != 'roomStone') {
      return null;
    }

    return $this->gainNode([CLAY => count($event['rooms'])]);
  }
}
