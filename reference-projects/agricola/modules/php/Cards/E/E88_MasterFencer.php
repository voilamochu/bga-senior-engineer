<?php
namespace AGR\Cards\E;
use AGR\Helpers\Utils;

class E88_MasterFencer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E88_MasterFencer';
    $this->name = clienttranslate('Master Fencer');
    $this->deck = 'E';
    $this->author = 'barbarossa89';
    $this->number = 88;
    $this->category = 'FARMYARD_-_FENCING';
    $this->desc = [
      clienttranslate(
        'Once you live in a stone house, at the start of each round, you can pay 2 or 3 <WOOD> to build up to 3 or 4 fences, respectively.'
      ),
    ];
    $this->players = '1+';
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
          'type' => NODE_SEQ,
          'childs' => [
            $this->payNode([WOOD => 2]),
            [
              'action' => FENCING,
              'args' => ['costs' => Utils::formatCost([WOOD => 0]), 'max' => 3],              
            ]
          ]
        ],
        [
          'type' => NODE_SEQ,
          'childs' => [
            $this->payNode([WOOD => 3]),
            [
              'action' => FENCING,
              'args' => ['costs' => Utils::formatCost([WOOD => 0]), 'max' => 4],              
            ]
          ]
        ]
      ],
    ];    
  }
}
