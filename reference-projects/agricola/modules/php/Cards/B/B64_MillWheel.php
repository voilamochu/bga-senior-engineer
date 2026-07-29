<?php
namespace AGR\Cards\B;
use AGR\Managers\Farmers;

class B64_MillWheel extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B64_MillWheel';
    $this->name = clienttranslate('Mill Wheel');
    $this->deck = 'B';
    $this->number = 64;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Grain Utilization__ action space while the __Fishing__ accumulation space is occupied, you get an additional 2 <FOOD>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 2,
    ];
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'GrainUtilization');  
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
	if (!Farmers::getOnCard('ActionFishing')->empty()) {
      return $this->gainNode([FOOD => 2]);
    }
  }
  
}
