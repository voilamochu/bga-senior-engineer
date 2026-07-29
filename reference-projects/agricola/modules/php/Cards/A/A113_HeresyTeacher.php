<?php
namespace AGR\Cards\A;

class A113_HeresyTeacher extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A113_HeresyTeacher';
    $this->name = ('Heresy Teacher');
    $this->deck = 'A';
    $this->number = 113;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      (
        'Each time you use a "Lessons" action space, you get 1 vegetable in each of your fields with at least 3 grain and no vegetable. Place the vegetable below the grain.'
      ),
    ];
    $this->players = '1+';
    $this->implemented = false;
  }
}
