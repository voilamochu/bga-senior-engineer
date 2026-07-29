<?php
namespace AGR\Cards\C;
use AGR\Core\Globals;
use AGR\Helpers\Utils;

class C85_DenBuilder extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C85_DenBuilder';
    $this->name = clienttranslate('Den Builder');
    $this->deck = 'C';
    $this->number = 85;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'When you live in a clay or stone house, you can pay 1 <GRAIN> and 2 <FOOD>. If you do, for the rest of the game, this card provides room for exactly one person.'
      ),
    ];
    $this->players = '1+';
    $this->holder = true;
    $this->isCorbariusOrDulcinaria = true;
  }
  public function isListeningTo($event)
  {
    $player = $this->getPlayer();
    return $this->isAnytime($event) &&
      ($player->getRoomType() == 'roomClay' ||
      $player->getRoomType() == 'roomStone') &&
      !$this->isFlagged();
  }
  public function onPlayerAtAnytime($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => false,
      'childs' => [
        $this->flagCardNode(),
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'addRoom',
            'args' => [],
          ],
        ],
        [
          'action' => PAY,
          'args' => [
                'nb' => 1,
                'costs' => Utils::formatCost([GRAIN => 1, FOOD => 2]),
                'source' => $this->name,
              ],
          'source' => $this->name,
        ],
      ],
    ];
  }
  public function addRoom()
  {
    Globals::setDenBuilderRoom(1);
  }
}