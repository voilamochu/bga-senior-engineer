<?php
namespace AGR\Cards\D;

class D31_Storeroom extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D31_Storeroom';
    $this->name = clienttranslate('Storeroom');
    $this->deck = 'D';
    $this->number = 31;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'During scoring, you get ½ bonus <SCORE> for each pair of <GRAIN> plus <VEGETABLE> you have (considering all crops in your supply and fields), rounded up.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 1,
      STONE => 2,
    ];
    $this->extraVp = true;
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function computeBonusScore()
  {
    $player = $this->getPlayer();
    
    $grain = $player->countReserveResource(GRAIN) + 
      $player->board()->getGrowingCrops(GRAIN)->count();    
    $vegetables = $player->countReserveResource(VEGETABLE) + 
      $player->board()->getGrowingCrops(VEGETABLE)->count();
          
    $pairs = min($grain, $vegetables);
    $bonus = ceil($pairs / 2);
    
    if ($bonus > 0) {
      $this->addBonusScoringEntry($bonus);
    }
  }
}
