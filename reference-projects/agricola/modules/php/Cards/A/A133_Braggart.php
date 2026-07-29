<?php
namespace AGR\Cards\A;

class A133_Braggart extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A133_Braggart';
    $this->name = clienttranslate('Braggart');
    $this->deck = 'A';
    $this->number = 133;
    $this->category = POINTS_PROVIDER;
    $this->extraVp = true;
    $this->desc = [
      clienttranslate(
        'During the scoring, you get 2/3/4/5/7/9 bonus <SCORE> for having at least 5/6/7/8/9/10 improvements in front of you.'
      ),
    ];
    $this->players = '3+';
    $this->extraVp = true;
    $this->bannedStrong3or4p = true;
  }

  public function computeBonusScore()
  {
    $player = $this->getPlayer();
    $bonusMap = [0, 0, 0, 0, 0, 2, 3, 4, 5, 7, 9];
    $bonus = $bonusMap[$player->countAllImprovements()] ?? 9;
    $this->addBonusScoringEntry($bonus);
  }
}
