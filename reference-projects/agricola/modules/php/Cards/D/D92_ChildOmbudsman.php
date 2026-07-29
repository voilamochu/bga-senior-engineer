<?php

namespace AGR\Cards\D;

use AGR\Models\Occupation;
use AGR\Core\Globals;

class D92_ChildOmbudsman extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D92_ChildOmbudsman';
    $this->name = clienttranslate('Child Ombudsman');
    $this->deck = 'D';
    $this->number = 92;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
      'From round 5 on, if you have room in your house, at the end of each person action, you can take a __Family Growth__ action with that person. If you do, you get 2 negative <SCORE>.'
      ),
    ];
    $this->players = '1+';
    $this->extraVp = true;
    $this->holder = true;
    $this->isCorbariusOrDulcinaria = true;
    $this->bannedStrong3or4p = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer');
  }

  public function onPlayerAfterPlaceFarmer($player, $args)
  {
  if (Globals::getTurn() < 5) {
    return;
  }
  return [
    'countAsUse' => true,
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'getNegativeScore',
            'args' => [$player],
          ]
        ],[
        'action' => WISHCHILDREN,
        'optional' => false,
        'args' => ['constraints' => ['freeRoom'], 'cardLocation' => $this->id],
        'source' => $this->name,],],            
      ];
  }
  public function getNegativeScore($player)
  {
    $this->setExtraDatas('negativeScore',$this->getExtraDatas('negativeScore')+2);
  }
  public function computeBonusScore()
  {
    $malus = $this->getExtraDatas('negativeScore');
    if ($malus > 0) {
      $this->addBonusScoringEntry(-$malus);
    }
  }
}
