<?php

namespace AGR\Cards\A;

use AGR\Helpers\Utils;
use AGR\Models\MinorImprovement;
use AGR\Helpers\CardRulings;

class A60_OrientalFireplace extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A60_OrientalFireplace';
    $this->name = clienttranslate('Oriental Fireplace');
    $this->deck = 'A';
    $this->number = 60;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate('[Anytime]'),
      '<VEGETABLE> <ARROW> 4<FOOD>      <SHEEP> <ARROW> 3<FOOD>',
      '<CATTLE> <ARROW> 5<FOOD>',
      clienttranslate('[__Bake Bread__ action:]'),
      '<GRAIN> <ARROW> 2<FOOD>',
    ];
    $this->vp = 1;
    $this->costText = clienttranslate('Return Fireplace/ Cooking Hearth');
    $this->returnCards = ['Major_Fireplace1', 'Major_Fireplace2', 'Major_CookingHearth1', 'Major_CookingHearth2'];
    $this->isArtifexOrBubulcus = true;

    $this->isCookery = true;
    $this->isBakingImprovement = true;
    $this->exchanges = [
      Utils::formatExchange([VEGETABLE => [FOOD => 4]], $this->name),
      Utils::formatExchange([SHEEP => [FOOD => 3]], $this->name),
      Utils::formatExchange([CATTLE => [FOOD => 5]], $this->name),
      Utils::formatExchange([GRAIN => [FOOD => 2]], $this->name, [BREAD]),
    ];

    $this->rulings = array_merge(
      CardRulings::fromKeys([
      'BOTH_MINOR_AND_MAJOR',
      ]),
      [
      clienttranslate('If returned to pay for a Cooking Hearth or Earth Oven, remove this card from the game.'),
      ]
    );
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
    $hasCookery = $player->hasPlayedCard('Major_Fireplace1') || $player->hasPlayedCard('Major_Fireplace2') ||
      $player->hasPlayedCard('Major_CookingHearth1') || $player->hasPlayedCard('Major_CookingHearth2');

    if (!$hasCookery) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }
}
