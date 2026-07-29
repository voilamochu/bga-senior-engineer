<?php
namespace AGR\Cards\E;

class E34_LandRegister extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E34_LandRegister';
    $this->name = clienttranslate('Land Register');
    $this->deck = 'E';
    $this->author = 'luki';
    $this->number = 34;
    $this->category = 'BONUS_POINTS_-_GET';
    $this->desc = [clienttranslate('During scoring, if your farm has no unused spaces, you get 2 bonus <SCORE>.')];
    $this->extraVp = true;
    $this->cost = [
      WOOD => 1,
    ];
  }

  public function computeBonusScore()
  {
    $player = $this->getPlayer();
    if (empty($player->board()->getFreeZones())){
      $this->addBonusScoringEntry(2);
    }
  }
}
