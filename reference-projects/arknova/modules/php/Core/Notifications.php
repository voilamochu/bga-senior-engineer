<?php

namespace ARK\Core;

use ARK\Managers\Players;
use ARK\Helpers\Utils;
use ARK\Helpers\Collection;
use ARK\Core\Globals;
use ARK\Models\Player;
use ARK\Models\ZooCard;

class Notifications
{
  protected static $listeners = [
    [
      'name' => 'icons',
      'player' => true,
      'method' => 'countCardIcons',
    ],
    [
      'name' => 'sizes',
      'player' => true,
      'method' => 'countAnimalsBySizes',
    ],
    [
      'name' => 'income',
      'player' => true,
      'method' => 'getMoneyIncome',
    ],
    [
      'name' => 'score',
      'player' => true,
      'method' => 'updateScore',
    ],
    [
      'name' => 'mapStatus',
      'player' => true,
      'method' => 'getMapStatus',
    ],
    [
      'name' => 'handLimitStatus',
      'player' => true,
      'method' => 'getHandStatus',
    ],
    [
      'name' => 'projectStrength',
      'player' => true,
      'method' => 'getSupportProjectCost',
    ]
  ];
  protected static $ignoredNotifs = ['drawCards', 'discardCards', 'pilferingCard', 'storeCard', 'unstoreCard'];

  protected static $cachedValues = [];
  public static function resetCache()
  {
    foreach (self::$listeners as $listener) {
      $method = $listener['method'];
      if ($listener['player'] ?? false) {
        foreach (Players::getAll() as $pId => $player) {
          if ($method == 'updateScore') $method = 'getNewScore'; // Avoid race condition

          self::$cachedValues[$listener['name']][$pId] = $player->$method();
        }
      } else {
        self::$cachedValues[$listener['name']] = call_user_func($method);
      }
    }
  }

  public static function updateIfNeeded(&$args, $notifName, $notifType)
  {
    foreach (self::$listeners as $listener) {
      $name = $listener['name'];
      $method = $listener['method'];

      if ($listener['player'] ?? false) {
        foreach (Players::getAll() as $pId => $player) {
          $val = $player->$method();
          if ($val !== (self::$cachedValues[$name][$pId] ?? null)) {
            $args['infos'][$name][$pId] = $val;
            // Only bust cache when a public non-ignored notif is sent to make sure everyone gets the info
            if ($notifType == 'public' && !in_array($notifName, self::$ignoredNotifs)) {
              self::$cachedValues[$name][$pId] = $val;
            }
          }
        }
      } else {
        $val = call_user_func($method);
        if ($val !== (self::$cachedValues[$name] ?? null)) {
          $args['infos'][$name] = $val;
          // Only bust cache when a public non-ignored notif is sent to make sure everyone gets the info
          if ($notifType == 'public' && !in_array($notifName, self::$ignoredNotifs)) {
            self::$cachedValues[$name] = $val;
          }
        }
      }
    }
  }


  /*************************
   **** GENERIC METHODS ****
   *************************/
  protected static function notifyAll($name, $msg, $data)
  {
    self::updateArgs($data);
    self::updateIfNeeded($data, $name, 'public');
    Game::get()->notifyAllPlayers($name, $msg, $data);
  }

  protected static function notify($player, $name, $msg, $data)
  {
    $pId = is_int($player) ? $player : $player->getId();
    self::updateArgs($data, $name, 'private');
    Game::get()->notifyPlayer($pId, $name, $msg, $data);
  }

  public static function message($txt, $args = [])
  {
    self::notifyAll('midmessage', $txt, $args);
  }

  public static function messageTo($player, $txt, $args = [])
  {
    $pId = is_int($player) ? $player : $player->getId();
    self::notify($pId, 'message', $txt, $args);
  }

  public static function newUndoableStep($player, $stepId)
  {
    self::notify($player, 'newUndoableStep', clienttranslate('Undo here'), [
      'stepId' => $stepId,
      'preserve' => ['stepId'],
    ]);
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
    ];
  }
  public static function refreshUI($datas)
  {
    // // Keep only the thing that matters
    $fDatas = [
      'players' => $datas['players'],
      'cards' => $datas['cards'],
      'buildings' => $datas['buildings'],
      'meeples' => $datas['meeples'],
      'break' => $datas['break'],
      'conservationBonuses' => $datas['conservationBonuses'],
      'deckCount' => $datas['deckCount'],
      'discardCount' => $datas['discardCount'],
      'endOfGame' => $datas['endOfGame'],
    ];

    foreach ($fDatas['cards'] as $i => $card) {
      $fDatas['cards'][$i] = self::filterCardDatas($card);
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

  public static function removeActionCards()
  {
    self::notifyAll('removeActionCards', '', ['players_Id' => Players::getAll()->getIds()]);
  }

  public static function chooseCard($player, $card, $strength, $xtokens)
  {
    self::notifyAll(
      'chooseActionCard',
      clienttranslate(
        '${player_name} chooses action card ${action_card_name}${action_card_icon}${action_card_level} with strength ${strength_icon}${strength}'
      ),
      [
        'player' => $player,
        'actionCard' => $card,
        'strength' => $strength,
      ]
    );
  }

  public static function actionCardCleanup($player, $actionCard, $position, $cards, $msg = null)
  {
    self::notifyAll(
      'actionCardCleanup',
      $msg ?? clienttranslate('${player_name} places action card ${action_card_name}${action_card_icon} at position ${position} (finishing action)'),
      [
        'i18n' => ['card_type'],
        'player' => $player,
        'actionCard' => $actionCard,
        'position' => $position,
        'actionCards' => $cards,
      ]
    );
  }

  public static function buyBuilding($player, $cost, $building, $card)
  {
    $data = [
      'player' => $player,
      'amount_money' => $cost ?? 0,
      'total' => $player->getMoney(),
      'building' => $building,
    ];
    $msg = clienttranslate('${player_name} pays ${amount_money} for building ${building_name}');
    if (is_null($cost)) {
      $msg = clienttranslate('${player_name} adds ${building_name} for free');

      if (!is_null($card)) {
        $msg = clienttranslate('${player_name} adds ${building_name} (${card_name})');
        $data['card'] = $card;
      }
    }

    self::notifyAll('buyBuilding', $msg, $data);
  }

  public static function increaseSize($player, $coveredBuilding, $building)
  {
    self::notifyAll('increaseSize', clienttranslate('${player_name} increase the size of a ${building_name}'), [
      'player' => $player,
      'building' => $coveredBuilding,
      'newBuilding' => $building,
    ]);
  }

  public static function upgradeCard($player, $card)
  {
    self::notifyAll(
      'upgradeCard',
      clienttranslate('${player_name} upgrades ${action_card_name}${action_card_icon}${action_card_level}'),
      [
        'player' => $player,
        'actionCard' => $card,
      ]
    );
  }

  public static function placeWorkers($player, $strength, $workers)
  {
    $msgs = [
      0 => clienttranslate('${player_name} donates to an association'),
      2 => clienttranslate('${player_name} increases reputation'),
      3 => clienttranslate('${player_name} takes a new Partner zoo'),
      4 => clienttranslate('${player_name} takes a new university'),
      TAKE_IN_RANGE_OR_DECK => clienttranslate('${player_name} chooses to take/draw one card (Association4 effect)'),
    ];

    self::notifyAll('slideMeeples', $msgs[$strength], [
      'player' => $player,
      'strength' => $strength,
      'meeples' => $workers->toArray(),
    ]);
  }

  public static function association2HireWorker($player, $workers)
  {
    self::notifyAll('slideMeeples', clienttranslate('${player_name} hires a new worker (Association2 effect)'), [
      'player' => $player,
      'meeples' => $workers->toArray(),
    ]);
  }

  public static function takeSpecializedUniv($genericUniv)
  {
    self::notifyAll('discardTokens', '', [
      'meeples' => [$genericUniv]
    ]);
  }

  public static function placeWorkersProject($player, $strength, $workers, $project, $slot)
  {
    $slotNames = [
      0 => \clienttranslate('the first slot'),
      1 => \clienttranslate('the second slot'),
      2 => \clienttranslate('the third slot'),
    ];
    self::notifyAll('slideMeeples', clienttranslate('${player_name} supports a conservation project on ${slot} : ${card_name}'), [
      'player' => $player,
      'strength' => $strength,
      'meeples' => $workers->toArray(),
      'card' => $project,
      'slot' => $slotNames[$slot],
      'i18n' => ['slot'],
    ]);
  }

  public static function donation($player, $cost, $meeple)
  {
    if ($cost == 0) {
      self::notifyAll('donation', \clienttranslate('${player_name} donates for free to get ${bonuses2_desc}'), [
        'player' => $player,
        'bonuses' => [],
        'bonuses2' => [CONSERVATION => 1],
        'meeple' => $meeple,
        'score' => $player->updateScore(),
      ]);
    } else {
      self::notifyAll('donation', \clienttranslate('${player_name} donates ${bonuses_desc} to get ${bonuses2_desc}'), [
        'player' => $player,
        'bonuses' => [MONEY => -$cost],
        'bonuses2' => [CONSERVATION => 1],
        'meeple' => $meeple,
        'score' => $player->updateScore(),
      ]);
    }
  }

  public static function addUniversity($player, $meeple)
  {
    self::notifyAll('slideMeeples', '', ['meeples' => [$meeple], 'player' => $player]);
  }
  public static function addPartnerZoo($player, $meeple)
  {
    self::notifyAll('slideMeeples', '', ['meeples' => [$meeple], 'player' => $player]);
  }

  public static function slideMeeples($meeples)
  {
    self::notifyAll('slideMeeples', '', ['meeples' => $meeples]);
  }

  public static function addMeeples($meeples)
  {
    self::notifyAll('addMeeples', '', ['meeples' => $meeples]);
  }

  public static function removeContinentMarker($player, $continent, $meeple)
  {
    self::notifyAll('discardTokens', clienttranslate('${player_name} removes ${continent} marker from their map'), [
      'player' => $player,
      'continent' => '<' . mb_strtoupper($continent) . '>',
      'meeples' => [$meeple],
    ]);
  }

  public static function takeBonus($player, $type, $n, $source, $sourceType, $remove, $bonusTiles, $meeple)
  {
    $msg = clienttranslate('${player_name} gets ${bonus_desc}');
    $data = [
      'player' => $player,
      'i18n' => ['bonus_desc'],
      'bonus_desc' => [
        'log' => '${bonus_raw_desc}${bonus_pentagon}',
        'args' => [
          'bonus_source_type' => $sourceType,
          'bonus_pentagon' => '',
          'bonus_type' => $type,
          'bonus_n' => $n,
          'bonus_raw_desc' => $n . ' x ' . $type,
        ]
      ]
    ];

    if (!is_null($source)) {
      $msg = clienttranslate('${player_name} gets ${bonus_desc} (${source})');
      $data['i18n'][] = 'source';
      $data['source'] = $source;
    }

    if (!is_null($remove)) {
      $data['remove'] = $remove;
      $data['conservationBonuses'] = $bonusTiles;
    }

    if (!is_null($meeple)) {
      $data['meeple'] = $meeple;
    }

    if ($type == DISCARD_SCORING) {
      $msg = clienttranslate('${player_name} triggers scoring card discard by reaching 10 conservation points');
    }

    self::notifyAll('takeBonus', $msg, $data);
  }

  public static function useBonus($player, $type, $n, $source, $meeple)
  {
    $msg = clienttranslate('${player_name} uses ${bonus_desc}');
    $data = [
      'player' => $player,
      'i18n' => ['bonus_desc'],
      'meeples' => [$meeple],
      'bonus_desc' => [
        'log' => '${bonus_raw_desc}${bonus_pentagon}',
        'args' => [
          'bonus_source_type' => 'bonus',
          'bonus_pentagon' => '',
          'bonus_type' => $type,
          'bonus_n' => $n,
          'bonus_raw_desc' => $n . ' x ' . $type,
        ]
      ]
    ];

    self::notifyAll('discardTokens', $msg, $data);
  }


  /////////////////////////////////
  //  ____       _
  // / ___|  ___| |_ _   _ _ __
  // \___ \ / _ \ __| | | | '_ \
  //  ___) |  __/ |_| |_| | |_) |
  // |____/ \___|\__|\__,_| .__/
  //                      |_|
  /////////////////////////////////
  public static function setupPlayer($player, $mapId, $cards, $meeples, $buildings)
  {
    self::notifyAll('setupPlayer', \clienttranslate('${player_name} will play Map ${mapId}'), [
      'player' => $player,
      'mapId' => $mapId,
      'meeples' => $meeples->toArray(),
      'buildings' => $buildings,
      'action_cards' => $cards->ui(),
    ]);
  }

  public static function setupActionCards($player,  $cards)
  {
    $actionCards = $cards->toArray();
    $names = [
      'log' => clienttranslate('${actionCard0}, ${actionCard1}, ${actionCard2}, ${actionCard3} and ${actionCard4}'),
      'args' => []
    ];
    for ($i = 0; $i < 5; $i++) {
      $card = $actionCards[$i];
      $key = "actionCard$i";
      $names['args']['i18n'] = $key;
      $names['args'][$key] = $card->getNumber() == 0 ? $card->getName() : [
        'log' => clienttranslate('${actionCardType} ${actionCardNumber}'),
        'args' => [
          'actionCardType' => $card->getName(),
          'actionCardNumber' => $card->getNumber(),
          'i18n' => ['actionCardType']
        ]
      ];
    }


    self::notifyAll('setupActionCards', clienttranslate('${player_name} will play with action cards: ${action_cards_names}'), [
      'player' => $player,
      'action_cards' => $cards->ui(),
      'action_cards_names' => $names,
    ]);
  }

  public static function updateInitialSelection($player, $args)
  {
    self::notify($player, 'updateInitialSelection', '', [
      'args' => ['_private' => $args['_private'][$player->getId()]],
    ]);
  }

  public static function updateInitialMapSelection($player, $args)
  {
    self::notify($player, 'updateInitialMapSelection', '', [
      'args' => ['_private' => $args['_private'][$player->getId()]],
    ]);
  }

  public static function updateInitialActionCardSelection($player, $args)
  {
    self::notify($player, 'updateInitialActionCardSelection', '', [
      'args' => ['_private' => $args['_private'][$player->getId()]],
    ]);
  }

  public static function updateInitialActionCardsKeep($player, $args)
  {
    self::notify($player, 'updateInitialActionCardsKeep', '', [
      'args' => ['_private' => $args['_private'][$player->getId()]],
    ]);
  }

  /////////////////////////////////
  //  ____                 _
  // | __ ) _ __ ___  __ _| | __
  // |  _ \| '__/ _ \/ _` | |/ /
  // | |_) | | |  __/ (_| |   <
  // |____/|_|  \___|\__,_|_|\_\
  /////////////////////////////////

  public static function advanceBreak($player, $n, $break, $maxBreak)
  {
    $data = ['player' => $player, 'n' => $n, 'break' => $break, 'maxBreak' => $maxBreak];
    if ($break == $maxBreak) {
      $msg = clienttranslate(
        '${player_name} advances break token of ${n} space(s) and reach the last space of the Break track. At the end of the turn, all players must take a break'
      );
    } else {
      $msg = clienttranslate('${player_name} advances break token of ${n} space(s), now at ${break}/${maxBreak}');
    }

    self::notifyAll('advanceBreak', $msg, $data);
  }

  public static function startBreak()
  {
    self::notifyAll('startBreak', \clienttranslate('Starting a new break'), []);
  }

  public static function updateBreakDiscardSelection($player, $args)
  {
    self::notify($player, 'updateBreakDiscardSelection', '', [
      'args' => ['_private' => $args['_private'][$player->getId()]],
    ]);
  }

  public static function breakCleanupTokens($tokens)
  {
    self::notifyAll('discardTokens', clienttranslate('All tokens are removed from player cards'), [
      'meeples' => $tokens,
    ]);
  }

  public static function breakReturnWorkers($workers)
  {
    self::notifyAll('slideMeeples', clienttranslate('All workers go back to each player\'s reserve'), [
      'meeples' => $workers->toArray(),
    ]);
  }

  public static function breakRefill($meeples)
  {
    self::notifyAll('addMeeples', clienttranslate('Replenishing partner zoos and universities'), [
      'meeples' => $meeples->toArray(),
    ]);
  }

  public static function finishBreak()
  {
    self::notifyAll('finishBreak', \clienttranslate('End of the break'), []);
  }

  public static function finalScoring($player, $score, $newScore, $appeal, $conservation, $conservationScore)
  {
    self::notifyAll(
      'finalScoring',
      clienttranslate(
        '${player_name} has ${appeal}<APPEAL> and scores ${conservationScore} for having ${conservation}<CONSERVATION>. ${player_name} scores ${newScore}.'
      ),
      [
        'player' => $player,
        'score' => $score,
        'newScore' => $newScore,
        'appeal' => $appeal,
        'conservation' => $conservation,
        'conservationScore' => $conservationScore,
        'scoringHand' => $player->getScoringHand()->ui(),
      ]
    );
  }

  public static function endOfGame($player)
  {
    $msg = Globals::isBreak()
      ? clienttranslate('End of game triggered during a break: everyone will get a last turn to play, including ${player_name}')
      : clienttranslate('End of game triggered: everyone except ${player_name} will get a last turn to play');
    self::notifyAll('endOfGame', $msg, ['player' => $player]);
  }

  ////////////////////////////////
  //    ____              _
  //   / ___|__ _ _ __ __| |___
  //  | |   / _` | '__/ _` / __|
  //  | |__| (_| | | | (_| \__ \
  //   \____\__,_|_|  \__,_|___/
  ////////////////////////////////

  public static function drawCards($player, $cards, $privateMsg = null, $publicMsg = null, $args = [])
  {
    self::notifyAll(
      'drawCards',
      $publicMsg ?? clienttranslate('${player_name} draws ${n} card(s) from the deck'),
      $args + [
        'player' => $player,
        'n' => count($cards),
      ]
    );
    self::notify(
      $player,
      'pDrawCards',
      $privateMsg ?? clienttranslate('You draw ${card_names} from the deck'),
      $args + [
        'player' => $player,
        'cards' => is_array($cards) ? $cards : $cards->toArray(),
      ]
    );
  }

  public static function initialDraw($player, $cards, $scoringCards)
  {
    self::drawCards($player, $cards);
    self::drawCards(
      $player,
      $scoringCards,
      clienttranslate('You draw ${card_names} from the deck (scoring cards)'),
      clienttranslate('${player_name} draws ${n} scoring cards from the deck'),
      [
        'scoringCard' => true,
      ]
    );
  }

  public static function snapCard($player, $card)
  {
    self::notifyAll('snapCard', clienttranslate('${player_name} snaps ${card_names} from the display'), [
      'player' => $player,
      'cards' => [$card],
    ]);
  }

  public static function takeCardInRange($player, $card)
  {
    self::notifyAll('snapCard', clienttranslate('${player_name} takes ${card_names} in reputation range from the display'), [
      'player' => $player,
      'cards' => [$card],
    ]);
  }

  public static function sponsorMagnet($player, $cards)
  {
    self::notifyAll(
      'sponsorMagnet',
      clienttranslate('${player_name} takes ${card_names} from the display (Sponsor magnet effect)'),
      [
        'player' => $player,
        'cards' => $cards->toArray(),
      ]
    );
  }

  public static function seaAnimalMagnet($player, $cards)
  {
    self::notifyAll(
      'sponsorMagnet',
      clienttranslate('${player_name} takes ${card_names} from the display (Sea animal magnet effect)'),
      [
        'player' => $player,
        'cards' => $cards->toArray(),
      ]
    );
  }

  public static function animalMagnet($player, $cards)
  {
    self::notifyAll(
      'sponsorMagnet',
      clienttranslate('${player_name} takes ${card_names} from the display (Animal magnet effect)'),
      [
        'player' => $player,
        'cards' => $cards->toArray(),
      ]
    );
  }


  public static function fillPool($cards, $pool)
  {
    self::notifyAll('fillPool', clienttranslate('The display is replenished with ${card_names}'), [
      'cards' => $cards,
      'pool' => $pool->toArray(),
    ]);
  }

  public static function discardCards($player, $cards, $privateMsg = null, $publicMsg = null, $args = [], $privateArgs = null)
  {
    self::notifyAll(
      'discardCards',
      $publicMsg ?? clienttranslate('${player_name} discards ${n} card(s)'),
      $args + [
        'player' => $player,
        'n' => count($cards),
      ]
    );
    self::notify(
      $player,
      'pDiscardCards',
      $privateMsg ?? clienttranslate('You discard ${card_names}'),
      ($privateArgs ?? $args) + [
        'player' => $player,
        'cards' => $cards->toArray(),
      ]
    );
  }

  public static function discardCardsOnDisplay($player, $cards, $msg = null)
  {
    self::notifyAll('discardCardsOnDisplay', $msg ?? clienttranslate('${player_name} discards ${card_names} from display'), [
      'player' => $player,
      'cards' => $cards->toArray(),
    ]);
  }

  public static function discardPoolCardsWave($cards)
  {
    self::notifyAll('discardCardsOnDisplay', \clienttranslate('Removing ${n} cards of the display due to Wave icons reveal: ${card_names}'), [
      'cards' => $cards->toArray(),
      'n' => count($cards),
    ]);
  }

  public static function discardPoolCardsWaveBonus($player, $cards)
  {
    self::notifyAll('discardCardsOnDisplay', \clienttranslate('${player_name} removes ${n} cards of the display due to Wave bonus placement: ${card_names}'), [
      'player' => $player,
      'cards' => $cards->toArray(),
      'n' => count($cards),
    ]);
  }

  public static function discardPoolCardsBreak($cards)
  {
    self::notifyAll('discardCardsOnDisplay', \clienttranslate('Removing first two cards of the display: ${card_names}'), [
      'cards' => $cards->toArray(),
    ]);
  }

  public static function discardProject($card, $tokenIds)
  {
    self::notifyAll('discardCardsOnDisplay', clienttranslate('The rightmost project card is discarded: ${card_names}'), [
      'cards' => [$card],
      'tokenIds' => $tokenIds,
    ]);
  }

  public static function moveProjects($player, $card, $cards, $fromDisplay)
  {
    self::notifyAll(
      'moveProjects',
      $fromDisplay
        ? clienttranslate('${player_name} buys a new conservation project from display: ${card_names}')
        : clienttranslate('${player_name} plays a new conservation project: ${card_names}'),
      [
        'player' => $player,
        'cards' => [$card],
        'projects' => $cards->toArray(),
        'fromDisplay' => $fromDisplay,
      ]
    );
  }

  public static function discardScoringCard($player, $card)
  {
    self::discardCards(
      $player,
      new Collection([$card]),
      clienttranslate('You discard ${card_names} (scoring card)'),
      clienttranslate('${player_name} discards 1 scoring card'),
      ['scoringCard' => true]
    );
  }

  public static function buyAnimal($player, $animal, $cost, $enclosures, $fromDisplay)
  {
    $msg = is_null($enclosures)
      ? clienttranslate('${player_name} plays ${card_name} for ${amount_money} and places it using Flocking ability')
      : clienttranslate('${player_name} plays ${card_name} for ${amount_money} and places it in ${building_names}');

    if ($fromDisplay) {
      $msg = is_null($enclosures)
        ? clienttranslate(
          '${player_name} buys ${card_name} from display for ${amount_money} and places it using Flocking ability'
        )
        : clienttranslate('${player_name} buys ${card_name} from display for ${amount_money} and places it in ${building_names}');
    }

    self::notifyAll('buyAnimal', $msg, [
      'player' => $player,
      'card' => $animal,
      'amount' => $cost,
      'amount_money' => $cost,
      'total' => $player->getMoney(),
      'buildings' => $enclosures,
      'fromDisplay' => $fromDisplay,
    ]);
  }

  public static function releaseAnimal($player, $animal, $enclosures, $bonuses)
  {
    $data = [
      'player' => $player,
      'card' => $animal,
      'buildings' => $enclosures,
      'bonuses' => $bonuses,
      'release' => true,
      'amount' => $animal->getAppeal(),
      'score' => $player->updateScore(),
    ];
    $msg = clienttranslate(
      '${player_name} releases ${card_name} into the wild and loses ${bonuses_desc} and frees ${building_names}'
    );
    if ($bonuses[APPEAL] == 0) {
      unset($data['bonuses']);
      $msg = clienttranslate('${player_name} releases ${card_name} into the wild and frees ${building_names} (no appeal lost)');
    }
    self::notifyAll('releaseAnimal', $msg, $data);
  }

  public static function moveAnimal($player, $animal, $enclosure, $freeEnclosures)
  {
    self::notifyAll(
      'moveAnimal',
      clienttranslate('${player_name} moves ${card_name} into the ${building_name} and free ${building_names}'),
      [
        'player' => $player,
        'card' => $animal,
        'building' => $enclosure,
        'buildings' => $freeEnclosures,
      ]
    );
  }

  public static function playSponsor($player, $sponsor, $meeples, $fromDisplay)
  {
    self::notifyAll(
      'playSponsor',
      $fromDisplay
        ? clienttranslate('${player_name} buys ${card_name} from display')
        : clienttranslate('${player_name} plays ${card_name}'),
      [
        'player' => $player,
        'card' => $sponsor,
        'meeples' => $meeples,
        'fromDisplay' => $fromDisplay,
      ]
    );
  }

  ////////////////////////////////
  //   ____       _
  //  / ___| __ _(_)_ __  ___
  // | |  _ / _` | | '_ \/ __|
  // | |_| | (_| | | | | \__ \
  //  \____|\__,_|_|_| |_|___/
  ////////////////////////////////

  public static function gain($player, $args, $source = null)
  {
    self::getBonuses($player, $args, $source, []);
  }

  public static function getBonuses($player, $bonuses, null|string|ZooCard $source = null, $args = [], $msg = null)
  {
    $found = false;
    foreach ($bonuses as $type => $bonus) {
      if ($bonus > 0) {
        $found = true;
        break;
      }
    }
    if (!$found) {
      return;
    }

    if (is_null($msg)) {
      $msg = clienttranslate('${player_name} gains ${bonuses_desc}');
      if (!is_null($source)) {
        if ($source instanceof ZooCard) {
          $msg = clienttranslate('${player_name} gains ${bonuses_desc} (${card_name})');
          $args['card_id'] = $source->getId();
          $args['card_name'] = $source->getName();
          $args['i18n'][] = 'card_name';
          $args['preserve'][] = 'card_id';
        } else {
          $msg = clienttranslate('${player_name} gains ${bonuses_desc} (${source})');
          $args['source'] = $source;
          $args['i18n'][] = 'source';
        }
      }
    }

    $args['player'] = $player;
    $args['score'] = $player->updateScore();
    $args['bonuses'] = $bonuses;
    self::notifyAll('getBonuses', $msg, $args);
  }

  public static function incAppeal(Player $player, int $amount, int $total, null|string|ZooCard $source = null)
  {
    self::getBonuses($player, [APPEAL => $amount], $source);
  }

  public static function incReputation(Player $player, int $amount, int $total, null|string|ZooCard $source = null)
  {
    self::getBonuses($player, [REPUTATION => $amount], $source);
  }

  public static function incConservation(Player $player, int $amount, int $total, null|string|ZooCard $source = null)
  {
    self::getBonuses($player, [CONSERVATION => $amount], $source);
  }

  public static function incMoney(Player $player, int $amount, int $total, null|string|ZooCard $source = null)
  {
    self::getBonuses($player, [MONEY => $amount], $source);
  }

  public static function incXToken(Player $player, int $amount, int $total, null|string|ZooCard $source = null)
  {
    self::getBonuses($player, [XTOKEN => $amount], $source);
  }

  public static function payMoney(Player $player, int $amount, int $total, string|ZooCard $source)
  {
    self::notifyAll('getBonuses', clienttranslate('${player_name} pays ${bonuses_desc} for ${source}'), [
      'i18n' => ['source'],
      'player' => $player,
      'source' => $source,
      'bonuses' => [MONEY => -$amount],
    ]);
  }

  public static function payXToken($player, $amount, $total, $source)
  {
    self::notifyAll('getBonuses', clienttranslate('${player_name} pays ${bonuses_desc} for ${source}'), [
      'i18n' => ['source'],
      'player' => $player,
      'source' => $source,
      'bonuses' => [XTOKEN => -$amount],
    ]);
  }

  public static function searchCard($player, $cardToKeep, $type, $source = null)
  {
    if ($source == 'University') {
      $pMsg = clienttranslate('You draw ${card_names} for gaining a new university with <SEARCH-${type}>');
      $msg = clienttranslate('${player_name} draws 1 <${type}> card for gaining a new university: ${card_names}');
    } else if ($source == MONKEY_GANG) {
      $pMsg = clienttranslate('You draw ${card_names} for monkey gang effect');
      $msg = clienttranslate('${player_name} draws 1 <PRIMATE> card for monkey gang effect: ${card_names}');
    } else if (!is_null($source)) {
      $pMsg = clienttranslate('You draw ${card_names} with <${type}> (${source})');
      $msg = clienttranslate('${player_name} draws 1 <${type}> card: ${card_names} (${source})');
    } else {
      var_dump($source);
      die("UNSUPPORTED SEARCH CARD NOTIFICATION");
    }

    self::drawCards(
      $player,
      [$cardToKeep],
      $pMsg,
      $msg,
      [
        'cards' => [$cardToKeep],
        'type' => mb_strtoupper($type),
        'source' => $source,
        'i18n' => ['source']
      ]
    );
  }

  ///////////////////////////////////////
  //  _____  __  __           _
  // | ____|/ _|/ _| ___  ___| |_ ___
  // |  _| | |_| |_ / _ \/ __| __/ __|
  // | |___|  _|  _|  __/ (__| |_\__ \
  // |_____|_| |_|  \___|\___|\__|___/
  ///////////////////////////////////////

  public static function sprint($player, $cards)
  {
    self::drawCards(
      $player,
      $cards,
      clienttranslate('You draw ${card_names} for sprint effect'),
      clienttranslate('${player_name} draws ${n} card(s) for sprint effect')
    );
  }

  public static function preHunter($player, $cards)
  {
    self::drawCards(
      $player,
      $cards,
      clienttranslate('You draw ${card_names} for hunter effect'),
      clienttranslate('${player_name} draws ${n} card(s) for hunter effect'),
      ['cards' => $cards->toArray()]
    );
  }

  public static function hunter($player, $cardsToDiscard, $card)
  {
    $noDiscard = count($cardsToDiscard) == 0;
    self::discardCards(
      $player,
      $cardsToDiscard,
      $noDiscard
        ? clienttranslate('You keep ${card_name} for hunter effect')
        : clienttranslate('You keep ${card_name} and discard ${card_names} for hunter effect'),
      $noDiscard
        ? clienttranslate('${player_name} keeps ${card_name} card for hunter effect')
        : clienttranslate('${player_name} keeps ${card_name} card and discard ${n} card(s) for hunter effect'),
      ['card' => $card],
      ['card' => $card]
    );
  }

  public static function failHunter($player, $cardsToDiscard)
  {
    self::discardCards(
      $player,
      $cardsToDiscard,
      clienttranslate('You discard ${card_names} (no animal)'),
      clienttranslate('${player_name} discards all ${n} card(s) of hunter effect (no animals)')
    );
  }

  public static function prePerception($player, $cards, $draw, $keep)
  {
    self::drawCards(
      $player,
      $cards,
      clienttranslate('You draw ${card_names} for perception effect'),
      clienttranslate('${player_name} draws ${n} card(s) for perception effect')
    );
  }

  public static function perception($player, $cardsToDiscard, $cardsToKeep)
  {
    self::notifyAll(
      'discardCards',
      clienttranslate('${player_name} keeps ${m} card(s) and discard ${n} card(s) for perception effect'),
      [
        'player' => $player,
        'n' => count($cardsToDiscard),
        'm' => count($cardsToKeep),
      ]
    );
    self::notify($player, 'pDiscardCards', clienttranslate('You keep ${card_names2} and discard ${card_names}'), [
      'player' => $player,
      'cards' => $cardsToDiscard->toArray(),
      'cards2' => $cardsToKeep->toArray(),
    ]);
  }

  public static function sunbathing($player, $cardsToSell, $money)
  {
    self::discardCards(
      $player,
      $cardsToSell,
      clienttranslate('You sell ${card_names} cards for ${bonuses_desc}'),
      clienttranslate('${player_name} sells ${n} card(s) for ${bonuses_desc}'),
      [
        'bonuses' => [MONEY => $money],
      ]
    );
  }

  public static function pouch($player, $cardsToPouch, $appeal, $source)
  {
    self::discardCards(
      $player,
      $cardsToPouch,
      clienttranslate('You pouch ${card_names} cards for ${bonuses_desc}'),
      clienttranslate('${player_name} pouches ${n} card(s) for ${bonuses_desc}'),
      [
        'bonuses' => [APPEAL => $appeal],
        'pouch' => $source,
        'score' => $player->updateScore()
      ]
    );
  }

  public static function digging($player, $type, $cardToDiscard, $card)
  {
    if ($type == 'hand') {
      self::discardCards(
        $player,
        $cardToDiscard,
        \clienttranslate('You dig ${card_names} from your hand'),
        \clienttranslate('${player_name} digs 1 card from their hand')
      );
    } else {
      self::discardCardsOnDisplay($player, $cardToDiscard, clienttranslate('${player_name} digs ${card_names} from the display'));
    }

    if (!is_null($card)) {
      self::drawCards($player, $card);
    }
  }

  public static function diggingMap10($player, $type, $card, $newCards)
  {
    self::notifyAll('buyAnimal', clienttranslate('${player_name} tucks ${card_name} in the rescue station (Map10\'s effect)'), [
      'player' => $player,
      'card' => $card,
      'amount' => 0,
      'total' => $player->getMoney(),
      'building' => null,
      'fromDisplay' => $type == 'display',
    ]);

    if (!is_null($newCards)) {
      self::drawCards($player, $newCards);
    }
  }

  public static function preScavenging($player, $cards)
  {
    self::drawCards(
      $player,
      $cards,
      clienttranslate('You draw ${card_names} from discard for scavenging effect'),
      clienttranslate('${player_name} draws ${n} card(s) from discard for scavenging effect')
    );
  }

  public static function scavenging($player, $cardsToDiscard, $card)
  {
    self::discardCards(
      $player,
      $cardsToDiscard,
      clienttranslate('You keep ${card_name} and discard ${card_names} for scavenging effect'),
      clienttranslate('${player_name} keeps 1 card and discards ${n} card(s) for scavenging effect'),
      [],
      ['card' => $card]
    );
  }

  public static function clever($player, $actionCard, $position, $cards)
  {
    self::actionCardCleanup(
      $player,
      $actionCard,
      $position,
      $cards,
      clienttranslate('${player_name} places ${action_card_name}${action_card_icon} at position ${position} (Clever effect)')
    );
  }

  public static function preResistance($player, $cards)
  {
    self::drawCards(
      $player,
      $cards,
      clienttranslate('You draw ${card_names} for resistance effect'),
      clienttranslate('${player_name} draws ${n} scoring cards for resistance effect'),
      ['scoringCard' => true]
    );
  }

  public static function resistance($player, $cardsToDiscard, $card)
  {
    self::discardCards(
      $player,
      $cardsToDiscard,
      clienttranslate('You keep ${card_name} and discard ${card_names} for resistance effect'),
      clienttranslate('${player_name} keeps 1 scoring card and discard ${n} scoring card for resistance effect'),
      ['scoringCard' => true],
      ['scoringCard' => true, 'card' => $card]
    );
  }

  public static function assertion($player, $card)
  {
    self::drawCards(
      $player,
      [$card],
      clienttranslate('You keep ${card_names} with Assertion'),
      clienttranslate('${player_name} keeps 1 card with Assertion')
    );
  }

  public static function dominance($player, $card)
  {
    $cards = [$card];
    self::drawCards(
      $player,
      $cards,
      clienttranslate('You get ${card_names} with Dominance'),
      clienttranslate('${player_name} adds ${card_names} conservation project to their hand with Dominance'),
      ['cards' => $cards]
    );
  }

  public static function boost($player, $actionCard, $position, $cards)
  {
    self::actionCardCleanup(
      $player,
      $actionCard,
      $position,
      $cards,
      clienttranslate('${player_name} places ${action_card_name}${action_card_icon} at position ${position} (Boost effect)')
    );
  }

  public static function constriction($player, $meeples, $players)
  {
    self::notifyAll(
      'addMeeples',
      clienttranslate('${player_name} uses Constriction effect and gives constriction token(s) to ${players_names}'),
      ['player' => $player, 'meeples' => $meeples, 'players' => $players]
    );
  }

  public static function venom($player, $meeples, $players)
  {
    self::notifyAll(
      'addMeeples',
      clienttranslate('${player_name} uses Venom effect and gives Venom token(s) to ${players_names}'),
      [
        'player' => $player,
        'meeples' => $meeples,
        'players' => $players,
      ]
    );
  }

  public static function multiplier($player, $actionCard, $meeple)
  {
    self::notifyAll(
      'addMeeples',
      clienttranslate(
        '${player_name} adds a multiplier token on action card ${action_card_name}${action_card_icon}${action_card_level}'
      ),
      [
        'player' => $player,
        'meeples' => [$meeple],
        'actionCard' => $actionCard,
      ]
    );
  }

  public static function enableMultiplier($meeples)
  {
    self::notifyAll('enableMultiplier', '', ['meepleIds' => $meeples->getIds()]);
  }

  public static function useMultiplier($player, $meeple)
  {
    self::notifyAll('discardTokens', clienttranslate('${player_name} uses a multiplier token'), [
      'player' => $player,
      'meeples' => [$meeple],
    ]);
  }

  public static function discardToken($player, $type, $meeple, $silent = false)
  {
    $typeMap = [
      VENOM => clienttranslate('Venom'),
      CONSTRICTION => clienttranslate('Constriction')
    ];
    self::notifyAll('discardTokens', $silent ? '' : clienttranslate('${player_name} discards their ${type} token'), [
      'player' => $player,
      'meeples' => [$meeple],
      'type' => $typeMap[$type] ?? "",
      'i18n' => [$type],
    ]);
  }

  public static function useReductionTokens($player, $tokensUsed)
  {
    $n = count($tokensUsed);
    $msg = clienttranslate('${player_name} uses ${n} token(s) from sponsor card(s)');
    $data = [
      'player' => $player,
      'n' => $n,
      'meeples' => $tokensUsed,
    ];

    // MW GRAY BONUS
    $types = array_map(fn($m) => $m['type'], $tokensUsed);
    if (in_array(BONUS_ICON_SUPPORT_PROJECT, $types)) {
      $n--;
      $data['n'] = $n;
      $msg = $n == 0 ? clienttranslate('${player_name} uses ${bonus_desc}') : clienttranslate('${player_name} uses ${n} token(s) from sponsor card(s) and ${bonus_desc}');
      $data['i18n'] = ['bonus_desc'];
      $data['bonus_desc'] = [
        'log' => '${bonus_raw_desc}${bonus_pentagon}',
        'args' => [
          'bonus_source_type' => 'bonus',
          'bonus_pentagon' => '',
          'bonus_type' => BONUS_ICON_SUPPORT_PROJECT,
          'bonus_n' => 1,
          'bonus_raw_desc' => 1 . ' x ' . BONUS_ICON_SUPPORT_PROJECT,
        ]
      ];
    }

    self::notifyAll('discardTokens', $msg, $data);
  }

  public static function hypnosis($player, $target)
  {
    self::notifyAll('hypnosis', clienttranslate('${player_name} chooses to hypnotize ${player_name2}'), [
      'player' => $player,
      'player2' => $target,
    ]);
  }

  public static function pilfering($player, $target1, $target2)
  {
    $msg = clienttranslate('${player_name} chooses ${player_name2} to Pilfer');
    if (!is_null($target1) && !is_null($target2)) {
      $msg = clienttranslate('${player_name} chooses ${player_name2} and ${player_name3} to Pilfer');
    }

    self::notifyAll('pilfering', $msg, [
      'player' => $player,
      'player2' => $target1 ?? $target2,
      'player3' => $target2,
    ]);
  }

  public static function pilferingCard($player, $card, $otherPlayer)
  {
    $cards = [$card];
    // Public notif : slide card
    self::notifyAll('pilferingCard', clienttranslate('${player_name} gives 1 card to ${player_name2} from Pilfering effect'), [
      'player' => $player,
      'player2' => $otherPlayer,
    ]);

    self::notify($player, 'pDiscardCards', clienttranslate('You give ${card_names} from the Pilfering effect'), [
      'player' => $player,
      'pilfering' => $otherPlayer->getId(),
      'cards' => $cards,
    ]);
    self::notify($otherPlayer, 'pDrawCards', clienttranslate('You get ${card_names} from the Pilfering effect'), [
      'player' => $otherPlayer,
      'pilfering' => $player->getId(),
      'cards' => $cards,
    ]);
  }

  public static function pilferingMoney($player, $amount, $otherPlayer)
  {
    self::notifyAll(
      'pilferingMoney',
      clienttranslate('${player_name} gives ${bonuses_desc} to ${player_name2} for Pilfering effect'),
      [
        'player' => $player,
        'player2' => $otherPlayer,
        'bonuses' => [MONEY => $amount],
      ]
    );
  }

  // FULL-THROATED
  public static function gainWorker($player, $worker)
  {
    self::notifyAll('slideMeeples', clienttranslate('${player_name} gains a new Association worker'), [
      'player' => $player,
      'meeples' => [$worker],
    ]);
  }

  public static function map8Effect($player, $cards)
  {
    self::drawCards(
      $player,
      $cards,
      clienttranslate('You draw ${card_names} from the deck (Map 8 effect)'),
      clienttranslate('${player_name} draws ${card_names} sponsor card(s) from the deck (Map 8 effect)'),
      ['cards' => $cards->toArray()]
    );
  }

  public static function wazaSpecial($player, $type, $card)
  {
    self::notifyAll(
      'wazaSpecial',
      $type == 'small'
        ? \clienttranslate('${player_name} focuses on small animals and won\'t be able to play large animal from now on')
        : \clienttranslate('${player_name} focuses on large animals and won\'t be able to play small animal from now on'),
      [
        'player' => $player,
        'type' => $type,
      ]
    );

    $cards = [$card];
    self::drawCards(
      $player,
      $cards,
      clienttranslate('You draw ${card_names} from the deck (Waza Special Assignement effect)'),
      clienttranslate('${player_name} draws ${card_names} animal card from the deck (Waza Special Assignement effect)'),
      ['cards' => $cards]
    );
  }

  public static function mark($player, $cards, $meeples)
  {
    self::notifyAll(
      'markCard',
      clienttranslate('${player_name} marks ${card_names} from display'),
      [
        'player' => $player,
        'cards' => $cards,
        'meeples' => $meeples,
      ]
    );
  }

  public static function gainMarked($player, $token, $source)
  {
    self::notifyAll('gainMarked', clienttranslate('${player_name} gains ${bonuses_desc} for their mark on ${card_name}'), [
      'player' => $player,
      'card_id' => $source->getId(),
      'card_name' => $source->getName(),
      'i18n' => ['card_name'],
      'preserve' => ['card_id'],
      'bonuses' => [MONEY => 2],
      'token' => $token,
    ]);
  }

  public static function removeMark($token)
  {
    self::notifyAll('discardTokens', clienttranslate('Removing the mark on the card'), [
      'meeples' => [$token]
    ]);
  }

  public static function markAssign($assignedCards, $meeplesToDelete)
  {
    if ($assignedCards->empty()) {
      return;
    }

    $cardsByPlayer = [];
    foreach ($assignedCards as $card) {
      $cardsByPlayer[$card->getPId()][] = $card;
    }
    $meeplesByPlayer = [];
    foreach ($meeplesToDelete as $meeple) {
      $meeplesByPlayer[$meeple['pId']][] = $meeple;
    }

    foreach ($cardsByPlayer as $pId => $cards) {
      $player = Players::get($pId);
      self::notifyAll(
        'markAssign',
        count($cards) == 1 ?
          clienttranslate('${card_names} is not discarded and given to ${player_name} (Mark effect)') :
          clienttranslate('${card_names} are not discarded and given to ${player_name} (Mark effect)'),
        [
          'player' => $player,
          'cards' => $cards,
          'meeples' => $meeplesByPlayer[$pId],
        ]
      );
    }
  }

  public static function trade($player, $trade, $msg, $xtoken, $money, $reputation)
  {
    self::notifyAll(
      'getBonuses',
      $msg,
      [
        'player' => $player,
        'bonuses' => $trade,
        'money' => $money,
        'xtoken' => $xtoken,
        'reputation' => $reputation,
      ]
    );
  }

  public static function extraShift($player, $workers, $slot)
  {
    $zones = [
      2 => clienttranslate('the reputation zone'),
      3 => clienttranslate('the partner zoo zone'),
      4 => clienttranslate('the university zone'),
      5 => clienttranslate('the conservation project zone'),
    ];

    self::notifyAll('slideMeeples', clienttranslate('${player_name} takes ${n} worker(s) back from ${source} to their notepad (Extra Shift effect)'), [
      'player' => $player,
      'meeples' => $workers->toArray(),
      'n' => $workers->count(),
      'source' => $zones[$slot],
      'i18n' => ['source'],
    ]);
  }

  public static function cutDown($player, $buildingId, $size, $money)
  {
    self::notifyAll('cutDown', clienttranslate('${player_name} removes a size-${size} empty standard enclosure and gains ${bonuses_desc} (Cut Down effect)'), [
      'player' => $player,
      'size' => $size,
      'bonuses' => [MONEY => $money],
      'buildingId' => $buildingId,
    ]);
  }

  public static function preScubaDive($player, $cards)
  {
    self::drawCards(
      $player,
      $cards,
      clienttranslate('You draw ${card_names} for scuba dive effect'),
      clienttranslate('${player_name} draws ${n} cards for scuba dive effect'),
    );
  }

  public static function scubaDive($player, $cardsToDiscard, $card)
  {
    $noDiscard = count($cardsToDiscard) == 0;
    self::discardCards(
      $player,
      $cardsToDiscard,
      $noDiscard
        ? clienttranslate('You keep ${card_name} for scuba dive effect')
        : clienttranslate('You keep ${card_name} and discard ${card_names} for scuba dive effect'),
      $noDiscard
        ? clienttranslate('${player_name} keeps ${card_name} card for scuba dive effect')
        : clienttranslate('${player_name} keeps ${card_name} card and discard ${n} card(s) for scuba dive effect'),
      ['card' => $card],
      ['card' => $card]
    );
  }

  public static function failScubaDive($player, $cardsToDiscard)
  {
    self::discardCards(
      $player,
      $cardsToDiscard,
      clienttranslate('You discard ${card_names} (no sponsor)'),
      clienttranslate('${player_name} discards all ${n} card(s) of hunter effect (no sponsor)')
    );
  }

  public static function preAdapt($player, $cards)
  {
    self::drawCards(
      $player,
      $cards,
      clienttranslate('You draw ${card_names} for adapt effect'),
      clienttranslate('${player_name} draws ${n} scoring card(s) for adapt effect'),
      ['scoringCard' => true]
    );
  }

  public static function adapt($player, $cardsToDiscard)
  {
    self::discardCards(
      $player,
      $cardsToDiscard,
      clienttranslate('You discard ${card_names} for adapt effect'),
      clienttranslate('${player_name} discards ${n} scoring card(s) for adapt effect'),
      ['scoringCard' => true],
      ['scoringCard' => true]
    );
  }


  public static function expedition($player, $card)
  {
    self::notifyAll('discardCardsOnDisplay', clienttranslate('${player_name} sends ${card_names} away to an expedition'), [
      'player' => $player,
      'cards' => [$card],
    ]);
  }

  public static function searchPetDiscard($player, $card)
  {
    self::drawCards(
      $player,
      [$card],
      clienttranslate('You draw ${card_names} from discard for Horse Whisperer effect'),
      clienttranslate('${player_name} draws ${n} card from discard for Horse Whisperer effect')
    );
  }

  public static function reconstructionRemove($player, $buildings)
  {
    self::notifyAll('reconstructionRemove', clienttranslate('${player_name} removes ${building_names} (Reconstruction)'), [
      'player' => $player,
      'buildings' => $buildings->toArray(),
    ]);
  }

  public static function placeBackBuilding($player, $building)
  {
    self::notifyAll('reconstructionPlaceBack', clienttranslate('${player_name} places back ${building_name} (Reconstruction)'), [
      'player' => $player,
      'building' => $building,
    ]);
  }

  public static function increaseSizeRemove($player, $building)
  {
    self::message(clienttranslate('${player_name} chooses ${building_names} for Expert on Australia\'s effect'), [
      'player' => $player,
      'buildings' => [$building],
    ]);
  }

  // public static function placeBackBuilding($player, $building)
  // {
  //   self::notifyAll('reconstructionPlaceBack', clienttranslate('${player_name} places back ${building_name} (Reconstruction)'), [
  //     'player' => $player,
  //     'building' => $building,
  //   ]);
  // }


  // MAP PACK 2
  public static function storeCard($player, $card)
  {
    self::notifyAll(
      'storeCard',
      clienttranslate('${player_name} stores 1 card from their hand (Map 11 Effect)'),
      ['player' => $player]
    );
    self::notify(
      $player,
      'pStoreCard',
      clienttranslate('You store ${card_names} from your hand (Map 11 Effect)'),
      [
        'player' => $player,
        'cards' => [$card],
        'infos' => ['income' => [$player->getId() => $player->getMoneyIncome()]],
      ]
    );
  }
  public static function unstoreCard($player, $card)
  {
    self::notifyAll(
      'unstoreCard',
      clienttranslate('${player_name} takes 1 stored card back into their hand (Map 11 Effect)'),
      ['player' => $player]
    );
    self::notify(
      $player,
      'pUnstoreCard',
      clienttranslate('You takes ${card_names} back into your hand (Map 11 Effect)'),
      [
        'player' => $player,
        'cards' => [$card],
      ]
    );
  }


  ///////////////////////////////////////////////////////////////
  //  _   _           _       _            _
  // | | | |_ __   __| | __ _| |_ ___     / \   _ __ __ _ ___
  // | | | | '_ \ / _` |/ _` | __/ _ \   / _ \ | '__/ _` / __|
  // | |_| | |_) | (_| | (_| | ||  __/  / ___ \| | | (_| \__ \
  //  \___/| .__/ \__,_|\__,_|\__\___| /_/   \_\_|  \__, |___/
  //       |_|                                      |___/
  ///////////////////////////////////////////////////////////////

  protected static function getBuildingName($building)
  {
    $names = [
      'pavilion' => clienttranslate('a pavilion'),
      'kiosk' => clienttranslate('a Kiosk'),
      LARGE_BIRD_AVIARY => clienttranslate('the Large Bird Aviary'),
      PETTING_ZOO => clienttranslate('the Petting Zoo'),
      REPTILE_HOUSE => clienttranslate('the Reptile House'),
      SMALL_AQUARIUM => clienttranslate('the small aquarium'),
      LARGE_AQUARIUM => clienttranslate('the large aquarium'),
      UNDERWATER_TUNNEL => clienttranslate('the underwater tunnel'),
      'empty' => clienttranslate('no enclosure'),
    ];
    $name = $names[$building['type']] ?? [
      'log' => clienttranslate('a size-${n} enclosure'),
      'args' => ['n' => count(\BUILDINGS[$building['type']])],
    ];
    if (in_array($building['type'], \UNIQUE_BUILDINGS)) {
      $name = \clienttranslate('a unique building');
    }

    return $name;
  }

  /*
   * Automatically adds some standard field about player and/or card
   */
  protected static function updateArgs(&$data)
  {
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
    if (isset($data['player3'])) {
      $data['player_name3'] = $data['player3']->getName();
      $data['player_id3'] = $data['player3']->getId();
      unset($data['player3']);
    }
    if (isset($data['players'])) {
      $args = [];
      $logs = [];
      foreach ($data['players'] as $i => $player) {
        $logs[] = '${player_name' . $i . '}';
        $args['player_name' . $i] = $player->getName();
      }
      $data['players_names'] = [
        'log' => join(', ', $logs),
        'args' => $args,
      ];
      $data['i18n'][] = 'players_names';
      unset($data['players']);
    }

    if (isset($data['actionCard'])) {
      $lvlMapping = [
        1 => 'I',
        2 => 'II',
      ];
      $card = $data['actionCard'];
      $data['i18n'][] = 'action_card_name';
      $data['action_card_name'] = $card->getName();
      $data['action_card_level'] = $lvlMapping[$card->getLevel()];
      $data['action_card_icon'] = '';
      $data['action_card_type'] = $card->getActionType();
      $data['preserve'][] = 'action_card_type';
    }

    if (isset($data['actionCards'])) {
      $data['actionCards'] = $data['actionCards']->map(function ($card) {
        return $card->getStrength();
      });
    }

    // Useful for frontend formating
    if (isset($data['strength'])) {
      $data['strength_icon'] = '';
    }

    if (isset($data['building'])) {
      $building = $data['building'];
      $name = self::getBuildingName($building);
      $data['i18n'][] = 'building_name';
      $data['building_name'] = $name;
    }

    if (isset($data['building2'])) {
      $building = $data['building2'];
      $name = self::getBuildingName($building);
      $data['i18n'][] = 'building_name2';
      $data['building_name2'] = $name;
    }

    if (isset($data['buildings'])) {
      $args = [];
      $logs = [];
      foreach ($data['buildings'] as $i => $building) {
        $logs[] = '${building_name_' . $i . '}';
        $args['i18n'][] = 'building_name_' . $i;
        $args['building_name_' . $i] = self::getBuildingName($building);
      }
      $data['building_names'] = [
        'log' => join(', ', $logs),
        'args' => $args,
      ];
      $data['i18n'][] = 'building_names';
    }

    if (isset($data['resources'])) {
      // Get an associative array $resource => $amount
      $resources = Utils::reduceResources($data['resources']);
      $data['resources_desc'] = Utils::resourcesToStr($resources);
    }

    if (isset($data['resources2'])) {
      // Get an associative array $resource => $amount
      $resources2 = Utils::reduceResources($data['resources2']);
      $data['resources2_desc'] = Utils::resourcesToStr($resources2);
    }

    if (isset($data['card'])) {
      $data['card_id'] = $data['card']->getId();
      $data['card_name'] = $data['card']->getName();
      $data['i18n'][] = 'card_name';
      $data['preserve'][] = 'card_id';
    }

    if (isset($data['cards'])) {
      $args = [];
      $logs = [];
      foreach ($data['cards'] as $i => $card) {
        $logs[] = '${card_name_' . $i . '}';
        $args['i18n'][] = 'card_name_' . $i;
        $args['card_name_' . $i] = [
          'log' => '${card_name}',
          'args' => [
            'i18n' => ['card_name'],
            'card_name' => is_array($card) ? $card['name'] : $card->getName(),
            'card_id' => is_array($card) ? $card['id'] : $card->getId(),
            'preserve' => ['card_id'],
          ],
        ];
      }
      $data['card_names'] = [
        'log' => join(', ', $logs),
        'args' => $args,
      ];
      $data['i18n'][] = 'card_names';
    }

    if (isset($data['cards2'])) {
      $args = [];
      $logs = [];
      foreach ($data['cards2'] as $i => $card) {
        $logs[] = '${card_name_' . $i . '}';
        $args['i18n'][] = 'card_name_' . $i;
        $args['card_name_' . $i] = [
          'log' => '${card_name}',
          'args' => [
            'i18n' => ['card_name'],
            'card_name' => is_array($card) ? $card['name'] : $card->getName(),
            'card_id' => is_array($card) ? $card['id'] : $card->getId(),
            'preserve' => ['card_id'],
          ],
        ];
      }
      $data['card_names2'] = [
        'log' => join(', ', $logs),
        'args' => $args,
      ];
      $data['i18n'][] = 'card_names2';
      $data['cards2'] = $data['cards2'];
    }

    foreach (['bonuses', 'bonuses2'] as $key) {
      if (isset($data[$key]) && !empty($data[$key])) {
        $bonusesNames = [
          'money' => clienttranslate('money'),
          'appeal' => clienttranslate('appeal'),
          'reputation' => clienttranslate('reputation'),
          'conservation' => clienttranslate('conservation'),
          'xtoken' => \clienttranslate('xtoken'),
        ];

        $args = [];
        $i = 0;
        foreach ($data[$key] as $type => $bonus) {
          if ($bonus == 0) {
            continue;
          }
          $args['i18n'][] = 'bonus_' . $i;
          $args['bonus_' . $i] = [
            'log' => '${bonus}${bonus_icon} ${bonus_name}',
            'args' => [
              'i18n' => ['bonus_name'],
              'bonus_name' => $bonusesNames[$type],
              'bonus_icon' => '',
              'bonus' => $bonus > 0 ? $bonus : -$bonus,
            ],
          ];
          $i++;
        }
        $logs = [
          0 => '',
          1 => '${bonus_0}',
          2 => clienttranslate('${bonus_0} and ${bonus_1}'),
          3 => clienttranslate('${bonus_0}, ${bonus_1} and ${bonus_2}'),
        ];
        $data[$key . '_desc'] = [
          'log' => $logs[$i],
          'args' => $args,
        ];
        $data['i18n'][] = $key . '_desc';
      }
    }


    // SERIALIZE STUFF
    foreach ($data as $key => $v) {
      if (is_object($v)) {
        $data[$key] = $v->jsonSerialize();
      } else if (is_array($v)) {
        foreach ($v as $key2 => $v2) {
          if (is_object($v2)) {
            $data[$key][$key2] = $v2->jsonSerialize();
          }
        }
      }
    }
  }
}
