<?php
namespace AGR\Cards\E;

use AGR\Helpers\UsedText;

class E102_Acquirer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E102_Acquirer';
    $this->name = clienttranslate('Acquirer');
    $this->deck = 'E';
    $this->author = 'grummelbaum';
    $this->number = 102;
    $this->category = 'GOODS_-_GET';
    $this->desc = [
      clienttranslate(
        'At the start of each round, you can pay <FOOD> equal to the number of people you have to buy 1 good of your choice from the general supply.'
      ),
    ];
    $this->players = '1+';
    $this->usedText = UsedText::get('GOODS_BOUGHT');
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) &&
      $event['type'] == 'StartOfTurn';
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    $n = $player->countFarmers();

    return [
      'type' => NODE_SEQ,
      'customDescription' => 'Pay ' . $n . '<FOOD>, Buy a good',
      'optional' => true,
      'countAsUse' => true,
      'childs' => [
        $this->payNode([FOOD => $n]),
        [
          'type' => NODE_XOR,
          'childs' => [
            $this->gainNode([VEGETABLE => 1]),
            $this->gainNode([GRAIN => 1]),
            $this->gainNode([CATTLE => 1]),
            $this->gainNode([SHEEP => 1]),
            $this->gainNode([PIG => 1]),
            $this->gainNode([WOOD => 1]),
            $this->gainNode([CLAY => 1]), 
            $this->gainNode([REED => 1]), 
            $this->gainNode([STONE => 1]),
          ]
        ]
      ]
    ];
  }
}
