<?php

namespace AGR\Cards\D;

use AGR\Core\Globals;
use AGR\Helpers\Utils;
use AGR\Models\MinorImprovement;

class D64_BakingCourse extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D64_BakingCourse';
    $this->name = clienttranslate('Baking Course');
    $this->deck = 'D';
    $this->number = 64;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate('[__Bake Bread__ action:]'),
      '<GRAIN> <ARROW> 2<FOOD>',
      clienttranslate(
        'At the end of each round that does not end with a harvest, you can take a __Bake Bread__ action.'
      ),
    ];
    $this->prerequisite = clienttranslate('1 Occupation');
    $this->occupationPrerequisites = ['min' => 1];
    $this->isCorbariusOrDulcinaria = true;
    $this->exchanges = [Utils::formatExchange([GRAIN => [FOOD => 2]], $this->name, [BREAD])];
    $this->isBakingImprovement = true;
  }

  public function isListeningTo($event)
  {
    if (!$this->isPlayerEvent($event)) return false;
    if (!($event['type'] == 'EndOfRound')) return false;
    $turn = Globals::getTurn();
    $harvestRounds = [4, 7, 9, 11, 13, 14];
    return !in_array($turn, $harvestRounds);
  }

  public function onPlayerEndOfRound($player, $event)
  {
    return $this->bakeBreadNode();
  }
}
