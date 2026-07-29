<?php

namespace AGR\Cards\C;

use AGR\Helpers\Utils;
use AGR\Models\Occupation;
use const CONSTRUCT;
use const NODE_SEQ;

class C149_ResourceRecycler extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C149_ResourceRecycler';
    $this->name = clienttranslate('Resource Recycler');
    $this->deck = 'C';
    $this->number = 149;
    $this->isCorbariusOrDulcinaria = true;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Each time another player renovates to stone, if you live in a clay house, you can pay 2 <FOOD> to build a clay room at no additional cost.'
      ),
    ];
    $this->players = '4+';

    $this->rulings = [
      clienttranslate('If another player renovates from wood to stone, this card\'s effect is still triggered.'),
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Renovation', 'opponent') &&
      $event['newRoomType'] == 'roomStone' &&
      $this->getPlayer()->getRoomType() == 'roomClay' &&
      $this->getPlayer()
        ->board()
        ->canConstruct();
  }

  public function onOpponentAfterRenovation($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'pId' => $this->pId,
      'childs' => [
        [
          'type' => NODE_SEQ,
          'optional' => true,
          'forceConfirmation' => true,
          'pId' => $this->pId,
          'childs' => [
            [
              'action' => PAY,
              'pId' => $this->pId,
              'args' => [
                'nb' => 1,
                'costs' => Utils::formatCost([FOOD => 2]),
                'source' => $this->name,
              ],
            ],
            [
              'action' => CONSTRUCT,
              'cardId' => $this->id,
              'pId' => $this->pId,
              'args' => [
                'max' => 1,
                'costs' => Utils::formatCost([]),
                'trueAction' => false
              ],
            ],
          ],
        ],
      ],
    ];
  }
}
