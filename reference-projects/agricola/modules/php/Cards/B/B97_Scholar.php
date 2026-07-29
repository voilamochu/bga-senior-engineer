<?php
namespace AGR\Cards\B;

class B97_Scholar extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B97_Scholar';
    $this->name = clienttranslate('Scholar');
    $this->deck = 'B';
    $this->number = 97;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Once you live in a stone house, at the start of each round, you can play an occupation for an occupation cost of 1 <FOOD>, or a minor improvement (by paying its cost).'
      ),
    ];
    $this->players = '1+';

    $this->rulings = [
      clienttranslate('If you use Scholar to play a card with text such as “remaining rounds” such as __Manservant__ (B107), it will not trigger in time for the current round.'),
    ];
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
      'countAsUse' => true,
      'type' => NODE_XOR,
      'optional' => true,
      'childs' => [
        [
          'action' => OCCUPATION,
          'args' => [
            'cost' => [FOOD => 1],
            'source' => $this->name,
          ],
        ],
        [
          'action' => IMPROVEMENT,
          'args' => [
            'types' => [MINOR],
            'trueAction' => false,
          ],
        ],
      ],
    ];
  }
}
