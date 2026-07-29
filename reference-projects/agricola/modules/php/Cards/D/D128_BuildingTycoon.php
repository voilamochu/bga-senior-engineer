<?php
namespace AGR\Cards\D;

use AGR\Helpers\Utils;
use AGR\Managers\Actions;

class D128_BuildingTycoon extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D128_BuildingTycoon';
    $this->name = clienttranslate('Building Tycoon');
    $this->deck = 'D';
    $this->number = 128;
    $this->isCorbariusOrDulcinaria = true;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Each time after another player builds 1 or more rooms, you can give them 1 <FOOD> to build exactly 1 room yourself. (You must pay the building cost of the room.)'
      ),
    ];
    $this->players = '3+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Construct', 'opponent') &&
      Actions::get(CONSTRUCT)->isDoable($this->getPlayer(), true);
  }

  public function onOpponentAfterConstruct($player, $event)
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
                'costs' => Utils::formatCost([FOOD => 1]),
                'source' => $this->name,
                'to' => $player->getId(),
              ],
            ],
            [
              'action' => CONSTRUCT,
              'cardId' => $this->id,
              'pId' => $this->pId,
              'args' => [
                'max' => 1,
                'trueAction' => false
              ],
            ],
          ],
        ],
      ],
    ];
  }
}
