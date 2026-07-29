<?php
namespace AGR\Cards\A;

use AGR\Helpers\CardRulings;

class A90_PlowDriver extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A90_PlowDriver';
    $this->name = clienttranslate('Plow Driver');
    $this->deck = 'A';
    $this->number = 90;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Once you live in a stone house, at the start of each round, you can pay 1 <FOOD> to plow 1 field.'
      ),
    ];
    $this->players = '1+';

    $this->rulings = CardRulings::fromKeys([
      'SCHOLAR_IMMEDIATE_USE',
    ]);
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) &&
      $event['type'] == 'StartOfTurn' &&
      $this->getPlayer()->getRoomType() == 'roomStone';
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        $this->payNode([FOOD => 1]),
        [
          'action' => PLOW,
          'cardId' => $this->id,
        ],
      ],
    ];
  }
}
