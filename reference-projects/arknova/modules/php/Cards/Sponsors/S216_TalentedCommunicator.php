<?php

namespace ARK\Cards\Sponsors;

class S216_TalentedCommunicator extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S216_TalentedCommunicator';
    $this->number = 216;
    $this->name = clienttranslate('Talented Communicator');
    $this->lvl = 5;
    $this->prerequisites = [UPGRADED_SPONSORS_CARD => 1];
    $this->effects = [
      IMMEDIATE => [
        clienttranslate(
          'Hire 1 additional association worker (move it from the lowest occupied storage space to the notepad above).'
        ),
        clienttranslate('This worker will be available to you immediately.'),
        \clienttranslate(
          'Certain zoo maps reward you with conservation points for hiring your last worker. If you have already hired all your association workers, the ability has no effect. (Full-throated)'
        ),
      ],
      ENDGAME => [clienttranslate('Gain 1 conservation point if your zoo has 9 or more reputation.')],
    ];
    $this->person = true;
  }

  public function getImmediate()
  {
    return [[BONUS_WORKER => 1]];
  }

  public function score()
  {
    $n = $this->getPlayer()->getReputation();
    $this->scoreConservation($n, [9 => 1]);
  }
}
