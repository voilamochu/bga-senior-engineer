<?php
namespace AGR\Cards\C;

use AGR\Helpers\Utils;
use AGR\Helpers\CardRulings;

class C126_Excavator extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C126_Excavator';
    $this->name = clienttranslate('Excavator');
    $this->deck = 'C';
    $this->number = 126;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time after you use the __Day Laborer__ action space, you get 1 additional <WOOD> and <CLAY>, and you can buy 1 <STONE> for 1 <FOOD>.'
      ),
    ];
    $this->players = '1+';

    $this->rulings = CardRulings::fromKeys([
      'HAPPENS_AFTER_COTTAGER',
    ]);
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer') && $event['actionCardType'] == 'DayLaborer';
  }

  public function onPlayerAfterPlaceFarmer($player, $args)
  {
    return [
      'type' => NODE_SEQ,
      'countAsUse' => true,
      'childs' => [
        $this->gainNode([CLAY => 1, WOOD => 1]),
        $this->payGainNode([FOOD => 1], [STONE => 1]),
      ],
    ];
  }
}
