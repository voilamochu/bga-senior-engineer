<?php
namespace AGR\Cards\E;
use AGR\Core\Globals;

class E139_BunnyBreeder extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E139_BunnyBreeder';
    $this->name = clienttranslate('Bunny Breeder');
    $this->deck = 'E';
    $this->author = 'barbarossa89';
    $this->number = 139;
    $this->category = FOOD;
    $this->desc = [
      clienttranslate(
        'Select a future round space, subtract the number of the current round from it, and place this many <FOOD> on that space. At the start of that round, you get the <FOOD>.'
      ),
    ];
    $this->players = '3+';
  }

  public function onBuy($player)
  {
    $turnLeft = 14 - Globals::getTurn();
    $childs = [];
      for ($i = 1; $i <= $turnLeft; $i++) {
        $childs[] = $this->futureMeeplesNode([FOOD => $i], [Globals::getTurn()+$i]);  
      }
      return  [
        'type' => NODE_XOR,
        'optional' => true,
        'childs' => $childs,
      ];
  }
}