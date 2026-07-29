<?php

namespace AGR\Cards\A;

use AGR\Models\Occupation;
use const NODE_SEQ;

class A128_RiparianBuilder extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A128_RiparianBuilder';
    $this->name = clienttranslate('Riparian Builder');
    $this->deck = 'A';
    $this->number = 128;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Each time another player uses the __Reed Bank__ accumulation space, you can build a room: if you build a clay/stone room, you get a discount of 1 <CLAY>/2 <STONE>.'
      ),
    ];
    $this->players = '3+';
    $this->isArtifexOrBubulcus = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer', 'opponent')
      && $event['actionCardType'] == 'ReedBank';
  }

  public function onOpponentAfterPlaceFarmer($player, $event)
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
              'action' => CONSTRUCT,
              'cardId' => $this->id,
              'pId' => $this->pId,
              'args' => [
                'max' => 1,
                'actionCardId' => 'A128_RiparianBuilder',
                'trueAction' => false
              ],
            ],
          ],
        ],
      ],
    ];
  }

  public function orderComputeCostsConstruct()
  {
    return [['>', 'B145_BrushwoodCollector']];
  }

  public function onPlayerComputeCostsConstruct($player, &$args)
  {
    if ($args['actionCardId'] != 'A128_RiparianBuilder') {
      return;
    }

    $res = $player->getRoomType() == 'roomClay' ? CLAY : STONE;
    $amount = $player->getRoomType() == 'roomClay' ? 1 : 2;

    foreach ($args['costs']['trades'] as $trade) {
      if (isset($trade[$res])) {
        $trade[$res] -= $amount;
        if ($trade[$res] <= 0) {
          unset($trade[$res]);
        }
        $trade['sources'][] = $this->id;
        $args['costs']['trades'][] = $trade;
      }
    }
  }
}
