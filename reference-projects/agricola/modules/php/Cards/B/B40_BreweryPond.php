<?php
namespace AGR\Cards\B;

class B40_BreweryPond extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B40_BreweryPond';
    $this->name = clienttranslate('Brewery Pond');
    $this->deck = 'B';
    $this->number = 40;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Fishing__ or __Reed Bank__ accumulation space, you also get 1 <GRAIN> and 1 <WOOD>.'
      ),
    ];
    $this->vp = -1;
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];
    $this->isArtifexOrBubulcus = true;
    $this->bannedStrong1or2p = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'Fishing') ||
	  $this->isActionCardEvent($event, 'ReedBank');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    return $this->gainNode([GRAIN => 1, WOOD => 1]);
  }  
}
