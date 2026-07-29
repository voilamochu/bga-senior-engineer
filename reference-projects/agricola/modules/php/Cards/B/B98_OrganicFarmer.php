<?php
namespace AGR\Cards\B;

class B98_OrganicFarmer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B98_OrganicFarmer';
    $this->name = clienttranslate('Organic Farmer');
    $this->deck = 'B';
    $this->number = 98;
    $this->category = POINTS_PROVIDER;
    $this->extraVp = true;
    $this->desc = [
      clienttranslate(
        'During the scoring, you get 1 bonus <SCORE> for each pasture containing at least 1 animal while having unused capacity for at least three more animals.'
      ),
    ];
    $this->players = '1+';
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
      if ($zone['animals'] > 0 && $zone['capacity'] - $zone['animals'] >= 3) {
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
