<?php
namespace AGR\Cards\A;
use AGR\Helpers\Utils;

class A98_StableArchitect extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A98_StableArchitect';
    $this->name = clienttranslate('Stable Architect');
    $this->deck = 'A';
    $this->number = 98;
    $this->category = POINTS_PROVIDER;
    $this->extraVp = true;
    $this->desc = [clienttranslate('During scoring, you get 1 bonus <SCORE> for each unfenced stable in your farmyard.')];
    $this->players = '1+';
  }

  public function computeBonusScore()
  {
    $player = $this->getPlayer();
    $count = $player->board()->countUnfencedStablesForCards();
    if ($count > 0) {
      $this->addBonusScoringEntry($count);
    }
  }
}
