<?php
namespace AGR\Cards\A;

use AGR\Helpers\Utils;

class A15_CarpentersAxe extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A15_CarpentersAxe';
    $this->name = clienttranslate("Carpenter's Axe");
    $this->deck = 'A';
    $this->number = 15;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Each time after you use a wood accumulation space, if you then have at least 7 <WOOD> in your supply, you can build exactly 1 stable for 1 <WOOD>.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isCollectEvent($event, WOOD);
  }

  public function onPlayerAfterCollect($player, $event)
  {
	if ($player->countReserveResource(WOOD) > 6) {
		return [
		  'action' => STABLES,
		  'cardId' => $this->id,
		  'optional' => true,
		  'args' => [
			'max' => 1,
			'costs' => Utils::formatCost(['max' => 1, WOOD => 1]),
		  ],
		];
	}
  }  
}
