<?php

namespace AGR\Core;

use AGR\Helpers\Utils;
use AGR\Managers\Meeples;
use AGR\Managers\Players;

class Notifications
{
  /*************************
   **** GENERIC METHODS ****
   *************************/
  protected static function notifyAll($name, $msg, $data)
  {
    self::markPending();
    self::updateArgs($data);
    Game::get()->notifyAllPlayers($name, $msg, $data);
  }

  protected static function notify($player, $name, $msg, $data)
  {
    self::markPending();
    $pId = is_numeric($player) ? (int) $player : $player->getId();
    self::updateArgs($data);
    Game::get()->notifyPlayer($pId, $name, $msg, $data);
  }

  public static function message($txt, $args = [])
  {
    self::notifyAll('message', $txt, $args);
  }

  public static function messageTo($player, $txt, $args = [])
  {
    $pId = is_int($player) ? $player : $player->getId();
    self::notify($pId, 'message', $txt, $args);
  }

  public static function seed($seed)
  {
    self::notifyAll(
      'seed',
      clienttranslate('Want to play with the same configuration? Here is the seed of your game: ${seed}'),
      [
        'seed' => $seed,
      ]
    );
  }

  public static function campaignPermanentChosen($player, $card)
  {
    self::notifyAll(
      'campaignPermanentChosen',
      clienttranslate('${player_name} makes ${card_name} a permanent occupation for the rest of the campaign'),
      [
        'player' => $player,
        'card' => $card,
      ]
    );
  }

  public static function campaignPlayPermanent($player, $card)
  {
    self::notifyAll('buyCard', clienttranslate('${player_name} plays permanent occupation ${card_name}'), [
      'player' => $player,
      'card' => $card,
    ]);
  }

  public static function campaignNewGame($gameNumber, $goal, $cards, $campaign, $turn)
  {
    self::notifyAll(
      'campaignNewGame',
      clienttranslate('Campaign game ${gameNumber} begins — goal: ${goal} points'),
      [
        'gameNumber' => $gameNumber,
        'goal' => $goal,
        'cards' => $cards,
        'campaign' => $campaign,
        'turn' => $turn,
      ]
    );
  }

  public static function campaignComplete($results, $goals)
  {
    $hits = 0;
    foreach ($results as $r) {
      if ($r['hit']) {
        $hits++;
      }
    }
    self::notifyAll(
      'campaignComplete',
      clienttranslate('Campaign over — you hit the target in ${hits} game(s)'),
      [
        'hits' => $hits,
        'results' => $results,
        'goals' => $goals,
      ]
    );
  }

  public static function clearTurn($player, $notifIds)
  {
    self::notifyAll('clearTurn', clienttranslate('${player_name} restarts their turn'), [
      'player' => $player,
      'notifIds' => $notifIds,
    ]);
  }

  // Remove extra information from cards
  protected static function filterCardDatas($card)
  {
    return [
      'id' => $card['id'],
      'location' => $card['location'],
      'pId' => $card['pId'],
      'state' => $card['state'],
      'bonusVp' => $card['bonusVp'],
      'infobox' => $card['infobox'],
      'marked' => $card['marked'],
      'rulings' => $card['rulings'] ?? [],
      'stats' => $card['stats'] ?? [],
    ];
  }
  public static function refreshUI($datas)
  {
    // Keep only the thing that matters
    $fDatas = [
      'meeples' => $datas['meeples'],
      'players' => $datas['players'],
      'scores' => $datas['scores'],
      'playerCards' => $datas['playerCards'],
    ];

    foreach ($fDatas['playerCards'] as $i => $card) {
      $fDatas['playerCards'][$i] = self::filterCardDatas($card);
    }
    foreach ($fDatas['players'] as &$player) {
      $player['hand'] = []; // Hide hand !
    }

    self::notifyAll('refreshUI', '', [
      'datas' => $fDatas,
    ]);
  }

  public static function refreshHand($player, $hand)
  {
    foreach ($hand as &$card) {
      $card = self::filterCardDatas($card);
    }
    self::notify($player, 'refreshHand', '', [
      'player' => $player,
      'hand' => $hand,
    ]);
  }

  // refreshUI/refreshHand strip static fields assuming the client cache is
  // warm; for seed-loaded hands the cards are new, so prime the cache first.
  public static function populateCardCache($cards)
  {
    self::notifyAll('populateCardCache', '', ['cards' => $cards]);
  }

  public static function startNewTurn($turn)
  {
    self::notifyAll('startNewTurn', clienttranslate('Starting round n°${round}'), [
      'round' => $turn,
    ]);
  }

  public static function revealActionCard($card)
  {
    self::notifyAll('revealActionCard', clienttranslate('Action __${action}__ is revealed'), [
      'action' => $card->getName(),
      'i18n' => ['action'],
      'card' => $card,
      'turn' => Globals::getTurn(),
    ]);
  }

  public static function accumulate($meeples, $silent = false)
  {
    self::notifyAll('accumulation', $silent ? '' : clienttranslate('Accumulation spaces are being filled in'), [
      'resources' => $meeples->toArray(),
    ]);
  }

  public static function placeFarmer($player, $fId, $card, $source = null)
  {
    if ($source != null) {
      $msg = clienttranslate('${player_name} places a person on card ${card_name} (${source})');
    } else {
      $msg = clienttranslate('${player_name} places a person on card ${card_name}');
    }
    self::notifyAll('placeFarmer', $msg, [
      'card' => $card,
      'player' => $player,
      'farmer' => $fId,
      'source' => $source,
    ]);
  }

  public static function growFamily($player, $meeple)
  {
    self::notifyAll('growFamily', clienttranslate('${player_name} family grows'), [
      'player' => $player,
      'meeple' => $meeple,
    ]);
  }

  public static function growChildren($children)
  {
    self::notifyAll('growChildren', clienttranslate('${nb} child(ren) become(s) adult(s)'), [
      'nb' => count($children),
      'ids' => $children,
    ]);
  }

  public static function constructFences($player, $fences)
  {
    $msg =
      count($fences) == 1
      ? clienttranslate('${player_name} constructs one fence')
      : clienttranslate('${player_name} constructs ${nb} fences');

    self::notifyAll('addFences', $msg, [
      'nb' => count($fences),
      'fences' => $fences,
      'player' => $player,
    ]);

    // Update drop zones of player
    self::updateDropZones($player);
  }

  public static function plow($player, $field, $source = null)
  {
    if ($source != null) {
      $msg = clienttranslate('${player_name} plows one field (${source})');
    } else {
      $msg = clienttranslate('${player_name} plows one field');
    }
    self::notifyAll('plow', $msg, [
      'field' => $field,
      'player' => $player,
      'source' => $source,
    ]);
  }

  public static function sow($player, $sows, $noSeed = false, $isPlacement = false)
  {
    if ($noSeed) {
      // we are sowing only one thing
      $seeds = [];
      foreach ($sows as $sow) {
        // throw new \feException(print_r($sow));
        $seeds[] = ['type' => array_shift($sow['crops'])['type']];
      }
    } else {
      $seeds = array_map(function ($sow) {
        return $sow['seed'];
      }, $sows);
    }

    // Placement for A40_PottersYard
    if ($isPlacement) {
      $msg = clienttranslate('${player_name} places ${resources_desc} on unused farmyard spaces');
    } else {
      $msg = clienttranslate('${player_name} sows ${resources_desc}');
    }

    self::notifyAll('sow', $msg, [
      'player' => $player,
      'resources' => $seeds,
      'sows' => $sows,
    ]);
  }

  public static function construct($player, $rooms, $source = null)
  {
    // $msg =
    //   count($rooms) == 1
    //     ? clienttranslate('${player_name} constructs one room')
    // : clienttranslate('${player_name} constructs ${nb} rooms');

    if (count($rooms) == 1 && $source == null) {
      $msg = clienttranslate('${player_name} constructs one room');
    } elseif (count($rooms) == 1 && $source != null) {
      $msg = clienttranslate('${player_name} constructs one room (${source})');
    } else {
      $msg = clienttranslate('${player_name} constructs ${nb} rooms');
    }

    self::notifyAll('construct', $msg, [
      'nb' => count($rooms),
      'player' => $player,
      'rooms' => $rooms,
      'source' => $source,
    ]);

    // Update drop zones of player
    self::updateDropZones($player);
  }

  public static function renovate($player, $rooms)
  {
    self::notifyAll('renovate', clienttranslate('${player_name} renovates their house (${nb} rooms)'), [
      'nb' => count($rooms),
      'player' => $player,
      'rooms' => $rooms,
    ]);
  }

  public static function stables($player, $stables)
  {
    $msg =
      count($stables) == 1
      ? clienttranslate('${player_name} constructs one stable')
      : clienttranslate('${player_name} constructs ${nb} stables');

    self::notifyAll('addStables', $msg, [
      'nb' => count($stables),
      'player' => $player,
      'stables' => $stables,
    ]);

    // Update drop zones of player
    self::updateDropZones($player);
  }

  public static function collectResources($player, $meeples)
  {
    self::notifyAll('collectResources', clienttranslate('${player_name} collects ${resources_desc}'), [
      'player' => $player,
      'resources' => $meeples,
    ]);
  }

  public static function returnToReserve($player, $meeples)
  {
    foreach ($meeples as $meeple) {
      $meeple['target'] = 'reserve';
    }

    self::notifyAll('collectResources', clienttranslate('${player_name} returns ${resources_desc} to their reserve'), [
      'player' => $player,
      'resources' => $meeples,
    ]);
  }

  public static function moveAnimalsAround($player, $meeples)
  {
    self::notifyAll('collectResources', '', [
      'player' => $player,
      'resources' => $meeples,
    ]);
  }

  // Receive is for future resources on action card
  public static function receiveResource($player, $meeple)
  {
    self::notifyAll('collectResources', clienttranslate('${player_name} receives ${resources_desc}'), [
      'player' => $player,
      'resources' => [$meeple],
    ]);
  }

  public static function receiveResources($player, $meeples)
  {
    self::notifyAll('collectResources', clienttranslate('${player_name} receives ${resources_desc}'), [
      'player' => $player,
      'resources' => $meeples,
    ]);
  }

  // Receive is for future resources on action card
  public static function receiveRoundResource($player, $meeple)
  {
    self::notifyAll('collectRoundResources', clienttranslate('${player_name} receives ${resources_desc}'), [
      'player' => $player,
      'resources' => [$meeple],
    ]);
  }

  public static function gainResources($player, $meeples, $cardId = null, $source = null)
  {
    if ($source != null) {
      $msg = clienttranslate('${player_name} gains ${resources_desc} (${source})');
    } else {
      $msg = clienttranslate('${player_name} gains ${resources_desc}');
    }

    self::notifyAll('gainResources', $msg, [
      'i18n' => ['source'],
      'player' => $player,
      'resources' => $meeples,
      //'cardId' => $cardId,
      'source' => $source,
    ]);
  }

  public static function breed($player, $meeples, $source)
  {
    if ($source != null) {
      $msg = clienttranslate('${player_name} breeds ${resources_desc} (${source})');
    } else {
      $msg = clienttranslate('${player_name} breeds ${resources_desc}');
    }
    self::notifyAll('gainResources', $msg, [
      'i18n' => ['source'],
      'player' => $player,
      'resources' => $meeples,
      'source' => $source,
    ]);
  }

  public static function payResources($player, $resources, $source, $cardSources = [], $cardNames = [], $sourceInfo = [])
  {
    $data = [
      'i18n' => ['source'],
      'player' => $player,
      'resources' => $resources,
      'source' => $source,
      'sourceInfo' => $sourceInfo,
    ];
    $msg = clienttranslate('${player_name} pays ${resources_desc} for ${source}');

    // Card sources modifiers
    if (!empty($cardSources)) {
      $msg = clienttranslate('${player_name} pays ${resources_desc} for ${source} (${cards})');
      $data['i18n'][] = 'cards';

      $log = [];
      $args = [];
      foreach ($cardSources as $i => $cardId) {
        $log[] = '${card' . $i . '}';
        $args['card' . $i] = $cardNames[$cardId];
        $args['i18n'][] = 'card' . $i;
      }
      $data['cards'] = [
        'log' => implode(', ', $log),
        'args' => $args,
      ];
    }

    self::notifyAll('payResources', $msg, $data);
  }

  public static function payResourcesTo($player, $resources, $source, $cardSources = [], $cardNames = [], $otherPlayer)
  {
    $data = [
      'i18n' => ['source'],
      'player' => $player,
      'resources' => $resources,
      'source' => $source,
      'player2' => $otherPlayer,
    ];
    $msg = clienttranslate('${player_name} pays ${resources_desc} to ${player_name2} for ${source}');

    self::notifyAll('collectResources', $msg, $data);
  }

  public static function payWithCard($player, $card, $source)
  {
    self::notifyAll('payWithCard', clienttranslate('${player_name} returns ${card_name} for ${source}'), [
      'i18n' => ['source'],
      'player' => $player,
      'card' => $card,
      'source' => $source,
    ]);
  }

  public static function payResourcesFromFields($player, $resources, $source, $locations)
  {
    $data = [
      'i18n' => ['source'],
      'player' => $player,
      'resources' => $resources,
      'source' => $source,
    ];
    $pId = is_int($player) ? $player : $player->getId();
    $harvestedFields = Globals::getHarvestedFields();
    $fields = $harvestedFields[$pId] ?? [];
    Utils::filter($fields, function ($field) use ($locations) {
      return !in_array($field, $locations);
    });
    $harvestedFields[$pId] = $fields;
    Globals::setHarvestedFields($harvestedFields);
    $msg = clienttranslate('${player_name} pays ${resources_desc} from their fields for ${source}');

    self::notifyAll('payResources', $msg, $data);
  }

  public static function payResourcesFromCards($player, $resources, $source)
  {
    $data = [
      'i18n' => ['source'],
      'player' => $player,
      'resources' => $resources,
      'source' => $source,
    ];
    $msg = clienttranslate('${player_name} pays ${resources_desc} from ${source}');
    self::notifyAll('payResources', $msg, $data);
  }

  public static function discardAnimals($player, $resources)
  {
    self::notifyAll(
      'payResources',
      clienttranslate('${player_name} discards ${resources_desc} as no more room in the meadows'),
      [
        'player' => $player,
        'resources' => $resources,
      ]
    );
  }

  public static function endOfGame($player, $resources, $source)
  {
    $data = [
      'i18n' => ['source'],
      'player' => $player,
      'resources' => $resources,
      'source' => $source,
    ];

    self::notifyAll(
      'payResources',
      clienttranslate('${player_name} pays ${resources_desc} for bonus of ${source}'),
      $data
    );
  }

  /**
   * Silent kill slide the resources to top bar and destroy them whereas silent destroy simply destroy them
   */
  public static function silentKill($resources)
  {
    self::notifyAll('silentKill', '', [
      'resources' => $resources,
    ]);
  }

  public static function discardNewborn($player, $resources, $reason)
  {
    // We want to say "a newborn <ANIMAL>", need to get rid of the "1" before the animal icon 
    $token = '<' . strtoupper($resources[0]['type']) . '>';

    $msg = $reason == 'noParents'
      ? clienttranslate('${player_name} discards a newborn ${resources_desc} (not enough parents)')
      : clienttranslate('${player_name} discards a newborn ${resources_desc} (no room)');
    self::notifyAll('silentKill', $msg, [
      'player' => $player,
      'resources' => $resources,
      'resources_desc' => $token,
    ]);
  }
  public static function silentDestroy($resources)
  {
    self::notifyAll('silentDestroy', '', [
      'resources' => $resources,
    ]);
  }

  public static function returnHome($meeples)
  {
    self::notifyAll('returnHome', clienttranslate('End of turn. All people return home'), [
      'farmers' => $meeples->toArray(),
    ]);
  }

  public static function returnHomeToReserve($meeples)
  {
    self::notifyAll('returnHome', clienttranslate('All temporary people return to the supply'), [
      'farmers' => $meeples->toArray(),
    ]);
  }

  public static function returnHomeSilent($meeples)
  {
    self::notifyAll('returnHome', '', [
      'farmers' => $meeples->toArray(),
    ]);
  }

  public static function returnHomeOne($player, $meeples)
  {
    self::notifyAll('returnHome', clienttranslate('${player_name} returns a farmer to their home'), [
      'player' => $player,
      'farmers' => $meeples->toArray(),
    ]);
  }

  public static function returnToSupplyOne($player, $meeples)
  {
    self::notifyAll('returnHome', clienttranslate('${player_name} returns a farmer to their supply'), [
      'player' => $player,
      'farmers' => $meeples->toArray(),
    ]);
  }

  public static function adoptiveChildren($meeples)
  {
    self::notifyAll('returnHome', '', [
      'farmers' => $meeples->toArray(),
    ]);
  }

  public static function firstPlayer($player, $tokenId)
  {
    self::notifyAll('firstPlayer', clienttranslate('${player_name} takes the First player token'), [
      'player' => $player,
      'meepleId' => $tokenId,
    ]);
  }

  public static function updateDropZones($player)
  {
    self::notifyAll('updateDropZones', '', [
      'player' => $player,
      'zones' => $player->board()->getAnimalsDropZones(),
    ]);
  }

  public static function reorganize($player, $meeples)
  {
    self::notifyAll('reorganize', clienttranslate('${player_name} reorganizes their animals'), [
      'player' => $player,
      'meeples' => $meeples->toArray(),
    ]);
  }

  public static function startWork()
  {
    self::notifyAll('startWork', '', []);
  }

  public static function startReturnHome()
  {
    self::notifyAll('startReturnHome', '', []);
  }

  public static function startHarvest()
  {
    self::notifyAll('startHarvest', clienttranslate('Start of harvest phase'), [
      'turn' => Globals::getTurn(),
    ]);
  }

  public static function startHarvestField()
  {
    self::notifyAll('startHarvestField', '', []);
  }

  public static function startHarvestFeed()
  {
    self::notifyAll('startHarvestFeed', '', []);
  }

  public static function startHarvestBreed()
  {
    self::notifyAll('startHarvestBreed', '', []);
  }

  public static function harvestCrop($player, $crops)
  {
    self::notifyAll('harvestCrop', clienttranslate('${player_name} harvests ${resources_desc}'), [
      'player' => $player,
      'resources' => $crops,
    ]);
  }

  public static function buyCard($card, $player)
  {
    self::notifyAll('buyCard', clienttranslate('${player_name} buys ${card_name} (${card_type})'), [
      'player' => $player,
      'card' => $card,
    ]);
  }

  public static function buyAndPassCard($card, $player, $nextPlayer)
  {
    self::notifyAll(
      'buyAndPassCard',
      clienttranslate('${player_name} buys ${card_name} (${card_type}) and passes it to ${player_name2}'),
      [
        'player' => $player,
        'player2' => $nextPlayer,
        'card' => $card,
      ]
    );
  }

  public static function buyAndDestroyCard($card, $player)
  {
    self::notifyAll(
      'buyAndDestroyCard',
      clienttranslate('${player_name} buys ${card_name} (${card_type}) and removes it from the game'),
      [
        'player' => $player,
        'card' => $card,
      ]
    );
  }

  public static function passCard($card, $player, $nextPlayer)
  {
    self::notifyAll(
      'buyAndPassCard',
      clienttranslate('${player_name} passes ${card_name} (${card_type}) to ${player_name2}'),
      [
        'player' => $player,
        'player2' => $nextPlayer,
        'card' => $card,
      ]
    );
  }

  public static function destroyCard($card, $player)
  {
    self::notifyAll(
      'buyAndDestroyCard',
      clienttranslate('${player_name} removes ${card_name} (${card_type}) from the game'),
      [
        'player' => $player,
        'card' => $card,
      ]
    );
  }

  public static function placeMeeplesForFuture($player, $resources, $turns, $meeples)
  {
    $msg =
      count($turns) == 1
      ? clienttranslate('${player_name} puts ${resources_desc} on the action card of round ${turns}')
      : clienttranslate('${player_name} puts ${resources_desc} on the action cards of rounds ${turns}');

    self::notifyAll('placeMeeplesForFuture', $msg, [
      'player' => $player,
      'resources_desc' => Utils::resourcesToStr($resources),
      'turns' => implode(', ', $turns),
      'meeples' => $meeples->toArray(),
    ]);
  }

  public static function placeMeeplesFromSupply($player, $resources, $meeples, $spaces, $source)
  {
    $msg = clienttranslate('${player_name} places ${resources_desc} on ${spaces} (${source})');
    self::notifyAll('placeMeeplesFromSupply', $msg, [
      'player' => $player,
      'meeples' => $meeples->toArray(),
      'resources_desc' => Utils::resourcesToStr($resources),
      'spaces' => $spaces,
      'source' => $source,
    ]);
  }

  public static function exchange($player, $deleted, $created, $source)
  {
    $msg =
      $source == ''
      ? clienttranslate('${player_name} converts ${resources_desc} into ${resources2_desc}')
      : clienttranslate('${player_name} converts ${resources_desc} into ${resources2_desc} (${source})');

    self::notifyAll('exchange', $msg, [
      'i18n' => ['source'],
      'player' => $player,
      'resources' => $deleted,
      'resources2' => $created,
      'source' => $source,
    ]);
  }

  public static function discard($player, $discarded)
  {
    $msg = clienttranslate('${player_name} discards ${resources_desc} from their supply');

    self::notifyAll('payResources', $msg, [
      'player' => $player,
      'resources' => $discarded,
    ]);
  }

  public static function begging($player, $meeples)
  {
    self::notifyAll('gainResources', clienttranslate('${player_name} gets ${resources_desc} as food is missing'), [
      'player' => $player,
      'resources' => $meeples,
    ]);
  }

  public static function updateScores($scores)
  {
    self::notifyAll('updateScores', '', [
      'scores' => $scores,
    ]);
  }

  public static function updateHarvestCosts()
  {
    $data = [];
    foreach (Players::getAll() as $pId => $player) {
      $data[$pId] = $player->getHarvestCost();
    }

    self::notifyAll('updateHarvestCosts', '', [
      'costs' => $data,
    ]);
  }

  public static function addCardToDraftSelection($player, $card, $pos)
  {
    self::notify($player, 'addCardToDraftSelection', '', [
      'cardId' => $card->getId(),
      'pos' => $pos,
    ]);
  }
  public static function removeCardFromDraftSelection($player, $card)
  {
    self::notify($player, 'removeCardFromDraftSelection', '', [
      'cardId' => $card->getId(),
    ]);
  }
  public static function confirmDraftSelection($card)
  {
    $pId = $card->getPId();
    $player = Players::get($pId);
    self::notify($pId, 'confirmDraftSelection', clienttranslate('${player_name} picks ${card_name} (${card_type})'), [
      'player' => $player,
      'card' => $card,
    ]);
  }
  public static function clearDraftPools()
  {
    self::notifyAll('clearDraftPools', '', []);
  }

  public static function newDraftPile($pId, $cards, $turn, $type, $draftChoice = null)
  {
    self::notify($pId, 'newDraftPile', '', [
      'cards' => array_values($cards),
      'turn' => $turn,
      'type' => $type,
      'draftChoice' => $draftChoice,
    ]);
  }

  public static function draftProgress($allTurns, $total)
  {
    self::notifyAll('draftProgress', '', [
      'allTurns' => $allTurns,
      'total' => $total,
    ]);
  }

  public static function draftWaiting($pId, $predecessorPId, $allTurns, $total)
  {
    self::notify($pId, 'draftWaiting', '', [
      'predecessorId' => $predecessorPId,
      'allTurns' => $allTurns,
      'total' => $total,
    ]);
  }

  public static function draftIsOver()
  {
    self::notifyAll('draftIsOver', clienttranslate('Draft is over, starting the game now'), []);
  }

  public static function livingHandOfferCreated($pId, $offerCards, $type)
  {
    self::notify($pId, 'livingHandOfferCreated', '', [
      'offer' => $offerCards->ui(),
      'type' => $type,
    ]);
  }

  public static function livingHandPassDecision($player, $card, $decision)
  {
    $msg = $decision == 'decline'
      ? clienttranslate('${player_name} declines ${card_name}; it is discarded')
      : clienttranslate('${player_name} keeps ${card_name}');
    self::notifyAll('livingHandPassDecision', $msg, [
      'player' => $player,
      'card' => $card,
    ]);
  }

  public static function livingHandPicked($pId, $cardId, $pos)
  {
    self::notify($pId, 'livingHandPicked', '', [
      'cardId' => $cardId,
      'pos' => $pos,
    ]);
  }

  public static function livingHandOfferCleared($pId, $done = false)
  {
    self::notify($pId, 'livingHandOfferCleared', '', [
      'done' => $done,
    ]);
  }

  public static function A71_ClearingSpade($player, $crops)
  {
    self::notifyAll(
      'harvestCrop',
      clienttranslate('${player_name} moves ${resources_desc} on empty fields (Clearing Spade\'s effect)'),
      [
        'player' => $player,
        'resources' => $crops,
      ]
    );
  }

  public static function C51_FishingNet($meeples)
  {
    self::notifyAll(
      'accumulation',
      clienttranslate('${resources_desc} are put on Fishing space (Fishing Net\'s effect)'),
      [
        'resources' => $meeples->toArray(),
      ]
    );
  }

  public static function E4_Thunderbolt($meeples)
  {
    self::notifyAll(
      'payResources',
      clienttranslate('${resources_desc} are discarded from the field to the supply (Thunderbolt\'s effect)'),
      [
        'resources' => $meeples,
      ]
    );
  }

  public static function updateCardStats($cardId, $cardStats)
  {
    self::notifyAll('updateCardStats', '', [
      'cardId' => $cardId,
      'cardStats' => $cardStats,
    ]);
  }

  public static function placeInfobox($cardId, $string)
  {
    self::notifyAll('placeInfobox', '', [
      'i18n' => ['string'],
      'cardId' => $cardId,
      'string' => $string,
    ]);
  }

  public static function updateInfobox($cardId, $string)
  {
    self::notifyAll('updateInfobox', '', [
      'i18n' => ['string'],
      'cardId' => $cardId,
      'string' => $string,
    ]);
  }

  public static function markUsableExchange($cardId)
  {
    self::notifyAll('markUsableExchange', '', [
      'cardId' => $cardId,
    ]);
  }

  public static function unmarkUsableExchange($cardId)
  {
    self::notifyAll('unmarkUsableExchange', '', [
      'cardId' => $cardId,
    ]);
  }

  public static function unmarkAllUsableExchanges()
  {
    self::notifyAll('unmarkAllUsableExchanges', '', []);
  }

  /*************************
   ****** CARDS NOTIFS ******
   **************************/

   //B85 Farm Hand

  public static function farmHandStable($player, $pos, int $stableId)
  {
    self::notifyAll(
      'farmHandStable',
      clienttranslate('${player_name} builds a Farm Hand stable'),
      [
        'playerId' => $player->getId(),
        'player_name' => $player->getName(),
        'stableId' => $stableId,
        'farmHandStable' => [
          'x' => (int) $pos['x'],
          'y' => (int) $pos['y'],
        ],
      ]
    );
  }

  public static function farmHandStableReturned($player, $topLeft)
  {
    self::notifyAll(
      'farmHandStableReturned',
      clienttranslate('${player_name} returns the Farm Hand stable to their reserve'),
      [
        'playerId' => $player->getId(),
        'player_name' => $player->getName(),
        'topLeft' => $topLeft,
      ]
    );
  }

  /*********************
   **** UPDATE ARGS ****
   *********************/
  /*
   * Automatically adds some standard field about player and/or card
   */
  protected static function updateArgs(&$data)
  {
    if (isset($data['resource'])) {
      $names = [
        WOOD => clienttranslate('wood'),
        CLAY => clienttranslate('clay'),
        REED => clienttranslate('reed'),
        STONE => clienttranslate('stone'),
        GRAIN => clienttranslate('grain'),
        VEGETABLE => clienttranslate('vegetable'),
        SHEEP => clienttranslate('sheep'),
        PIG => clienttranslate('pig'),
        CATTLE => clienttranslate('cattle'),
        FOOD => clienttranslate('food'),
      ];

      $data['resource_name'] = $names[$data['resource']];
      $data['i18n'][] = 'resource_name';
    }

    if (isset($data['player'])) {
      $data['player_name'] = $data['player']->getName();
      $data['player_id'] = $data['player']->getId();
      unset($data['player']);
    }

    if (isset($data['player2'])) {
      $data['player_name2'] = $data['player2']->getName();
      $data['player_id2'] = $data['player2']->getId();
      unset($data['player2']);
    }

    if (isset($data['card'])) {
      $data['i18n'][] = 'card_name';
      $data['card_name'] = $data['card']->getName();

      if ($data['card'] instanceof \AGR\Models\PlayerCard) {
        $data['i18n'][] = 'card_type';
        $data['card_type'] = $data['card']->getTypeStr();
      }
    }

    if (isset($data['resources']) && !isset($data['resources_desc'])) {
      // Get an associative array $resource => $amount
      $resources = Utils::reduceResources($data['resources']);
      $data['resources_desc'] = Utils::resourcesToStr($resources);
    }

    if (isset($data['resources2'])) {
      // Get an associative array $resource => $amount
      $resources2 = Utils::reduceResources($data['resources2']);
      $data['resources2_desc'] = Utils::resourcesToStr($resources2);
    }

    // Append readable resource names to the icon tokens so BGA's archived text log (which doesn't
    // run game JS) reads "1 reed, 1 stone" not "1,1"; the live client strips them in formatStringMeeples.
    foreach (['resources_desc', 'resources2_desc'] as $descKey) {
      if (isset($data[$descKey])) {
        $data[$descKey] = self::appendResourceNames($data[$descKey]);
      }
    }
  }

  private static function appendResourceNames($str)
  {
    $names = [
      'WOOD' => clienttranslate('wood'),
      'CLAY' => clienttranslate('clay'),
      'REED' => clienttranslate('reed'),
      'STONE' => clienttranslate('stone'),
      'FOOD' => clienttranslate('food'),
      'GRAIN' => clienttranslate('grain'),
      'VEGETABLE' => clienttranslate('vegetable'),
      'SHEEP' => clienttranslate('sheep'),
      'PIG' => clienttranslate('pig'),
      'CATTLE' => clienttranslate('cattle'),
    ];
    foreach ($names as $token => $name) {
      $str = str_replace('<' . $token . '>', '<' . $token . '><span class="log-res-name"> ' . $name . '</span>', $str);
    }
    return $str;
  }

  /*********************
   **********************
   *********************/
  public static function updatePersonalOwned($pId, $personalOwned)
  {
    self::notifyAll('updatePersonalOwned', '', [
      'pId' => $pId,
      'personalOwned' => $personalOwned,
    ]);
  }

  // Flag used by Log::currentMaxPacketId() to skip empty flushes that would
  // otherwise create a gamelog packet and a 1s archive-replay pause.
  private static $hasPending = false;

  private static function markPending()
  {
    self::$hasPending = true;
  }

  public static function hasPending()
  {
    return self::$hasPending;
  }

  public static function markFlushed()
  {
    self::$hasPending = false;
  }
}
