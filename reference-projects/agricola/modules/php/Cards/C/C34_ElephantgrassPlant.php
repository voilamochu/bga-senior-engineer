<?php
namespace AGR\Cards\C;

// NOTE: treating "immediately after" and "after" harvest as equivalent for now
// I don't think there are any relevant interactions

class C34_ElephantgrassPlant extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C34_ElephantgrassPlant';
    $this->name = clienttranslate('Elephantgrass Plant');
    $this->deck = 'C';
    $this->number = 34;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Immediately after each harvest, you can use this card to exchange exactly 1 <REED> for 1 bonus <SCORE>.'
      ),
    ];
    $this->cost = [
      CLAY => 2,
      STONE => 1,
    ];
    $this->extraVp = true;
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];
    $this->isCorbariusOrDulcinaria = true;
    $this->bannedWeak1or2p = true;
    $this->bannedWeak3or4p = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && 
      $event['type'] == 'AfterHarvest';
  }

  public function onPlayerAfterHarvest($player)
  {
    return $this->payGainNode([REED => 1], [SCORE => 1]);
  }
}
