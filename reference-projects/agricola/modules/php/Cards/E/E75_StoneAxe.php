<?php
namespace AGR\Cards\E;

class E75_StoneAxe extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E75_StoneAxe';
    $this->name = clienttranslate('Stone Axe');
    $this->deck = 'E';
    $this->author = 'beso';
    $this->number = 75;
    $this->category = 'BUILDING_RESOURCES_-_WOOD';
    $this->desc = [
      clienttranslate(
        'Each time you use a wood accumulation space, you can return 1 <STONE> to the general supply to get an additional 3 <WOOD>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 1,
      CLAY => 1,
    ];
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->implemented = true;
    $this->occupationPrerequisites = ['min' => 2];
  }
  public function isListeningTo($event)
  {
    return $this->isCollectEvent($event, WOOD);
  }

  public function onPlayerAfterCollect($player, $event)
  {
	return $this->payGainNode([STONE => 1], [WOOD => 3]);
  }  
}
