<?php
namespace AGR\Cards\A;

class A42_ForestLakeHut extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A42_ForestLakeHut';
    $this->name = clienttranslate('Forest Lake Hut');
    $this->deck = 'A';
    $this->number = 42;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate('Each time you use the __Fishing__/__Forest__ accumulation space, you also get 1 <WOOD>/<FOOD>.'),
    ];
    $this->vp = 1;
    $this->cost = [
      CLAY => 2,
    ];
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'Fishing') ||
      $this->isActionCardEvent($event, 'Forest');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    if ($args['actionCardType'] == 'Fishing') {
      $resource = WOOD;
	} elseif ($args['actionCardType'] == 'Forest') {
      $resource = FOOD;
	}
    return $this->gainNode([$resource => 1]);
  }
  
}
