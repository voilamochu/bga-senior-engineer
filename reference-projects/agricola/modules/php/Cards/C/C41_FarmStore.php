<?php
namespace AGR\Cards\C;

use AGR\Helpers\Utils;

class C41_FarmStore extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C41_FarmStore';
    $this->name = clienttranslate('Farm Store');
    $this->deck = 'C';
    $this->number = 41;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'After the feeding phase of each harvest, you can exchange exactly 1 <FOOD> for 2 different building resources of your choice or 1 <VEGETABLE>.'
      ),
    ];
    $this->cost = [
      WOOD => 2,
      CLAY => 2,
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'EndHarvestFeedingPhase';
  }

  public function onPlayerEndHarvestFeedingPhase($player)
  {
    return [
      'type' => NODE_XOR,
      'optional' => true,
      'countAsUse' => true,
      'doableRequiresChild' => true,
      'customDescription' => 'Buy goods',
      'childs' => [
        $this->payGainNode([FOOD => 1], [VEGETABLE => 1], null, false),
        $this->payGainNode([FOOD => 1], [WOOD => 1, CLAY => 1], null, false),
        $this->payGainNode([FOOD => 1], [WOOD => 1, STONE => 1], null, false),
        $this->payGainNode([FOOD => 1], [WOOD => 1, REED => 1], null, false),
        $this->payGainNode([FOOD => 1], [CLAY => 1, STONE => 1], null, false),          
        $this->payGainNode([FOOD => 1], [CLAY => 1, REED => 1], null, false),          
        $this->payGainNode([FOOD => 1], [STONE => 1, REED => 1], null, false),                    
      ],
    ];
  }
}
