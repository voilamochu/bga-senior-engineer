<?php
namespace AGR\Cards\E;

class E96_Elder extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E96_Elder';
    $this->name = clienttranslate('Elder');
    $this->deck = 'E';
    $this->author = 'mattthelesser';
    $this->number = 96;
    $this->category = 'ACTION_-_IMPROVEMENT';
    $this->desc = [
      clienttranslate(
        'You can play this card at the start of the work phase of round 1 without placing a person. (This card has no effect other than counting as a played occupation.)'
      ),
    ];
    $this->players = '1+';
  }

  // Functionality in States/TurnTrait -> stStartOfTurn()
}
