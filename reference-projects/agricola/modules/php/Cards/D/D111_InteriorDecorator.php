<?php
namespace AGR\Cards\D;
use AGR\Managers\Players;

class D111_InteriorDecorator extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D111_InteriorDecorator';
    $this->name = clienttranslate('Interior Decorator');
    $this->deck = 'D';
    $this->number = 111;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you renovate, place 1 <FOOD> on each of the next 6 round spaces. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Renovation');
  }

  public function onPlayerAfterRenovation($player, $event)
  {
    return $this->futureMeeplesNode([FOOD => 1], 6);      
  }
}
