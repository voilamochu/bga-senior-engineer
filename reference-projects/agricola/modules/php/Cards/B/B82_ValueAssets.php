<?php
namespace AGR\Cards\B;

class B82_ValueAssets extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B82_ValueAssets';
    $this->name = clienttranslate('Value Assets');
    $this->deck = 'B';
    $this->number = 82;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'After each harvest, you can buy exactly one of the following goods: 1 <FOOD> <ARROW> 1 <WOOD>; 1 <FOOD> <ARROW> 1 <CLAY>; 2 <FOOD> <ARROW> 1 <REED>; 2 <FOOD> <ARROW> 1 <STONE>'
      ),
    ];
    $this->isArtifexOrBubulcus = true;
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'AfterHarvest';
  }

  public function onPlayerAfterHarvest($player)
  {
    return [
      'type' => NODE_XOR,
      'optional' => true,
      'doableRequiresChild' => true,
      'customDescription' => 'Buy building resource',
      'childs' => [
        $this->payGainNode([FOOD => 1], [WOOD => 1], null, false),
        $this->payGainNode([FOOD => 1], [CLAY => 1], null, false),
        $this->payGainNode([FOOD => 2], [REED => 1], null, false),
        $this->payGainNode([FOOD => 2], [STONE => 1], null, false),
      ]
    ];
  }  
}
