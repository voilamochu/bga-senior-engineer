<?php
namespace AGR\Cards\A;

class A85_Homekeeper extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A85_Homekeeper';
    $this->name = clienttranslate('Homekeeper');
    $this->deck = 'A';
    $this->number = 85;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Exactly one clay or stone room in your house can hold an additional person if the room is adjacent to both a field and a pasture.'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;

    $this->rulings = [
      clienttranslate('Only field tiles on your farm, not field cards, can count as adjacent.'),
      clienttranslate('If the room later loses adjacency to the field and/or pasture, the capacity for an additional person is lost, for example after playing __Overhaul__ (C001).'),
    ];
  }
}