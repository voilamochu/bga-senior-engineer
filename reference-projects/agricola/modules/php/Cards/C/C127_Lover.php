<?php
namespace AGR\Cards\C;
use AGR\Core\Globals;

class C127_Lover extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C127_Lover';
    $this->name = clienttranslate('Lover');
    $this->deck = 'C';
    $this->number = 127;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'When you play this card, immediately pay an amount of <FOOD> equal to the number of complete rounds left to play to take a __Family Growth Even without Room__ action.'
      ),
    ];
    $this->players = '3+';
    $this->holder = true;
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function onBuy($player)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        $this->payNode([FOOD => 14 - Globals::getTurn()]),
        [
          'action' => WISHCHILDREN,
          'args' => ['cardLocation' => $this->id],
          'source' => $this->name
        ],
      ]
    ];
  }  
}
