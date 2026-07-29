<?php
namespace AGR\Cards\D;

class D152_Patron extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D152_Patron';
    $this->name = clienttranslate('Patron');
    $this->deck = 'D';
    $this->number = 152;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Immediately before each time you play an occupation after this one (even before paying the occupation cost), you get 2 <FOOD>.'
      ),
    ];
    $this->players = '4+';
  }

  public function onPlayerIsDoable($player, &$args)
  {
    if ($args['isDoable']) {
      return;
    }

    if ($args['action'] == OCCUPATION) {
      $args['isDoable'] = true;
    }
  }

  public function isListeningTo($event)
  {
    return $event['type'] == 'action' &&
      $event['action'] == 'Occupation' &&
      $this->pId == $event['pId'] &&
      $event['method'] == 'beforeOccupation';
  }

  public function onPlayerBeforeOccupation($player, $event)
  {
    return $this->gainNode([FOOD => 2]);
  }
}
