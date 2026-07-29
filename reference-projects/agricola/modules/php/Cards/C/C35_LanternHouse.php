<?php
namespace AGR\Cards\C;

use AGR\Helpers\CardRulings;

class C35_LanternHouse extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C35_LanternHouse';
    $this->name = clienttranslate('Lantern House');
    $this->deck = 'C';
    $this->number = 35;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'During scoring, you get 1 negative <SCORE> for each card left in your hand. You cannot discard cards from your hand unplayed. If you already have, you cannot play this card.'
      ),
    ];
    $this->vp = 7;
    $this->cost = [
      WOOD => 1,
    ];
    $this->extraVp = true;
    $this->prerequisite = clienttranslate('No occupation');
    $this->occupationPrerequisites = ['max' => 0];
    $this->bannedLiving = true;
    $this->rulings = CardRulings::fromKeys([
      'ILLUSIONIST_LANTERN_HOUSE',
    ]);
  }

  public function computeBonusScore()
  {
    $player = $this->getPlayer();
    $handCards = $player->getHand()->count();
    $this->addBonusScoringEntry($handCards * -1);
  }
}
