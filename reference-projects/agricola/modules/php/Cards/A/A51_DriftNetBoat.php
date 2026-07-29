<?php
namespace AGR\Cards\A;

class A51_DriftNetBoat extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A51_DriftNetBoat';
    $this->name = clienttranslate('Drift-Net Boat');
    $this->deck = 'A';
    $this->number = 51;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate('Each time you use the __Fishing__ accumulation space, you get an additional 2 <FOOD>.'),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 1,
      REED => 1,
    ];
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'Fishing');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    return $this->gainNode([FOOD => 2]);
  }  
}
