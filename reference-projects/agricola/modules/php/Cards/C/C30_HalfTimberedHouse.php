<?php
namespace AGR\Cards\C;

class C30_HalfTimberedHouse extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C30_HalfTimberedHouse';
    $this->name = clienttranslate('Half-Timbered House');
    $this->deck = 'C';
    $this->number = 30;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'During scoring, you get 1 bonus <SCORE> for each stone room you have. You can only use one card to get bonus points for your stone house.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
      CLAY => 1,
      STONE => 2,
      REED => 1,
    ];
    $this->extraVp = true;
    $this->bonusStoneRoom = true;
  }

  public function computeBonus()
  {
    $player = $this->getPlayer();
    if ($player->getRoomType() == 'roomStone') {
      $nbRooms = $player->countRooms();
      return $nbRooms;
    }
    return 0;
  }

  public function computeBonusScore()
  {
    if ($this->getMaxBonus('bonusStoneRoom') == $this->id) {
      $bonus = $this->computeBonus();
      if ($bonus > 0) {
        $this->addBonusScoringEntry($bonus);
      }
      return;
    }
  }
}
