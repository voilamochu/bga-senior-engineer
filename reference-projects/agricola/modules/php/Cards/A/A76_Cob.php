<?php
namespace AGR\Cards\A;

// TODO: determine whether "at any time" cards can insert themselves before the clay check
// if so, change implementation

class A76_Cob extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A76_Cob';
    $this->name = clienttranslate('Cob');
    $this->deck = 'A';
    $this->number = 76;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'At the start of each work phase, if you have at least 1 <CLAY> in your supply, you can exchange exactly 1 <GRAIN> for 2 <CLAY> and 1 <FOOD>.'
      ),
    ];
    $this->cost = [
      FOOD => 1,
    ];
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) &&
      $event['type'] == 'startOfWork' &&
	  $this->getPlayer()->countReserveResource(CLAY) >= 1;
  }

  public function onPlayerStartOfWork($player, $event)
  {
	return $this->payGainNode([GRAIN => 1], [CLAY => 2, FOOD => 1]);
  }
}
