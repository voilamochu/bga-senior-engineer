<?php
namespace AGR\Cards\E;

class E163_Patroness extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E163_Patroness';
    $this->name = clienttranslate('Patroness');
    $this->deck = 'E';
    $this->author = 'beso';
    $this->number = 163;
    $this->category = 'BUILDING_RESOURCES';
    $this->desc = [
      clienttranslate(
        'Each time after you play an occupation after this one, you get 1 building resource of your choice.'
      ),
    ];
    $this->players = '4+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Occupation');
  }

  public function onPlayerAfterOccupation($player, $event)
  {
    if ($event['cardId'] != 'E163_Patroness'){
return [
      'type' => NODE_XOR,
      'childs' => [$this->gainNode([WOOD => 1]), $this->gainNode([CLAY => 1]),$this->gainNode([REED => 1]), $this->gainNode([STONE => 1])],
    ];
    }
  }
}
