<?php
namespace AGR\Cards\B;
use AGR\Managers\Players;

class B102_Consultant extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B102_Consultant';
    $this->name = clienttranslate('Consultant');
    $this->deck = 'B';
    $this->number = 102;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card in a 1-/2-/3-/4- player game, you immediately get 2 <GRAIN>/3 <CLAY>/2 <REED>/2 <SHEEP>.'
      ),
    ];
    $this->players = '1+';
  }

  public function onBuy($player)
  {
    $reward = [1 => [GRAIN => 2], 2 => [CLAY => 3], 3 => [REED => 2], 4 => [SHEEP => 2]];
    $playerCount = Players::count();
    return $this->gainNode($reward[$playerCount]);
  }
}
