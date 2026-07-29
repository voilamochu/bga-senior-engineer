<?php
namespace AGR\Cards\B;
use AGR\Helpers\CardRulings;

class B99_Tutor extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B99_Tutor';
    $this->name = clienttranslate('Tutor');
    $this->deck = 'B';
    $this->number = 99;
    $this->category = POINTS_PROVIDER;
    $this->extraVp = true;
    $this->desc = [
      clienttranslate('During scoring, you get 1 bonus <SCORE> for each occupation played after this one.'),
    ];
    $this->players = '1+';

    $this->rulings = CardRulings::fromKeys([
      'ONLY_PLAYED_BY_OWNER',
    ]);
  }

  public function onBuy($player)
  {
    $this->setExtraDatas('prevOccupations', $player->countOccupations());
  }

  public function computeBonusScore()
  {
    $player = $this->getPlayer();
    $this->addBonusScoringEntry($player->countOccupations() - $this->getExtraDatas('prevOccupations'));
  }
}
