<?php
namespace AGR\Cards\D;

class D76_SocialBenefits extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D76_SocialBenefits';
    $this->name = clienttranslate('Social Benefits');
    $this->deck = 'D';
    $this->number = 76;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Immediately after the feeding phase of each harvest, if you have no <FOOD> left, you get 1 <WOOD> and 1 <CLAY>.'
      ),
    ];
    $this->cost = [
      REED => 1,
    ];
    $this->prerequisite = clienttranslate('At Most 1 Occupation');
    $this->occupationPrerequisites = ['max' => 1];
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'EndHarvestFeedingPhase';
  }

  public function onPlayerEndHarvestFeedingPhase($player)
  {
    $food = $player->countReserveResource(FOOD);

    if ($food == 0) {
      return $this->gainNode([WOOD => 1, CLAY => 1]);
    }
  }
}
