<?php
namespace AGR\Cards\E;

class E37_OxSkull extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E37_OxSkull';
    $this->name = clienttranslate('Ox Skull');
    $this->deck = 'E';
    $this->author = 'tamos';
    $this->number = 37;
    $this->category = 'BONUS_POINTS_-_GET';
    $this->desc = [clienttranslate('During scoring, if you have no <CATTLE>, you get 3 bonus <SCORE>.')];
    $this->prerequisite = clienttranslate('1 cattle');
    $this->extraVp = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    return $player->countAnimalsOnBoard()[CATTLE] >= 1 && parent::isBuyable($player, $ignoreResources, $args);
  }

  public function computeBonusScore()
  {
    $player = $this->getPlayer();
    $cattles = $player->countAnimalsOnBoard()[CATTLE];
    if ($cattles==0 ) {
      $this->addBonusScoringEntry(3);
    }
  }
}
