<?php

namespace AGR\Cards\D;

use AGR\Core\Notifications;
use AGR\Helpers\Utils;
use AGR\Models\MinorImprovement;
use AGR\Helpers\CardRulings;

class D60_LargePottery extends MinorImprovement
{  
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D60_LargePottery';
    $this->name = clienttranslate('Large Pottery');
    $this->deck = 'D';
    $this->number = 60;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate('[Anytime]'),
      '<CLAY> <ARROW> 2<FOOD>',
      clienttranslate('[Scoring]'),
      '3/5/6/7<CLAY> <ARROW-1X> 1/2/3/4<SCORE>',
    ];
    $this->vp = 3;
    //    $this->costText = clienttranslate('Return Pottery');
    $this->returnCards = ['Major_Pottery'];
    $this->cost = [
      CLAY => '1',
      STONE => '1',
    ];
    $this->prerequisite = clienttranslate('Return the Pottery');
    $this->isCorbariusOrDulcinaria = true;

    $this->extraVp = true;
    $this->exchanges = [Utils::formatExchange([CLAY => [FOOD => 2]], $this->name)];
    $this->scoresMap = [
      '3-4' => 1,
      '5' => 2,
      '6' => 3,
      '7+' => 4,
    ];

    $this->rulings = CardRulings::fromKeys([
      'BOTH_MINOR_AND_MAJOR',
    ]);
  }
  
  public function getOtherCardTypes() {
    return [MAJOR];
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if (!$player->hasPlayedCard('Major_Pottery')) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    if (!$player->hasPlayedCard('Major_Pottery')) {
      return;
    }
    return $player->pay(1,['cards'=>['type' => 'Major', 'list' => $this->returnCards]],clienttranslate('Return the Pottery'),false);
  }

  public function computeBonusScore()
  {
    $bonus = $this->getBonusScore();
    if ($bonus != 0) {
      $this->addBonusScoringEntry($bonus);
    } else {
      $this->addResourceScoringEntry(CLAY, $this->scoresMap, '<CLAY>', '<CLAY>');
    }
  }

  public function onEndOfGame()
  {
    $meeples = $this->endOfGameCleanup(CLAY, $this->scoresMap);
    if ($meeples != null) {
      Notifications::endOfGame($this->getPlayer(), $meeples, $this->name);
    }
  }
}
