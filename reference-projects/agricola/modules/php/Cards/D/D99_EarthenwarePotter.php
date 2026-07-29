<?php
namespace AGR\Cards\D;
use AGR\Core\Globals;

class D99_EarthenwarePotter extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D99_EarthenwarePotter';
    $this->name = clienttranslate('Earthenware Potter');
    $this->deck = 'D';
    $this->number = 99;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'If you play this card in round 4 or before, after the final harvest, you get 1 bonus <SCORE> for each person for which you then pay 1 <CLAY>.'
      ),
    ];
    $this->players = '1+';
    $this->extraVp = true;
    $this->isCorbariusOrDulcinaria = true;
  }

  public function onBuy($player)
  {
    if (Globals::getTurn() <= 4) {
      return $this->flagCardNode();
    }
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && 
      $event['type'] == 'AfterHarvest';
  }

  public function onPlayerAfterHarvest($player)
  {
    if (!$this->isFlagged() || Globals::getTurn() < 14) {
      return;
    }

    $clay = $player->countReserveResource(CLAY);
    $farmers = $player->countFarmers();
    $n = min($clay, $farmers);

    if ($n > 0) {
      return $this->payGainNode([CLAY => $n], [SCORE => $n], null, false);
    }
  }
}
