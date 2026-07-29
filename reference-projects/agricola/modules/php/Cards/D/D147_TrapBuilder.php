<?php
namespace AGR\Cards\D;
use AGR\Managers\Players;

class D147_TrapBuilder extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D147_TrapBuilder';
    $this->name = clienttranslate('Trap Builder');
    $this->deck = 'D';
    $this->number = 147;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Day Laborer__ action space, place 1 <FOOD>, 1 <FOOD>, and 1 <PIG> on the next 3 round spaces, respectively. At the start of these rounds, you get the good.'
      ),
    ];
    $this->players = '3+';
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'DayLaborer');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    return [
      'type' => NODE_SEQ,
      'countAsUse' => true,
      'childs' => [
        $this->futureMeeplesNode([FOOD => 1], 2),
        $this->futureMeeplesNode([PIG => 1], ['+3']),
      ]
    ];
  }  
}
