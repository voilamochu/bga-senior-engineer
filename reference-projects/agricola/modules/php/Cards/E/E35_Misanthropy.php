<?php
namespace AGR\Cards\E;

class E35_Misanthropy extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E35_Misanthropy';
    $this->name = clienttranslate('Misanthropy');
    $this->deck = 'E';
    $this->author = 'pm_ballard';
    $this->number = 35;
    $this->category = 'BONUS_POINTS_-_GET';
    $this->desc = [clienttranslate('During scoring, if you have exactly 4/3/2 people, you get 2/3/5 bonus <SCORE>.')];
    $this->extraVp = true;
    $this->cost = [
      WOOD => 1,
    ];
  }

  public function computeBonusScore()
  {
    $player = $this->getPlayer();
    $farmers = $player->countFarmers();
    if ($farmers==4){
      $this->addBonusScoringEntry(2);
    }
    elseif ($farmers==3){
      $this->addBonusScoringEntry(3);
    }
    elseif ($farmers==2){
      $this->addBonusScoringEntry(5);
    }
  }
}
