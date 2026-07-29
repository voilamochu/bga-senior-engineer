<?php
namespace AGR\Cards\B;

class B77_LoamPit extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B77_LoamPit';
    $this->name = clienttranslate('Loam Pit');
    $this->deck = 'B';
    $this->number = 77;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [clienttranslate('Each time you use the __Day Laborer__ action space, you also get 3 <CLAY>.')];
    $this->vp = 1;
    $this->cost = [
      FOOD => 1,
    ];
    $this->prerequisite = clienttranslate('3 Occupations');
    $this->occupationPrerequisites = ['min' => 3];
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'DayLaborer');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    return $this->gainNode([CLAY => 3]);
  }
}
