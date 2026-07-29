<?php

namespace AGR\Cards\A;
use AGR\Models\MinorImprovement;
use AGR\Helpers\CardRulings;

class A56_Basket extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A56_Basket';
    $this->name = clienttranslate('Basket');
    $this->deck = 'A';
    $this->number = 56;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Immediately after each time you use a wood accumulation space, you can exchange 2 <WOOD> for 3 <FOOD>. If you do, place those 2 <WOOD> on the accumulation space.'
      ),
    ];
    $this->cost = [
      REED => 1,
    ];

    $this->rulings = array_merge(
      CardRulings::fromKeys([
      'CANT_PAY_SHIFTING_CULTIVATOR',
      ]),
      [
      clienttranslate('You may take less than 2 <WOOD> from a wood accumulation space and still use this effect.'),
      ]
    );
  }

  public function isListeningTo($event)
  {
    return $this->isCollectEvent($event, WOOD, true);
  }

  public function onPlayerImmediatelyAfterCollect($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        $this->moveResourcesToSpaceNode([WOOD => 2], $event['actionCardId']),
        $this->gainNode([FOOD => 3]),
      ],
    ];
  }
}
