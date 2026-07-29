<?php
namespace AGR\Cards\C;
use AGR\Core\Globals;

class C100_Butler extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C100_Butler';
    $this->name = clienttranslate('Butler');
    $this->deck = 'C';
    $this->number = 100;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'If you play this card in round 11 or before, during scoring, you get 4 bonus <SCORE> if you then have more rooms than people.'
      ),
    ];
    $this->players = '1+';
    $this->extraVp = true;
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function onBuy($player)
  {
    if (Globals::getTurn() <= 11) {
      return $this->flagCardNode();
    }
  }
  
  public function computeBonusScore() {
    $player = $this->getPlayer();

    $rooms = $player->countRooms();
    $farmers = $player->countFarmers();

    if ($this->isFlagged() && ($rooms > $farmers)) {
      $this->addBonusScoringEntry(4);
    }
  }
}
