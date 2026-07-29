<?php
namespace AGR\Cards\A;

class A134_FullFarmer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A134_FullFarmer';
    $this->name = clienttranslate('Full Farmer');
    $this->deck = 'A';
    $this->number = 134;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <WOOD> and 1 <CLAY>. During scoring, you get 1 bonus <SCORE> for each pasture you have holding the maximum number of animals.'
      ),
    ];
    $this->players = '3+';
    $this->extraVp = true;
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onBuy($player) 
  {
    return $this->gainNode([WOOD => 1, CLAY => 1]);
  }
  
  public function computeBonusScore()
  {
    $player = $this->getPlayer();
    $bonus = 0;

    $zones = $player->board()->getAnimalsDropZonesWithAnimals();
    foreach ($zones as $zone) {
      if ($zone['type'] != 'pasture') {
        continue;
      }
      if ($zone['capacity'] - $zone['animals'] == 0) {
        $bonus++;
      }
    }

    if ($bonus != 0) {
      $this->addBonusScoringEntry($bonus);
    }
  }

  public function enforceReorganizeOnLastHarvest()
  {
    $animals = $this->getPlayer()->countAnimalsOnBoard();
    return max($animals) > 0;
  }  
}
