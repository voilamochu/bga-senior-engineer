<?php

namespace AGR\Cards\Actions;

use AGR\Core\Globals;
use AGR\Models\ActionCard;

class ActionLessons extends ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionLessons';
    $this->name = clienttranslate('Lessons');
    $this->actionCardType = 'Lessons';
    $this->desc = [clienttranslate('[Pay] 1<FOOD>*'), '1<OCCUPATION>'];
    $this->tooltipDesc = [clienttranslate('[Pay 1]') . ' <FOOD>*', '1<OCCUPATION>'];
    $this->tooltip = [
      clienttranslate('Play exactly one occupation card from your hand'),
      clienttranslate('* The first occupation you play in the game is free'),
    ];

    $this->isNotBeginner = true;
  }

  public function getFlow($player)
  {
    $lessonsTurnId = Globals::getLessonsTurnId();
    $lessonsTurnId[$player->getId()] = Globals::getTurnId();

    return [
          'action' => OCCUPATION,
          'args' => [
            'cost' => $player->countOccupations() == 0 ? [] : [FOOD => 1],
             'setTurnId' => $lessonsTurnId,
          ]];
  }
}
