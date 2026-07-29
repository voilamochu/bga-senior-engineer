<?php
namespace AGR\Cards\A;

class A67_CornScoop extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A67_CornScoop';
    $this->name = clienttranslate('Corn Scoop');
    $this->deck = 'A';
    $this->number = 67;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate('Each time you use the __Grain Seeds__ action space, you get 1 additional <GRAIN>.'),
    ];
    $this->cost = [
      WOOD => 1,
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'GrainSeeds');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    return $this->gainNode([GRAIN => 1]);
  }
}
