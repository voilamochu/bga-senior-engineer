<?php

namespace AGR\Cards\D;

use AGR\Helpers\Utils;
use AGR\Models\MinorImprovement;
use AGR\Helpers\CardRulings;

class D59_EarthOven extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D59_EarthOven';
    $this->name = clienttranslate('Earth Oven');
    $this->deck = 'D';
    $this->number = 59;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate('[Anytime]'),
      '<VEGETABLE> <ARROW> 3<FOOD>      <PIG> <ARROW> 3<FOOD>',
      '<SHEEP> <ARROW> 2<FOOD>      <CATTLE> <ARROW> 3<FOOD>',
      clienttranslate('[__Bake Bread__ action:]'),
      '<GRAIN> <ARROW> 2<FOOD>',
    ];
    $this->vp = 3;
    $this->costText = clienttranslate('Return Fireplace');
    $this->returnCards = ['Major_Fireplace1', 'Major_Fireplace2', 'A60_OrientalFireplace'];
    $this->isCorbariusOrDulcinaria = true;

    $this->isCookery = true;
    $this->isBakingImprovement = true;
    $this->exchanges = [
      Utils::formatExchange([VEGETABLE => [FOOD => 3]], $this->name),
      Utils::formatExchange([SHEEP => [FOOD => 2]], $this->name),
      Utils::formatExchange([PIG => [FOOD => 3]], $this->name),
      Utils::formatExchange([CATTLE => [FOOD => 3]], $this->name),
      Utils::formatExchange([GRAIN => [FOOD => 2]], $this->name, [BREAD]),
    ];

    $this->rulings = CardRulings::fromKeys([
      'BOTH_MINOR_AND_MAJOR',
    ]);
  }

  public function getOtherCardTypes()
  {
    return [MAJOR];
  }

  public function getCosts($player, $args = [])
  {
    $costs = [];
    $costs['cards'] = ['type' => 'Major', 'list' => $this->returnCards];
    return $costs;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $hasFireplace = $player->hasPlayedCard('Major_Fireplace1') || $player->hasPlayedCard('Major_Fireplace2') ||
      $player->hasPlayedCard('A60_OrientalFireplace');

    if (!$hasFireplace) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }
}
