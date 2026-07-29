<?php

namespace ARK\Actions;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Core\Notifications;
use ARK\Managers\ZooCards;
use ARK\Core\Engine;
use ARK\Core\Stats;
use ARK\Core\Globals;
use ARK\Helpers\Utils;
use ARK\Helpers\Collection;
use ARK\Helpers\FlowConvertor;
use ARK\Models\Player;

class Animals extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_ANIMALS;
  }

  public function getDescription(): string|array
  {
    $args = $this->getCtxArgs();
    if ($args['max'] == 2) {
      return clienttranslate('Play up to 2 Animals');
    } else {
      if ($args['ignore'] ?? false) {
        return clienttranslate('Play 1 Animal (ignore 1 condition)');
      } else {
        return clienttranslate('Play 1 Animal');
      }
    }
  }

  public static function getPlayableStatuses($player, $args = [])
  {
    $biggestHerbivore = $player->getBiggestHerbivore();
    $wazaSpecial = $player->hasPlayedCard('S227_WazaSpecialAssignment')
      ? ZooCards::getSingle('S227_WazaSpecialAssignment')->getExtraDatas('choice')
      : null;

    $cards = $player->getHand(CARD_ANIMAL)->merge(ZooCards::getPool(6, CARD_ANIMAL))->merge($player->getStoredCards(CARD_ANIMAL));
    $icons = $player->countCardIcons();

    $statuses = new Collection([]);
    foreach ($cards as $cId => $animal) {
      $status = ['id' => $cId];

      // 1. Conditions
      $status['conditions'] = $animal->checkConditions($player, $icons, $args['ignore'] ?? 0);

      // 1. bis Waza special
      if (!is_null($wazaSpecial)) {
        $status['wazaSpecial'] = !(
          ($wazaSpecial == 'small' && $animal->isLarge()) ||
          ($wazaSpecial == 'large' && $animal->isSmall())
        );
      }

      // 2. Can pay cost (- partner zoo)
      $cost = $animal->getBuyCost($player);
      // ANIMALS3 - SIDE I
      if (($args['number'] ?? 0) == 3 && $args['lvl'] == 1 && count($args['previous'] ?? []) == 0) {
        $cost = max($cost - 2, 0);
      }
      $status['cost'] = $player->getMoney() >= $cost;
      $baseCost = $cost - $animal->getFolder();
      $status['baseCost'] = $player->getMoney() >= $baseCost;
      $status['folder'] = $animal->getFolder();

      // 3. Has enclosure available
      $canFitEnclosure = false;
      $enclosuresByTypes = $player->map()->getAvailableEnclosures($animal);
      $status['enclosures'] = [];
      foreach ($enclosuresByTypes as $type => $enclosures) {
        $status['enclosures'][$type] = true;
        $canFitEnclosure = true;
      }

      $flockSize = $animal->getFlockSize();
      $canFlock = $flockSize !== false && $flockSize <= $biggestHerbivore;
      $status['canFlock'] = $canFlock;
      $status['enclosure'] = $canFitEnclosure || $status['canFlock'];

      // Playable
      $status['playable'] =
        $status['conditions']['valid'] && ($status['wazaSpecial'] ?? true) && $status['cost'] && $status['enclosure'];
      $statuses[$cId] = $status;
    }
    return $statuses;
  }

  public function getBuyableAnimals($player, $isDoableTesting = false)
  {
    $args = $this->getCtxArgs();
    $statuses = $this->getPlayableStatuses($player, $args)->filter(fn($status) => $status['playable']);
    $cardIds = $statuses->getIds();
    $cards = ZooCards::getMany($cardIds);

    $isSmallWaza = $this->getCtxArg('wazaSmall') ?? false;
    $maxFolder = $this->isUpgraded() && !$isSmallWaza ? $player->getMaxFolderInRange() : 0;
    $buyable = [];
    foreach ($cards as $cId => $animal) {
      // Within reputation range ?
      if ($animal->getFolder() > $maxFolder) {
        continue;
      }

      // 1.bis WazaSmall
      if ($isSmallWaza && !$animal->isSmall()) {
        continue;
      }

      // 3. Has enclosure available
      if ($isDoableTesting) {
        return true;
      }
      $enclosuresByTypes = $player->map()->getAvailableEnclosures($animal);
      if ($statuses[$cId]['canFlock']) {
        $enclosuresByTypes[FLOCK_ANIMAL] = true;
      }
      $buyable[$animal->getId()] = $enclosuresByTypes;
    }

    return $isDoableTesting ? false : $buyable;
  }

  public function isDoable(Player $player): bool
  {
    if (!$this->isUpgraded() && $this->getStrength() == 1) {
      return false;
    }
    return $this->getBuyableAnimals($player, true);
  }

  public function isOptional(): bool
  {
    return !empty($this->getPreviousActions());
  }

  public function getPreviousActions()
  {
    return $this->getCtxArg('previous') ?? [];
  }

  public function canUseWazaSmallAnimalProgram($previousActions = null)
  {
    $player = Players::getActive();
    if (!$player->hasPlayedCard('S228_WazaSmallAnimalProgram')) {
      return false;
    }

    $actions = $previousActions ?? $this->getPreviousActions();
    $smallAnimals = ZooCards::getMany($actions)->filter(function ($card) {
      return $card->isSmall();
    });

    return $smallAnimals->count() == count($actions);
  }

  public function argsAnimals()
  {
    $player = Players::getActive();
    $args = $this->getCtxArgs();

    $data = [
      'i18n' => ['source'],
      '_private' => ['active' => ['cardIds' => $this->getBuyableAnimals($player)]],

      // Title
      'source' => $this->isUpgraded() ? clienttranslate('hand or within reputation range') : clienttranslate('hand'),
      'showFolderCosts' => $this->isUpgraded(),
      'strength' => $this->getStrength(),
      'strength_icon' => '',
      'count' => ($args['max'] ?? 1) == 1 ? '' : ' (' . (count($this->getPreviousActions()) + 1) . '/' . ($args['max'] ?? 0) . ')',
    ];

    if ($this->getCtxArg('wazaSmall') ?? false) {
      $data['descSuffix'] = 'wazaSmall';
      $data['source'] = clienttranslate('hand');
    }

    return $data;
  }

  public function actAnimals($cardId, $type, $selection)
  {
    self::checkAction('actAnimals');
    $player = Players::getActive();
    $cards = $this->getBuyableAnimals($player);
    $args = $this->getCtxArgs();

    // 1. Sanity check
    $enclosures = $cards[$cardId][$type] ?? null;
    if (is_null($enclosures)) {
      throw new \BgaVisibleSystemException('Invalid animal or enclosure type. Should not happen');
    }
    $animal = ZooCards::get($cardId);
    $totalN = 0;
    foreach ($selection as $enclosureId => $n) {
      if (!isset($enclosures[$enclosureId])) {
        throw new \BgaVisibleSystemException('Invalid enclosure. Should not happen');
      }
      $totalN += $n;
    }
    if ($type != REGULAR_ENCLOSURE_TYPE && $type != FLOCK_ANIMAL && $totalN != $animal->getSpecialEnclosure()['cubes']) {
      throw new \BgaVisibleSystemException('Wrong number of cubes. Should not happen');
    }

    // 1.5 DO WE NEED TO USE A BONUS?
    $icons = $player->countCardIcons();
    $bonusTile = $player->getKeptBonusTile(BONUS_IGNORE_CONDITION);
    if (!is_null($bonusTile)) {
      $conditions = $animal->checkConditions($player, $icons, ($args['ignore'] ?? 0) - 3);
      if (!$conditions['valid']) {
        Meeples::move($bonusTile['id'], 'box');
        Notifications::useBonus($player, $bonusTile['type'], 3, clienttranslate('bonus tile'), $bonusTile);
      }
    }


    $fromDisplay = !is_null($animal->getPoolNumber());

    // 2. Pay costs
    $cost = $animal->getBuyCost($player);
    if ($this->getNumber() == 3 && !$this->isUpgraded() && empty($this->getPreviousActions())) {
      $cost = max($cost - 2, 0);
    }
    $player->pay($cost, false);
    Stats::incMoneyUsedAnimals($player, $cost);
    Stats::incMoneyUsedFromDisplay($player, $animal->getFolder());

    // 3. fill enclosure
    $enclosures = [];
    $map9Continents = [];
    if ($type == \FLOCK_ANIMAL) {
      $enclosures = null;
    } else {
      foreach ($selection as $enclosureId => $n) {
        list($enclosure, $map9Continent) = $player->map()->fillEnclosure($enclosureId, $animal, $n);
        $enclosures[] = $enclosure;
        if (!is_null($map9Continent) && !in_array($map9Continent, $map9Continents)) {
          $map9Continents[] = $map9Continent;
        }
      }
    }

    // Immediate/after finishing bonuses/effects
    $immediate = [];
    $after = [];

    // ANIMALS3
    if ($this->getNumber() == 3 && $this->isUpgraded()) {
      $immediate[] = [
        'action' => ANIMALS3_PAYGAIN,
        'source' => clienttranslate('Animals3 ability'),
      ];
    }

    // 4. place animal card
    // MARKED ?
    if ($animal->isMarked()) {
      $mark = $animal->getMark();
      $immediate[] = $animal->removeMarkForMoney($player->getId());

      // ANIMALS4
      if ($this->getNumber() == 4 && $mark['pId'] == $player->getId()) {
        $immediate[] = [
          'action' => GAIN,
          'args' => [REPUTATION => 1],
          'source' => clienttranslate('Animals4 bonus')
        ];
      }
    }
    $animal->setPId($player->getId());
    $animal->setLocation('inPlay');
    Notifications::buyAnimal($player, $animal, $cost, $enclosures, $fromDisplay);
    Stats::incAnimalsPlayed($player);


    //  Map 1 effect
    if ($player->canUseMap(1) && !is_null($enclosures) && $player->map()->isMapPower($enclosures)) {
      $immediate[] = [
        'action' => GAIN,
        'args' => [APPEAL => 2],
        'source' => clienttranslate('Map 1 bonus'),
      ];
    }
    // Waza special
    $wazaSpecial = $player->hasPlayedCard('S227_WazaSpecialAssignment')
      ? ZooCards::getSingle('S227_WazaSpecialAssignment')->getExtraDatas('choice')
      : null;
    if (($wazaSpecial == 'small' && $animal->isSmall()) || ($wazaSpecial == 'large' && $animal->isLarge())) {
      $immediate[] = [
        'action' => GAIN,
        'args' => [APPEAL => $wazaSpecial == 'small' ? 2 : 4],
        'sourceId' => 'S227_WazaSpecialAssignment',
      ];
    }
    // Map 9 effect
    if (!empty($map9Continents)) {
      foreach ($map9Continents as $map9Continent) {
        $immediate[] = [
          'action' => MAP9,
          'args' => ['continent' => $map9Continent],
        ];
      }
    }

    // 5.a Get bonuses
    $bonuses = $animal->getBonuses();
    foreach ($bonuses as $bonus => $n) {
      if ($n > 0) {
        $immediate[] = [
          'action' => GAIN,
          'args' => [$bonus => $n],
          'sourceId' => $cardId,
        ];
      }
    }

    // 5.b Execute effect :
    //  - after finishing effects are inserted in a special engine node
    //  - other effect are inserted as child
    $abilities = Globals::isPeaceful() ? $animal->getSoloAbility() : $animal->getAbility();

    foreach ($abilities as $ability => $n) {
      if (in_array($ability, ['Clever', 'Boost', 'Action', 'Determination', 'Mark'])) {
        $after[] = [
          'action' => $ability,
          'args' => ['n' => $n],
          'sourceId' => $cardId,
        ];
      } elseif ($ability != 'FlockAnimal') {
        //      Sprint, Hunter, Inventive, Jumping, Sunbathing, Pouch, Digging, Venom, Pilfering, Snapping, Hypnosis, Scavenging, Posturing, Perception, Pack, Multiplier, FullThroated, IconicAnimal, Resistance, Assertion, SponsorMagnet, Constriction, Peacocking, PettingZooAnimal, Dominance
        $immediate[] = [
          'action' => $ability,
          'args' => ['n' => $n],
          'sourceId' => $cardId,
        ];
      }
    }

    if (!empty($animal->getReefAbility())) {
      Notifications::message(clienttranslate('${player_name} triggers its reef abilities'), ['player' => $player]);
      $abilities = $player->getReefAbilities();
      list($immediateReef, $afterFinishingReef) = FlowConvertor::getFlow($abilities);
      $immediate = array_merge($immediate, $immediateReef);
      $after = array_merge($after, $afterFinishingReef);
    }

    $this->pushParallelChilds($immediate);
    $this->pushAfterFinishingChilds($after);

    // Check if we have reaction from listeners
    $eventData = [
      'animal' => $cardId,
    ];
    $this->checkListeners('Animals', $player, $eventData);
    $this->checkIconsListeners($animal->getIcons(), $player);

    // 6. Insert as brother a similar action with n+1 card played or nothing if done (if player can place multiple animals)
    $actions = $this->getPreviousActions();
    $actions[] = $cardId;
    if ($this->getCtxArg('wazaSmall') ?? false) {
      $this->pushParallelChild([
        'action' => SNAPPING,
        'args' => [
          'n' => 1,
          'wazaSmall' => true,
        ],
      ]);
    } elseif (count($actions) < $this->getCtxArg('max')) {
      $this->duplicateAction(['previous' => $actions]);
    }
    // WazaSmallAnimal card
    elseif ($this->canUseWazaSmallAnimalProgram($actions)) {
      $this->duplicateAction(['previous' => $actions, 'wazaSmall' => true]);
    }

    $this->resolveAction(['cardId' => $cardId]);
  }

  public function actPassAnimals()
  {
    if ($this->getCtxArg('wazaSmall') ?? false) {
      $this->pushParallelChild([
        'action' => SNAPPING,
        'args' => [
          'n' => 1,
          'wazaSmall' => true,
        ],
      ]);
    } elseif ($this->canUseWazaSmallAnimalProgram()) {
      $this->duplicateAction(['wazaSmall' => true]);
    }

    $this->resolveAction([PASS]);
  }
}
