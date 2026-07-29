<?php
namespace AGR\Cards\E;

class E122_Cottar extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E122_Cottar';
    $this->name = clienttranslate('Cottar');
    $this->deck = 'E';
    $this->author = 'chris';
    $this->number = 122;
    $this->category = 'BUILDING_RESOURCES_-_CLAY';
    $this->desc = [
      clienttranslate(
        'Each time you play or build an improvement, you get your choice of 1 <WOOD> or 1 <CLAY> immediately after paying its cost.'
      ),
    ];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Improvement');
  }
  
public function onPlayerAfterImprovement($player, $event)
  {
    return [
      'type' => NODE_XOR,
      'childs' => [$this->gainNode([WOOD => 1]), $this->gainNode([CLAY => 1])],
    ];
  }
}
