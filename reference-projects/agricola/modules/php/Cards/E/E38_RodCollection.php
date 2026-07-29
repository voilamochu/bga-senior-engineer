<?php
namespace AGR\Cards\E;
use AGR\Managers\Meeples;

class E38_RodCollection extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E38_RodCollection';
    $this->name = clienttranslate('Rod Collection');
    $this->deck = 'E';
    $this->author = 'superg';
    $this->number = 38;
    $this->category = 'BONUS_POINTS_-_GET';
    $this->desc = [
      clienttranslate(
        'Each time you use __Fishing__, you can place up to 2 <WOOD> on this card, irretrievably. During scoring, each such <WOOD> is worth 1 bonus <SCORE>, except the 1st, 4th, 7th, and 10th.'
      ),
    ];
    $this->vp = 1;
    $this->prerequisite = clienttranslate('3 Occupations');
    $this->occupationPrerequisites = ['min' => 3];
    $this->holder = true;
    $this->extraVp = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'Fishing');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    return [
      'type' => NODE_XOR,
      'optional' => true,
      'countAsUse' => true,
      'childs' => [
        $this->moveResourcesToSpaceNode([WOOD => 1], $this->id, true),
        $this->moveResourcesToSpaceNode([WOOD => 2], $this->id, true)
      ]
    ];
  }

  public function computeBonusScore()
  {
    $wood = Meeples::getResourcesOnCard($this->id, null, WOOD)->count();
    $bonus = $wood;
    
    if ($wood == 0) {return;}
    if ($wood >= 1) {$bonus--;}
    if ($wood >= 4) {$bonus--;}
    if ($wood >= 7) {$bonus--;}
    if ($wood >= 10) {$bonus--;}

    $this->addBonusScoringEntry($bonus);    
  }
}
