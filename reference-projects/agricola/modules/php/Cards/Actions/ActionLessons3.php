<?php

namespace AGR\Cards\Actions;

use AGR\Core\Globals;
use AGR\Models\ActionCard;

class ActionLessons3 extends ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionLessons3';
    $this->name = clienttranslate('Lessons');
    $this->actionCardType = 'Lessons';
    $this->desc = [clienttranslate('[Pay] 2<FOOD>'), '1<OCCUPATION>'];
    $this->tooltipDesc = [clienttranslate('[Pay 2]') . ' <FOOD>', '1<OCCUPATION>'];
    $this->tooltip = [clienttranslate('Play exactly one occupation card from your hand')];
    $this->container = 'left';

    $this->isNotBeginner = true;
    $this->players = [3];
  }

  public function getFlow($player)
  {
    $lessonsTurnId = Globals::getLessonsTurnId();
    $lessonsTurnId[$player->getId()] = Globals::getTurnId();

    return [
      'action' => OCCUPATION,
      'args' => [
        'cost' => [FOOD => 2],
         'setTurnId' => $lessonsTurnId,
      ],
    ];
  }
}
