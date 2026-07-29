<?php
namespace AGR\Cards\B;

class B176_VillageIdiot extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B176_VillageIdiot';
    $this->name = ('Village Idiot');
    $this->deck = 'B';
    $this->number = 176;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      (
        'The Village Idiot is your lone occupation. Each time another player uses the "Meeting Place" action space, you get 1 wood and 1 food.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
