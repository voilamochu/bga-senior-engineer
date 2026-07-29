<?php
namespace AGR\Actions;

use AGR\Core\Engine;
use AGR\Core\Globals;
use AGR\Core\Notifications;
use AGR\Core\Stats;
use AGR\Managers\ActionCards;
use AGR\Managers\Meeples;
use AGR\Managers\PlayerCards;
use AGR\Managers\Players;
use AGR\Managers\Farmers;

class PlaceFarmer extends \AGR\Models\Action
{
  public function __construct($row)
  {
    $this->description = clienttranslate('Place a person');
    parent::__construct($row);
  }

  public function getState()
  {
    return ST_PLACE_FARMER;
  }

  public function getDescription($ignoreResources = false)
  {
    $fromSupply = '';
    if ($this->ctx->getArgs()['fromSupply'] ?? false) {
      $fromSupply = ' from your supply';
    }

    $source =  '';
    if (!is_null($this->ctx->getArgs()['source'] ?? null)) {
      $source = ' (' . PlayerCards::get($this->ctx->getArgs()['source'])->getName() . ')';
    }

    return [
      'log' => clienttranslate('Place a person${fromSupply}${source}'),
      'args' => [
        'fromSupply' => $fromSupply,
        'source' => $source,
      ],
    ];
  }

  public function isDoable($player, $ignoreResources = false)
  {
    $fromSupply = $this->ctx->getArgs()['fromSupply'] ?? false;

    return $player->hasFarmerAvailable() || $fromSupply;
  }

  /**
   * Compute the selectable actions cards/space for active player
   */
  function argsPlaceFarmer()
  {
    $player = Players::getActive();
    $cards = ActionCards::getVisible($player);
    $constraints = $this->getCtxArgs()['constraints'] ?? null;

    $args = [
      'allCards' => $cards->getIds(),
      'cards' => $cards
        ->filter(function ($card) use ($player) {
          return $card->canBePlayed($player);
        })
        ->getIds(),
    ];

    // Add cards that have been made usable by player cards
    $addedCards = array_merge(
      $player->computeModifiedPlacementConstraints(),
      $this->getCtxArgs()['added'] ?? []
    );
    if ($addedCards != []) {
      $args['cards'] = array_merge($args['cards'], $addedCards);
    }

    // Remove cards that have been made unusable by player cards (e.g. D50 Foreign Aid)
    $removedCards = $player->computeForbiddenCards();
    if ($removedCards != []) {
      // wanted to just use array_diff rather than this convoluted solution, not sure why it doesn't work
      $temp = [];
      foreach ($args['cards'] as $card) {
        if (!in_array($card, $removedCards)) {
          $temp[] = $card;
        }
      }
      $args['cards'] = $temp;
    }

    // When placing from supply (Work Permit, Telegram, etc.), the sole reserve farmer
    // is about to be consumed by this placement, so filter out action spaces whose
    // flow needs a farmer in reserve (i.e. family growth).
    $fromSupply = $this->getCtxArgs()['fromSupply'] ?? false;
    if ($fromSupply && !Farmers::hasMultipleInReserve($player->getId())) {
      $args['cards'] = array_values(array_filter($args['cards'], function ($cardId) {
        $card = ActionCards::get($cardId);
        return $card->getActionCardType() != 'WishChildren';
      }));
    }

    if ($constraints != null) {
      $args['cards'] = \array_values(\array_intersect($constraints, $args['cards']));
    }
    return $args;
  }

  /**
   * Place the farmer on a card/space and activate the corresponding card
   *   to update the flow tree
   */
  function actPlaceFarmer($cardId)
  {
    self::checkAction('actPlaceFarmer');
    $player = Players::getActive();
    $fromSupply = $this->ctx->getArgs()['fromSupply'] ?? false;
    $markForRemoval = $this->ctx->getArgs()['markForRemoval'] ?? false;

    $cards = self::argsPlaceFarmer()['cards'];
    if (!\in_array($cardId, $cards)) {
      throw new \BgaUserException(clienttranslate('You cannot place a person here'));
    }

    if (in_array($cardId, $player->getActionCards()->getIds())) {
      $card = PlayerCards::get($cardId);
    } else {
      $card = ActionCards::get($cardId);
    }

    $eventData = [
      'actionCardId' => $card->getId(),
      'actionCardType' => $card->getActionCardType(),
    ];

    if ($fromSupply) {
      Notifications::message(
        clienttranslate('${player_name} is using a temporary person from their supply'),
        [
          'player_name' => $player->getName(),
        ]
      );
    }

    // Place farmer
    $fId = $player->moveNextFarmerAvailable($cardId, null, $fromSupply);
    $player->trackPlacedFarmers($fId);

    // Bassinet: lock first qualifying space of the work phase
    if (Globals::isWorkPhase() && Globals::getBassinetFirstSpace() === '') {
      // Meeting Place blocks Bassinet for the round
      if (in_array($cardId, ['ActionMeetingPlace', 'ActionMeetingPlaceSolo'], true)) {
        Globals::setBassinetFirstSpace('EXCLUDED');
      } else {
        // Any non-accumulating space qualifies
        if (!$card->hasAccumulation()) {
          Globals::setBassinetFirstSpace($cardId);
        }
      }
    }
    
    Notifications::placeFarmer($player, $fId, $card, $this->ctx->getSource());
    Stats::incPlacedFarmers($player);

    // Are there cards triggered by the placement ?
    $eventData['fId'] = $fId;
    $this->checkListeners('PlaceFarmer', $player, $eventData);


    if ($fromSupply) {
      Farmers::setFarmerState(Farmers::get($fId), TEMP);
    }

    if ($markForRemoval) {
      Farmers::setFarmerState(Farmers::get($fId), REMOVE);
    }

    // Activate action card
    $flow = $card->getFlow($player);
    $this->checkModifiers('computePlaceFarmerFlow', $flow, 'flow', $player, $eventData);

    // D101 side effect
    if (!$card->hasAccumulation() && !isset($card->skipGains) && Meeples::getResourcesOnCard($cardId)->count() > 0) {
      $flow = [
        'type' => NODE_SEQ,
        'childs' => [
          [
            'action' => COLLECT,
            'cardId' => $cardId,
          ],
          $flow,
        ],
      ];
    }
    Engine::insertAsChild($flow);

    $this->checkAfterListeners($player, $eventData, false);
    $this->resolveAction(['actionCardId' => $cardId]);
  }
}
