<?php
namespace AGR\Cards\A;

class A176_Wheelmaker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A176_Wheelmaker';
    $this->name = ('Wheelmaker');
    $this->deck = 'A';
    $this->number = 176;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      (
        'When you play this card, if you have another occupation in play and more wood than all other players combined, you immediately get wood from the general supply until you have 15 wood.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
