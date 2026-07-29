<?php
namespace AGR\Actions;

use AGR\Core\Engine;
use AGR\Core\Globals;
use AGR\Core\Notifications;
use AGR\Managers\Meeples;
use AGR\Managers\PlayerCards;
use AGR\Managers\Players;

class Reorganize extends \AGR\Models\Action
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->description = clienttranslate('Reorganize animals');
  }

  public function getState()
  {
    return ST_REORGANIZE;
  }

  public function isDoable($player, $ignoreResources = false)
  {
    return $player->getAnimals()->count() >= 0;
  }

  public function argsReorganize()
  {
    $args = $this->ctx->getArgs();
    $trigger = $args['trigger'] ?? ANYTIME;

    $player = Players::getActive();
    $breedTypes = $args['breedTypes'] ?? $player->breedTypes();
    return [
      'exchanges' => $player->getExchangeableAnimalTypes($trigger),
      'animals' => $player->getAnimals()->toArray(),
      'zones' => $player->board()->getAnimalsDropZones(),
      'harvest' => $trigger == HARVEST,
      'breedTypes' => $breedTypes,
      'dollysMother' => $player->hasPlayedCard('E84_DollysMother'),
      'canGoToReorganize' => false,
      'canGoToExchange' => false,
    ];
  }

  public function actReorganize($meeplesOnBoard, $meeplesInReserve, $meeplesOnCard)
  {
    $player = Players::getActive();
    $args = $this->ctx->getArgs();
    $trigger = $args['trigger'] ?? ANYTIME;
    $breed = $args['breedTypes'] ?? $player->breedTypes();

    $ids = [];
    foreach ($meeplesOnBoard as $meeple) {
      Meeples::moveToCoords($meeple['id'], 'board', $meeple);
      $ids[] = $meeple['id'];
    }

    foreach ($meeplesInReserve as $meeple) {
      Meeples::moveToCoords($meeple['id'], 'reserve');
      $ids[] = $meeple['id'];
    }

    foreach ($meeplesOnCard as $meeple) {
      Meeples::moveToCoords($meeple['id'], $meeple['card_id']);
      $ids[] = $meeple['id'];
    }

    // Checks all zones and animals
    $player->board()->areAnimalsValid();

    // For each newborn still in reserve at submit time, decide whether the breed "really happened".
    // A newborn is silent-killed (no food gained, doesn't count toward Dung Collector etc.) if EITHER
    // the player has moved too many parents to reserve (parent shortage) OR the species can't fit
    // the baby anywhere on the farm given how the player has arranged things (no-room).
    if ($trigger == HARVEST) {
      $reserveAnimals = $player->countAnimalsInReserve();
      $boardAnimals = $player->countAnimalsOnBoard();
      $pId = $player->getId();
      Globals::setNumSilentKills(0);

      $kills = []; // [['species' => ..., 'reason' => 'noParents' | 'noRoom'], ...]
      $needsFreeSlot = [];
      foreach ($breed as $animal => $born) {
        if (!isset($animal) || !$born) {
          continue;
        }
        if (($reserveAnimals[$animal] ?? 0) <= 0) {
          continue;
        }
        // numNeededParents = how many parents must remain on board for the breed to count.
        // Normally 2; Dolly's Mother lets sheep breed from a single parent.
        $numNeededParents = ($animal == SHEEP && $player->hasPlayedCard('E84_DollysMother')) ? 1 : 2;
        $minCount = $numNeededParents + 1;

        // Parent shortage: player evicted parents below the breed threshold.
        if (($boardAnimals[$animal] ?? 0) < $numNeededParents) {
          $kills[] = ['species' => $animal, 'reason' => 'noParents'];
          continue;
        }
        // Already housed at minCount (parents + baby's worth): baby is conceptually "covered".
        if (($boardAnimals[$animal] ?? 0) >= $minCount) {
          continue;
        }
        // The baby needs a slot to survive — does any zone accept this species right now?
        // If multiple species fall through here, they'll compete for the same free slots below.
        if ($player->board()->canAccommodateAll([$animal])) {
          $needsFreeSlot[] = $animal;
        } else {
          $kills[] = ['species' => $animal, 'reason' => 'noRoom'];
        }
      }

      // Each species in $needsFreeSlot can fit a baby alone; check if they all fit together.
      if (count($needsFreeSlot) > 0) {
        $fits = $player->board()->countAccommodatable(array_values($needsFreeSlot));
        $shortage = count($needsFreeSlot) - $fits;
        // Kill from end of list: with iteration order [SHEEP, PIG, CATTLE], cattle loses
        // first when multiple species compete. Player can override by manually placing the baby.
        for ($i = 0; $i < $shortage; $i++) {
          $kills[] = ['species' => array_pop($needsFreeSlot), 'reason' => 'noRoom'];
        }
      }

      $silentKill = [];
      foreach ($kills as $kill) {
        $killed = Meeples::useReserveResource($pId, $kill['species'], 1);
        Notifications::discardNewborn($player, $killed, $kill['reason']);
        array_push($silentKill, ...$killed);
      }
      if (count($silentKill) != 0) {
        Globals::setNumSilentKills(count($silentKill));
        $killedIds = array_map(function ($m) { return $m['id']; }, $silentKill);
        $ids = array_values(array_diff($ids, $killedIds));
      }
    }

    Notifications::reorganize($player, Meeples::getMany($ids));

    // Still animals in reserve => go to exchange state if canCook
    if (count($meeplesInReserve) > 0) {
      $canCookSomeAnimals = false;
      $exchanges = $player->getExchangeableAnimalTypes();
      $animals = $player->getAllReserveResources();
      foreach ([SHEEP, PIG, CATTLE] as $type) {
        if ($animals[$type] == 0) {
          continue;
        }

        // Can we exchange this type of animals ?
        if ($exchanges[$type]) {
          $canCookSomeAnimals = true;
        } else {
          // We discard the remaining animals as they cannot be cooked
          Notifications::discardAnimals($player, $player->useResource($type, $animals[$type]));
        }
      }

      if ($canCookSomeAnimals) {
        Engine::insertAsChild([
          'action' => EXCHANGE,
          'args' => [
            'mustCook' => true,
          ],
        ]);
      }
    }
    $this->checkAfterListeners($player, [
      'trigger' => $trigger,
    ]);
    $this->resolveAction();
  }

  public static function checkAutoReorganize($player, &$meeples)
  {
    // Are there any animals to check ?
    $atLeastOneAnimal = array_reduce(
      $meeples,
      function ($carry, $meeple) {
        return $carry || in_array($meeple['type'], ANIMALS);
      },
      false
    );
    if (!$atLeastOneAnimal || $player->getPref(OPTION_SMART_REORGANIZE) == OPTION_SMART_REORGANIZE_OFF) {
      return false;
    }

    // Always give player the option to decline both A17 and B34.
    // Also reorganize for D164
    if ($player->hasPlayedCard('A17_ReclamationPlow') && !PlayerCards::get('A17_ReclamationPlow')->isFlagged() ||
      $player->hasPlayedCard('B34_SpecialFood') && !PlayerCards::get('B34_SpecialFood')->isFlagged() ||
      $player->hasPlayedCard('D164_PetGrower') ||
      $player->hasPlayedCard('E36_HerbalGarden') ||
      $player->hasPlayedCard('E33_BeaverColony'))
    {
      return true;
    }

    // Try to find them a nice place
    $zones = $player->board()->getAnimalsDropZonesWithAnimals();

    // Sort pastures first, then stables, then rooms
    usort($zones, function ($a, $b) {
      $map = [
        'A11_special' => 5,
        'D148_special' => 4,
        'pasture' => 3,
        'stable' => 2,
        'card' => 1,
        'room' => 0,
      ];
      return $map[$b['type']] - $map[$a['type']];
    });

    foreach ($meeples as &$meeple) {
      if (!in_array($meeple['type'], ANIMALS) || $meeple['location'] == 'board') {
        continue;
      }

      if ($meeple['type'] == PIG && ($meeple['state'] ?? null) == MUD_WALLOWER_PIG) {
        foreach ($zones as &$zone) {
          if (
            $zone['type'] == 'card' &&
            ($zone['card_id'] ?? null) == 'C148_MudWallower' &&
            $zone['animals'] < $zone['capacity'] &&
            in_array(PIG, $zone['constraints'] ?? ANIMALS)
          ) {
            self::placeInZone($player, $meeple, $zone);
            continue 2;
          }
        }
      }

      // Search zone of same animal type with an empty slot
      $sameFound = false;
      foreach ($zones as &$zone) {
        if ($zone['type'] == 'card' && method_exists(PlayerCards::get($zone['card_id']), 'canAcceptAnimal')) {
          if (PlayerCards::get($zone['card_id'])->canAcceptAnimal($zone, $meeple)) {
            self::placeInZone($player, $meeple, $zone);
            $sameFound = true;
            break;
          }
        } elseif ($zone['animals'] < $zone['capacity'] && $zone[$meeple['type']] > 0) {
          self::placeInZone($player, $meeple, $zone);
          $sameFound = true;
          break;
        }
      }

      // Now search any zone with a free spot
      if (!$sameFound) {
        foreach ($zones as &$zone) {
          if ($zone['animals'] == 0 && in_array($meeple['type'], $zone['constraints'] ?? ANIMALS)) {
            self::placeInZone($player, $meeple, $zone);
            break;
          }
        }
      }
    }

    return $player->getPref(OPTION_SMART_REORGANIZE) == OPTION_SMART_REORGANIZE_CONFIRM;
  }

  protected static function placeInZone($player, &$meeple, &$zone)
  {
    // First find a spot
    $c = null;
    $loc = null;
    foreach ($zone['locations'] as $location) {
      if ($zone['type'] == 'card') {
        $n = Meeples::countAnimalsInZoneCard($player->getId(), $location);
      } else {
        $n = Meeples::countAnimalsInZoneLocation($player->getId(), $location);
      }
      if ($c == null) {
        $c = $n;
      }

      if ($n < $c) {
        $loc = $location;
        break;
      }
    }
    if ($loc == null) {
      $loc = $zone['locations'][0];
    }

    // Place meeple
    if ($zone['type'] == 'card') {
      Meeples::moveToCoords($meeple['id'], $loc['card_id'], null);
      $meeple['location'] = $loc['card_id'];
    } else {
      Meeples::moveToCoords($meeple['id'], 'board', $loc);
      $meeple['location'] = 'board';
      $meeple['x'] = $loc['x'];
      $meeple['y'] = $loc['y'];
    }
    $zone['animals']++;
    $zone[$meeple['type']] = ($zone[$meeple['type']] ?? 0) + 1;
  }
}
