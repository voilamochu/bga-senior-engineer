<?php
namespace AGR\Cards\A;

class A32_Manger extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A32_Manger';
    $this->name = clienttranslate('Manger');
    $this->deck = 'A';
    $this->number = 32;
    $this->category = POINTS_PROVIDER;
    $this->extraVp = true;
    $this->desc = [
      clienttranslate(
        'During scoring, if your pastures cover at least 6/7/8/10 farmyard spaces, you get 1/2/3/4 bonus <SCORE>.'
      ),
    ];
    $this->cost = [
      WOOD => 2,
    ];
  }

  public function computeBonusScore()
  {
    $player = $this->getPlayer();
    $coveredZones = $player->board()->countCoveredZonesByPastures();
    $bonusMap = [0, 0, 0, 0, 0, 0, 1, 2, 3, 3, 4];
    $bonus = $bonusMap[$coveredZones] ?? 4;
    $this->addBonusScoringEntry($bonus);
  }
}
