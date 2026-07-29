<?php
namespace AGR\Cards\C;

class C175_VillageTeacher extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C175_VillageTeacher';
    $this->name = ('Village Teacher');
    $this->deck = 'C';
    $this->number = 175;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      (
        'Immediately after each time you use a "Lessons" action space, if this is the 1st/2nd/3rd occupied Lessons action space that round, you get 1 food/grain/vegetable.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
