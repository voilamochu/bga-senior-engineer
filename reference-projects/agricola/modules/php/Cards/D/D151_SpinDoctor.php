<?php

namespace AGR\Cards\D;

use AGR\Helpers\CardRulings;
use AGR\Managers\ActionCards;
use AGR\Models\Occupation;

class D151_SpinDoctor extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D151_SpinDoctor';
    $this->name = clienttranslate('Spin Doctor');
    $this->deck = 'D';
    $this->number = 151;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Immediately after each time you use the __Traveling Players__ accumulation space, you can place another person on an action space of your choice, regardless whether or not the action space is occupied.'
      ),
    ];
    $this->players = '4+';
    $this->isCorbariusOrDulcinaria = true;

    $this->rulings = CardRulings::fromKeys([
      'MEETING_PLACE_NEVER_REUSED',
    ]);
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer') && $event['actionCardType'] == 'TravelingPlayers';
  }

  public function onPlayerAfterPlaceFarmer($player, $event)
  {
    $added = [];
    $cards = ActionCards::getVisible($player);
    foreach ($cards as $card) {
      if ($card->getActionCardType() == 'MeetingPlace') {
        continue;
      }
      $added[] = $card->getId();
    }

    return [
      'countAsUse' => true,
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        $this->setTurnIdNode(),
        [
          'action' => PLACE_FARMER,
          'args' => ['added' => $added],
          'source' => $this->name,
        ],
      ],
    ];
  }
}
