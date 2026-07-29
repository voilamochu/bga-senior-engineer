<?php
namespace AGR\Cards\C;

class C166_CattleWhisperer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C166_CattleWhisperer';
    $this->name = clienttranslate('Cattle Whisperer');
    $this->deck = 'C';
    $this->number = 166;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Add 5 and 8 to the current round and place 1 <CATTLE> on each corresponding round space. At the start of these rounds, you get the <CATTLE>.'
      ),
    ];
    $this->players = '4+';
  }

  public function onBuy($player)
  {
    return $this->futureMeeplesNode([CATTLE => 1], ['+5', '+8']);      
  }
}
