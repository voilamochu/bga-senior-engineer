<?php
namespace AGR\Cards\D;

class D49_Bookshelf extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D49_Bookshelf';
    $this->name = clienttranslate('Bookshelf');
    $this->deck = 'D';
    $this->number = 49;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Immediately before each time you play an occupation (even before paying the occupation cost), you get 3 <FOOD>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 1,
    ];
    $this->prerequisite = clienttranslate('3 Occupations');
    $this->occupationPrerequisites = ['min' => 3];
  }

  public function onPlayerIsDoable($player, &$args)
  {
    if ($args['isDoable']) {
      return;
    }

    if ($args['action'] == OCCUPATION) {
      // TODO : handle the cost of occupation
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
    return $this->gainNode([FOOD => 3]);
  }
}
