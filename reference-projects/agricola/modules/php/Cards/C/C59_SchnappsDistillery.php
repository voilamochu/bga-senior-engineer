<?php
namespace AGR\Cards\C;

use AGR\Helpers\Utils;
use AGR\Helpers\CardRulings;

class C59_SchnappsDistillery extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C59_SchnappsDistillery';
    $this->name = clienttranslate('Schnapps Distillery');
    $this->deck = 'C';
    $this->number = 59;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In each feeding phase, you can use this card to turn exactly 1 <VEGETABLE> into 5 <FOOD>. During scoring, you get 1 bonus <SCORE> each for your 5th and 6th <VEGETABLE>.'
      ),
    ];
    $this->vp = 2;
    $this->cost = [
      STONE => 2,
      VEGETABLE => 1,
    ];
    $this->extraVp = true;
    $this->exchanges = [
      Utils::formatExchange([\VEGETABLE => [FOOD => 5], 'max' => 1], $this->name, [HARVEST], $this->id),
    ];

    $this->rulings = CardRulings::fromKeys([
      'MUST_USE_EXCHANGE_WINDOW',
    ]);
  }

  public function computeBonusScore()
  {
    $player = $this->getPlayer();
    $vegetables =
      $player->countReserveResource(VEGETABLE) +
      $player
        ->board()
        ->getGrowingCrops(VEGETABLE)
        ->count();

    $bonus = 0;
    if ($vegetables == 5) {
      $bonus = 1;
    } elseif ($vegetables >= 6) {
      $bonus = 2;
    }
    $this->addBonusScoringEntry($bonus);
  }
}
