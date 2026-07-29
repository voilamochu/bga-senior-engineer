<?php
namespace AGR\Cards\A;

use AGR\Helpers\CardRulings;

class A91_ShiftingCultivator extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A91_ShiftingCultivator';
    $this->name = clienttranslate('Shifting Cultivator');
    $this->deck = 'A';
    $this->number = 91;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate('Each time you use a wood accumulation space, you can also pay 3 <FOOD> to plow 1 field.'),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;

    $this->rulings = array_merge(
      CardRulings::fromKeys([
      'TRIGGERS_BEFORE_ACTION_SPACE',
      ]),
      [
      clienttranslate('<FOOD> obtained via the __Basket__ (A056), or any other effect “after” using the space may not be used to pay for this effect.'),
      ]
    );
  }
  
  public function isListeningTo($event)
  {
    return $this->isBeforeCollectEvent($event, WOOD);
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
	    $this->payNode([FOOD => 3], clienttranslate('Shifting Cultivator\'s effect')),
        [
          'action' => PLOW,
          'cardId' => $this->id,
          'source' => $this->name,
        ],
      ],
    ];
  }  
}
