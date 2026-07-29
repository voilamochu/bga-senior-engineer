<?php
namespace AGR\Cards\D;

class D34_LuxuriousHostel extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D34_LuxuriousHostel';
    $this->name = clienttranslate('Luxurious Hostel');
    $this->deck = 'D';
    $this->number = 34;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'During scoring, if you then have more stone rooms than people, you get 4 bonus <SCORE>. You can only use one card to get bonus points for your stone house.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
      CLAY => 2,
    ];
    $this->extraVp = true;
    $this->bonusStoneRoom = true;
    $this->isCorbariusOrDulcinaria = true;
  }

  public function computeBonus()
  {
    $player = $this->getPlayer();
    if ($player->getRoomType() == 'roomStone' && ($player->countRooms() > $player->countFarmers())) {
      return 4;
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
