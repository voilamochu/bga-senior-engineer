<?php
namespace AGR\Cards\D;

class D29_MuckRake extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D29_MuckRake';
    $this->name = clienttranslate('Muck Rake');
    $this->deck = 'D';
    $this->number = 29;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'During scoring, you get 1 bonus <SCORE> for exactly 1 unfenced stable holding exactly 1 <SHEEP>. The same applies to <PIG> and <CATTLE>, if held in different unfenced stables.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->extraVp = true;
  }

  public function computeBonusScore()
  {
    $player = $this->getPlayer();
    $bonuses = [
      SHEEP => 0,
      PIG => 0,
      CATTLE => 0,
    ];

    $zones = $player->board()->getAnimalsDropZonesWithAnimals();
    foreach ($zones as $zone) {
      if ($zone['type'] != 'stable') {
        continue;
      }
      if ($zone['animals'] == 1) {
        foreach (ANIMALS as $type) {
          if ($zone[$type] == 1 && $bonuses[$type] == 0) {
            $bonuses[$type] = 1;
          }
        }
      }
    }

    $bonus = $bonuses[SHEEP] + $bonuses[PIG] + $bonuses[CATTLE];
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
