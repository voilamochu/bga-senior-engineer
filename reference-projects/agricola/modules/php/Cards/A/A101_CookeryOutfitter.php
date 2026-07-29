<?php
namespace AGR\Cards\A;

class A101_CookeryOutfitter extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A101_CookeryOutfitter';
    $this->name = clienttranslate('Cookery Outfitter');
    $this->deck = 'A';
    $this->number = 101;
    $this->category = POINTS_PROVIDER;
    $this->desc = [clienttranslate('During scoring, you get 1 bonus <SCORE> for each cooking improvement you have. (Ovens are not considered cooking improvements.)')];
    $this->players = '1+';
    $this->extraVp = true;
    $this->isArtifexOrBubulcus = true;

    $this->rulings = [
      clienttranslate('Ovens do not count towards this card.'),
    ];
  }
  
  public function computeBonusScore()
  {
    $player = $this->getPlayer();
    $bonus = 0;
    $cards = [];

    foreach ($player->getCards(MAJOR, true) as $card) {
      if ($card->canCook() && !in_array($card->id, $cards)) {
        $cards[] = $card->id;
        $bonus++;
      }
    }
    foreach ($player->getCards(MINOR, true) as $card) {
      if ($card->canCook() && !in_array($card->id, $cards)) {
        $cards[] = $card->id;
        $bonus++;
      }
    }

    if ($bonus > 0) {
      $this->addBonusScoringEntry($bonus);
    }
  } 
}
