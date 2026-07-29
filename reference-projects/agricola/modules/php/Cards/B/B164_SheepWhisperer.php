<?php
namespace AGR\Cards\B;

class B164_SheepWhisperer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B164_SheepWhisperer';
    $this->name = clienttranslate('Sheep Whisperer');
    $this->deck = 'B';
    $this->number = 164;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Add 2, 5, 8, and 10 to the current round and place 1 <SHEEP> on each corresponding round space. At the start of these rounds, you get the <SHEEP>.'
      ),
    ];
    $this->players = '4+';
  }

  public function onBuy($player)
  {
    return $this->futureMeeplesNode([SHEEP => 1], ['+2', '+5', '+8', '+10']);      
  }
}
