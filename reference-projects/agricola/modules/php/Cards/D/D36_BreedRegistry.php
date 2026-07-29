<?php

namespace AGR\Cards\D;

use AGR\Core\Stats;

class D36_BreedRegistry extends \AGR\Models\MinorImprovement
{
  protected $sheepObtained = 0;

  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D36_BreedRegistry';
    $this->name = clienttranslate('Breed Registry');
    $this->deck = 'D';
    $this->number = 36;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'During scoring, if you gained at most 2 <SHEEP> from sources other than breeding during the game and have not turned any sheep into food, you get 3 bonus <SCORE>.'
      ),
    ];
    $this->extraVp = true;
    $this->prerequisite = clienttranslate('No Sheep');
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if ($player->countAnimalsOnBoard()[SHEEP] != 0) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    $n = Stats::getBoardSheep($player) + Stats::getCardsSheep($player);
    $this->setInfobox($n . ' / 2');
  }

  public function computeBonusScore()
  {
    $player = $this->getPlayer();
    $boardSheep = Stats::getBoardSheep($player);
    $cardsSheep = Stats::getCardsSheep($player);
    if ($boardSheep + $cardsSheep > 2) {
      return;
    }
    // B135_NutritionExpert use payGainNode to get food, that is not exchange and will not increase convertedSheep.
    // if that counts, we need to change B135 to add stats after payGain node.
    $convertedSheep = Stats::getConvertedSheep($player);
    if ($convertedSheep > 0) {
      return;
    }
    $this->addBonusScoringEntry(3);
  }
}