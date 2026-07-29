<?php
namespace AGR\Cards\A;
use AGR\Helpers\Utils;

class A27_OvenSite extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A27_OvenSite';
    $this->name = clienttranslate('Oven Site');
    $this->deck = 'A';
    $this->number = 27;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you get 2 <WOOD> and you can immediately build the __Clay Oven__ or __Stone Oven__ major improvement. Either way, it only costs you 1 <CLAY> and 1 <STONE>.'
      ),
    ];
    $this->prerequisite = clienttranslate('Both Fireplace and Cooking Hearth');
    $this->rulings = [
      clienttranslate('__Oriental Fireplace__ (A060) counts as a Fireplace for this prerequisite.'),
    ];
    $this->isArtifexOrBubulcus = true;
    $this->bannedWeak1or2p = true;
    $this->bannedWeak3or4p = true;
    $this->replacesCostFor = ['ComputeCardCosts'];
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $fireplace = ($player->hasPlayedCard('Major_Fireplace1') || $player->hasPlayedCard('Major_Fireplace2') || $player->hasPlayedCard('A60_OrientalFireplace'));
    $hearth = ($player->hasPlayedCard('Major_CookingHearth1') || $player->hasPlayedCard('Major_CookingHearth2'));
    if (!($fireplace && $hearth)) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->gainNode([WOOD => 2]),
        $this->flagCardNode(),
        Utils::wrapOptional([
          'action' => IMPROVEMENT,
          'args' => [
            'types' => [MAJOR],
            'allowedPurchases' => ['Major_ClayOven', 'Major_StoneOven'],
          ]
        ]),
        $this->unflagCardNode(),
      ],
    ];
  }

  public function onPlayerComputeCardCosts($player, &$args)
  {
    if (!$this->isFlagged()) {
      return;
    }

    if (!in_array($args['card']->getId(), ['Major_ClayOven', 'Major_StoneOven'])) {
      return;
    }

    foreach ($args['costs']['trades'] as &$trade) {
      $trade[STONE] = 1;
      $trade[CLAY] = 1;
      $trade['sources'][] = $this->id;
    }
  }
}
