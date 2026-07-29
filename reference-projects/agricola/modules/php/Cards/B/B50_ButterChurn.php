<?php
namespace AGR\Cards\B;

class B50_ButterChurn extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B50_ButterChurn';
    $this->name = clienttranslate('Butter Churn');
    $this->deck = 'B';
    $this->number = 50;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In the field phase of each harvest, you get 1 <FOOD> for every 3 <SHEEP> and 1 <FOOD> for every 2 <CATTLE> you have.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 1,
    ];
    $this->prerequisite = clienttranslate('At Most 3 Occupations');
    $this->occupationPrerequisites = ['max' => 3];
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'HarvestFieldPhase';
  }

  public function onPlayerHarvestFieldPhase($player)
  {
    $animals = $player->countAnimalsOnBoard();
    $gain = intdiv($animals[SHEEP], 3) + intdiv($animals[CATTLE], 2);

    return $gain > 0 ? $this->gainNode([FOOD => $gain]) : null;
  }
}
