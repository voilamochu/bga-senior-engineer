<?php
namespace AGR\Cards\C;

class C134_CowPrince extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C134_CowPrince';
    $this->name = clienttranslate('Cow Prince');
    $this->deck = 'C';
    $this->number = 134;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'During scoring, you get 1 bonus <SCORE> for each space in your farmyard (including rooms) holding at least 1 <CATTLE>.'
      ),
    ];
    $this->players = '3+';
    $this->extraVp = true;
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function computeBonusScore()
  {
    $player = $this->getPlayer();
    $bonus = 0;

    $zones = $player->board()->getAnimalsDropZonesWithAnimals();
    foreach ($zones as $zone) {
      if ($zone[CATTLE] >= 1) {
        $bonus+= min($zone[CATTLE], count($zone['locations']));
      }
    }
        
    if ($bonus != 0) {
      $this->addBonusScoringEntry($bonus);
    }
  }

  public function enforceReorganizeOnLastHarvest()
  {
    $cows = $this->getPlayer()->countAnimalsOnBoard()[CATTLE];
    return $cows > 1;
  }
}
