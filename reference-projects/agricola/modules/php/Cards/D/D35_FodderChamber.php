<?php
namespace AGR\Cards\D;
use AGR\Managers\Players;

class D35_FodderChamber extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D35_FodderChamber';
    $this->name = clienttranslate('Fodder Chamber');
    $this->deck = 'D';
    $this->number = 35;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'During scoring in a game with 1/2/3/4+ players, you get 1 bonus <SCORE> for every 7th/5th/4th/3rd animal on your farm.'
      ),
    ];
    $this->vp = 2;
    $this->cost = [
      STONE => 3,
      GRAIN => 3,
    ];
    $this->extraVp = true;
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function computeBonusScore()
  {
    $div = [7, 5, 4, 3, 3];
    $playerCount = Players::count();
    $player = $this->getPlayer();
    
    $n = $player->getExchangeResources()[SHEEP] +
      $player->getExchangeResources()[PIG] +
      $player->getExchangeResources()[CATTLE];
    
    $bonus = intdiv($n, $div[$playerCount-1]);
    if ($bonus != 0) {
      $this->addBonusScoringEntry($bonus);
    }
  }  
}
