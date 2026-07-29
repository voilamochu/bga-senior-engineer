<?php
namespace AGR\Cards\A;

class A31_DebtSecurity extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A31_DebtSecurity';
    $this->name = clienttranslate('Debt Security');
    $this->deck = 'A';
    $this->number = 31;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'During scoring, you get 1 bonus <SCORE> for each major improvement you have, up to the number of your unused farmyard spaces.'
      ),
    ];
    $this->cost = [
      FOOD => 2,
    ];
    $this->extraVp = true;
    $this->isArtifexOrBubulcus = true;
  }

  public function computeBonusScore()
  {
    $player = $this->getPlayer();
    $majors = $player->getCards(MAJOR, true)->count();
    $unusedSpaces = count($player->board()->getFreeZones());

    $bonus = min($majors, $unusedSpaces);

    if ($bonus > 0) {
      $this->addBonusScoringEntry($bonus);
    }
  }
}
