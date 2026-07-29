<?php
namespace AGR\Cards\C;

use AGR\Managers\ActionCards;
use AGR\Helpers\CardRulings;

class C28_TeachersDesk extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C28_TeachersDesk';
    $this->name = clienttranslate("Teacher's Desk");
    $this->deck = 'C';
    $this->number = 28;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Major Improvement__ or __House Redevelopment__ action space, you can also play 1 occupation at an occupation cost of 1 <FOOD>.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->prerequisite = clienttranslate('1 Occupation');
    $this->occupationPrerequisites = ['min' => 1];
    $this->isCorbariusOrDulcinaria = true;
    $this->bannedStrong1or2p = true;
    $this->bannedStrong3or4p = true;

    $this->rulings = CardRulings::fromKeys([
      'TRIGGERS_BEFORE_ACTION_SPACE',
    ]);
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'MajorImprovement') ||
      $this->isActionCardEvent($event, 'HouseRedevelopment');
  }
  
  public function onPlayerPlaceFarmer($player, $event) 
  {
    return [
      'action' => OCCUPATION,
      'cardId' => $this->id,
      'optional' => true,
      'args' => ['cost' => [FOOD => 1]],
    ];
  }

  public function onPlayerComputeArgsPlaceFarmer($player)
  {
    $added = [];

    foreach (['ActionMajorImprovement', 'ActionHouseRedevelopment'] as $cId) {
      if (ActionCards::get($cId)->isVisible()) {
        $added[] = ['actionCardId' => $cId, 'ignoreResources' => true];
      }
    }

    return $added;
  }
}
