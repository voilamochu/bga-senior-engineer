<?php
namespace AGR\Cards\A;

class A62_BeerKeg extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A62_BeerKeg';
    $this->name = clienttranslate('Beer Keg');
    $this->deck = 'A';
    $this->number = 62;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In the feeding phase of each harvest, you can use this card to exchange 1/2/3 <GRAIN> for 0/1/2 bonus <SCORE> and exactly 3 <FOOD>.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->extraVp = true;
    $this->prerequisite = clienttranslate('2 Grain in Your Supply');
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if ($player->countReserveResource(GRAIN) < 2) {
      return false;
    }
	
    return parent::isBuyable($player, $ignoreResources, $args);	
  }
  
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'HarvestFeedingPhase';
  }

  public function onPlayerHarvestFeedingPhase($player, $event)
  {
    return [
      'type' => NODE_XOR,
      'optional' => true,
      'countAsUse' => true,
      'doableRequiresChild' => true,
      'customDescription' => 'Pay <GRAIN>',
      'childs' => [
        $this->payGainNode([GRAIN => 1], [FOOD => 3], null, false),
        $this->payGainNode([GRAIN => 2], [FOOD => 3, SCORE => 1], null, false),
        $this->payGainNode([GRAIN => 3], [FOOD => 3, SCORE => 2], null, false),
      ]
    ];
  }
}
