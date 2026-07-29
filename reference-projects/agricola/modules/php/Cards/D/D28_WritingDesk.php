<?php
namespace AGR\Cards\D;


class D28_WritingDesk extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D28_WritingDesk';
    $this->name = clienttranslate('Writing Desk');
    $this->deck = 'D';
    $this->number = 28;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each time you use a __Lessons__ action space, you can play 1 additional occupation for an occupation cost of 2 <FOOD>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 1,
    ];
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'Lessons');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    // Need at least 2 occupations in hand: one for the main Lessons action, one for Writing Desk
    if (count($player->getHand(OCCUPATION)) < 2) {
      return null;
    }
    return [
      'action' => OCCUPATION,
      'cardId' => $this->id,
      'optional' => true,
      'args' => [
        'cost' => [FOOD => 2],
        'source' => $this->name,
      ],
    ];
  }
}
