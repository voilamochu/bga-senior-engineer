<?php
namespace AGR\Cards\E;
use AGR\Core\Globals;

class E104_SpiceTrader extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E104_SpiceTrader';
    $this->name = clienttranslate('Spice Trader');
    $this->deck = 'E';
    $this->author = 'snapper';
    $this->number = 104;
    $this->category = 'GOODS_-_GET';
    $this->desc = [
      clienttranslate(
        'If you play this card in round 4 or before, place 3 <VEGETABLE> on the space for round 11. At the start of that round, you get the <VEGETABLE>.'
      ),
    ];
    $this->players = '1+';
  }

  public function onBuy($player)
  {
    if(Globals::getTurn()>=5){
      return;
    }
     return $this->futureMeeplesNode([VEGETABLE => 3], [11]);      
  }
}
