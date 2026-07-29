<?php
namespace AGR\Cards\C;

use AGR\Managers\PlayerCards;
use AGR\Helpers\Utils;

class C60_SmallPottersOven extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C60_SmallPottersOven';
    $this->name = clienttranslate("Small Potter's Oven");
    $this->deck = 'C';
    $this->number = 60;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
      'When you play this card, you immediately get 5 <FOOD>. Each time before you get a __Bake Bread__ action, you can build the __Clay Oven__ or __Stone Oven__ major improvement.'
      ),
    ];
    $this->vp = 5;
    $this->cost = [
      CLAY => '2',
    ];
    $this->prerequisite = ('Return the Clay / Stone Oven');
    $this->returnCards = ['Major_ClayOven', 'Major_StoneOven'];
    $this->isCorbariusOrDulcinaria = true;
    $this->bannedStrong1or2p = true;
    $this->bannedStrong3or4p = true;

    $this->rulings = [
      clienttranslate('With this card in play, you can take a __Bake Bread__ action even without a baking improvement, so long as you can build the Clay Oven or Stone Oven before baking.'),
    ];
  }

  public function onBuy($player)
  {
    //safety, matching Large Pottery pattern
    if (!$player->hasPlayedCard('Major_ClayOven') && !$player->hasPlayedCard('Major_StoneOven')) {
      return;
    }

    return [
      'type' => NODE_SEQ,
      'pId' => $this->pId,
      'childs' => [
        $player->pay(
          1,
          ['cards' => ['type' => 'Major', 'list' => $this->returnCards]],
          clienttranslate('Return the Clay Oven or Stone Oven'),
          false
        ),
        $this->gainNode([FOOD => 5]),
      ],
    ];
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $hasOven = $player->hasPlayedCard('Major_ClayOven') || $player->hasPlayedCard('Major_StoneOven');
    if (!$hasOven) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onPlayerIsDoable($player, &$args)
  {
    if ($args['isDoable']) {
      return;
    }

    $ctxArgs = $args['ctx'] == null ? null : (is_array($args['ctx']) ? $args['ctx'] : $args['ctx']->getArgs());

    $imprArgs = [
      'actionCardId' => $ctxArgs['actionCardId'] ?? null,
      'actionType' => $ctxArgs['actionType'] ?? null,
    ];

    $clay = PlayerCards::get('Major_ClayOven');
    $stone = PlayerCards::get('Major_StoneOven');

    // Building the oven only enables the bake if a bake can actually follow (own grain, or a conversion card)
    $canBakeAfter = ($player->getExchangeResources()[GRAIN] ?? 0) > 0 ||
      ($player->hasPlayedCard('D66_PotterCeramics') && $player->canPayCost([CLAY => 1])) ||
      ($player->hasPlayedCard('B67_HandTruck') && $player->getFarmersOnAccumulationSpaces() > 0);

    if ($args['action'] == EXCHANGE && ($ctxArgs['trigger'] ?? ANYTIME) == BREAD && $canBakeAfter && ($clay->isBuyable($player, false, $imprArgs) || $stone->isBuyable($player, false, $imprArgs))) {
      $args['isDoable'] = true;
    }
  }

  public function isListeningTo($event)
  {
    return $this->isBeforeEvent($event, 'Exchange') && ($event['args']['trigger'] ?? ANYTIME) == BREAD;
  }

  public function onPlayerBeforeExchange($player, $event)
  {
    // A space's bake enabled only by this build must actually happen
    $forced = Utils::isActionSpaceId($event['cardId'] ?? null) && !$player->canBake();

    return [
      'type' => NODE_SEQ,
      'pId' => $this->pId,
      'optional' => !$forced,
      'childs' => [
        [
          'action' => IMPROVEMENT,
          'pId' => $this->pId,
          'args' => [
            'types' => [MAJOR],
            'allowedPurchases' => ['Major_ClayOven', 'Major_StoneOven'],
            'trueAction' => false,
          ],
        ],
      ],
    ];
  }

}
