<?php
namespace AGR\Cards\A;
use AGR\Managers\Meeples;

class A49_NestSite extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A49_NestSite';
    $this->name = clienttranslate('Nest Site');
    $this->deck = 'A';
    $this->number = 49;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time 1 <REED> is placed on a non-empty __Reed Bank__ accumulation space during the preparation phase, you get 1 <FOOD>.'
      ),
    ];
    $this->cost = [
      FOOD => 1,
    ];
    $this->prerequisite = clienttranslate('1 Occupation');
    $this->occupationPrerequisites = ['min' => 1];
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && 
      $event['type'] == 'Preparation';
  }

  public function onPlayerPreparation($player, $event)
  {
    if (Meeples::getResourcesOnCard('ActionReedBank')->count() > 0) {
      return $this->gainNode([FOOD => 1]);
    }
  }  
}
