<?php
namespace AGR\Cards\D;

class D171_SeniorTeacher extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D171_SeniorTeacher';
    $this->name = ('Senior Teacher');
    $this->deck = 'D';
    $this->number = 171;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      (
        'Each time another player pays food on a "Lessons" action space, you get exactly 1 of that food.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
