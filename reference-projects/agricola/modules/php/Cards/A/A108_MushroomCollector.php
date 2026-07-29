<?php
namespace AGR\Cards\A;
use AGR\Core\Notifications;
use AGR\Managers\Meeples;
use AGR\Helpers\CardRulings;

class A108_MushroomCollector extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A108_MushroomCollector';
    $this->name = clienttranslate('Mushroom Collector');
    $this->deck = 'A';
    $this->number = 108;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Immediately after each time you use a wood accumulation space, you can exchange 1 <WOOD> for 2 <FOOD>. If you do, place the <WOOD> on the accumulation space.'
      ),
    ];
    $this->players = '1+';

    $this->rulings = array_merge(
      CardRulings::fromKeys([
      'CANT_PAY_SHIFTING_CULTIVATOR',
      ]),
      [
      clienttranslate('You may take 0 <WOOD> from a wood accumulation space and still use this effect.'),
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
      'countAsUse' => true,
      'childs' => [
        $this->moveResourcesToSpaceNode([WOOD => 1], $event['actionCardId']),
        $this->gainNode([FOOD => 2]),
      ]
    ];
  }
}
