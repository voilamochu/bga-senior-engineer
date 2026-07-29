<?php

namespace AGR\Cards\Actions;

use AGR\Core\Globals;
use AGR\Models\ActionCard;

class ActionLessons4 extends ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionLessons4';
    $this->name = clienttranslate('Lessons');
    $this->actionCardType = 'Lessons';
    $this->desc = [clienttranslate('[Pay] 2<FOOD>*'), '1<OCCUPATION>'];
    $this->tooltipDesc = [clienttranslate('[Pay 2]') . ' <FOOD>*', '1<OCCUPATION>'];
    $this->tooltip = [
      clienttranslate('Play exactly one occupation card from your hand'),
      clienttranslate('* The first two occupations you play in the game cost 1 food'),
    ];
    $this->container = 'left';

    $this->isNotBeginner = true;
    $this->players = [4];
  }

  public function getFlow($player)
  {
    $lessonsTurnId = Globals::getLessonsTurnId();
    $lessonsTurnId[$player->getId()] = Globals::getTurnId();

    return [
      'action' => OCCUPATION,
      'args' => [
        'cost' => [FOOD => $player->countOccupations() <= 1 ? 1 : 2],
         'setTurnId' => $lessonsTurnId,
      ],
    ];
  }
}
