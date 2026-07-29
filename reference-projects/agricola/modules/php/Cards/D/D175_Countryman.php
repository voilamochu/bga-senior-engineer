<?php
namespace AGR\Cards\D;

class D175_Countryman extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D175_Countryman';
    $this->name = ('Countryman');
    $this->deck = 'D';
    $this->number = 175;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      (
        'Each time any player (including you) takes a "Renovation" action on an action space, you can sow crops in exactly 1 field.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
