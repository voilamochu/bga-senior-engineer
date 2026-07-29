<?php
namespace AGR\Cards\A;

class A38_WoolBlankets extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A38_WoolBlankets';
    $this->name = clienttranslate('Wool Blankets');
    $this->deck = 'A';
    $this->number = 38;
    $this->category = POINTS_PROVIDER;
    $this->extraVp = true;
    $this->desc = [
      clienttranslate('During scoring, if you live in a wooden/clay/stone house by then, you get 3/2/0 bonus <SCORE>.'),
    ];
    $this->prerequisite = clienttranslate('5 Sheep');
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    return $player->countAnimalsOnBoard()[SHEEP] >= 5 && parent::isBuyable($player, $ignoreResources, $args);
  }

  public function computeBonusScore()
  {
    $player = $this->getPlayer();
    $roomType = $player->getRoomType();
    $bonusMap = [
      'roomWood' => 3,
      'roomClay' => 2,
      'roomStone' => 0,
    ];
    $bonus = $bonusMap[$roomType];
    $this->addBonusScoringEntry($bonus);
  }
}
