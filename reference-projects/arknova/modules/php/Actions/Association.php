<?php

namespace ARK\Actions;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Core\Notifications;
use ARK\Managers\ActionCards;
use ARK\Managers\ZooCards;
use ARK\Core\Engine;
use ARK\Core\Stats;
use ARK\Helpers\Utils;
use ARK\Helpers\Collection;
use ARK\Helpers\FlowConvertor;
use ARK\Core\Globals;
use ARK\Models\Player;

class Association extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_ASSOCIATION;
  }

  public function getDescription(): string
  {
    return clienttranslate('Perform association task(s)');
  }

  public function isDoable(Player $player): bool
  {
    return $this->getAvailableSlots($player, true) || $player->hasKeptBonusTile(BONUS_RETURN_WORKER);
  }

  public function isOptional(): bool
  {
    return !empty($this->getPreviousActions());
  }

  public function getPreviousActions()
  {
    return $this->getCtxArg('previous') ?? [];
  }

  public function getStrengthLeft()
  {
    return $this->getStrength() - ($this->getCtxArg('previousStrength') ?? 0);
  }

  public function getAvailableSlots($player, $isDoable = false)
  {
    $workers = $player->countAvailableWorkers();
    $actualStrength = $this->getStrengthLeft();
    $previousActions = $this->getPreviousActions();

    $methods = [
      0 => 'getAvailableDonation',
      2 => 'canTakeReputation',
      3 => 'getAvailablePartnerZoos',
      4 => 'getAvailableUniversities',
      5 => 'getPlayableConservationProjects',
    ];
    $slots = [];
    foreach ($methods as $strength => $method) {
      // Enough strength ?
      $strengthNeeded = $strength;
      if ($player->hasPlayedCard('S203_Veterinarian') && $strength == 5) {
        $strengthNeeded = 4;
      }

      // Already done ?
      if (in_array($strength, $previousActions)) {
        continue;
      }

      // Player has enough workers ?
      $workersNeeded = $strength == 0 ? 0 : $player->countWorkersInSlot($strength) + 1;
      if ($workers < $workersNeeded) {
        continue;
      }

      // Enough strength ?
      if ($strengthNeeded > $actualStrength) {
        // ASSOC2 - LVL2
        if ($this->getNumber() == 2 && $this->isUpgraded()) {
          $delta = $strengthNeeded - $actualStrength;
          $extraWorkers = ceil($delta / 2);
          if ($workers < $workersNeeded + $extraWorkers) {
            continue;
          }
        }
        // STANDARD CASE => cant do this task
        else {
          continue;
        }
      }

      // Check possible options for that strength
      $options = $this->$method($player);
      if (empty($options)) {
        continue;
      }

      if ($isDoable) {
        return true;
      }
      $slots[$strength] = [
        'workers' => $workersNeeded,
        'options' => $options,
      ];
      if (isset($extraWorkers)) {
        $slots[$strength]['extraWorkers'] = $extraWorkers;
      }

      // ASSOCIATION 1
      if (in_array($strength, [3, 4]) && $this->getNumber() == 1) {
        $slots[$strength]['meeples'] = Meeples::getMany($slots[$strength]['options']);
      }
    }

    // edge-case for map 4:
    // It is possible to sell a card that was get from reputation track and sell it to have money for donation
    $map4EdgeCase = !empty($previousActions) && $player->canUseMap(4) && $this->isUpgraded() && $player->getMoney() + 3 >= $this->findSmallestPossibleDonation($player)[1];

    // ASSOCIATION 4 - LVL2
    if ($this->getNumber() == 4 && $this->isUpgraded() && !empty($this->getPreviousActions())) {
      $slots[TAKE_IN_RANGE_OR_DECK] = [
        'workers' => 0,
        'options' => [1],
      ];
      if ($isDoable) return true;
    }

    // ASSOCIATION 2 - LVL1
    if ($this->getNumber() == 2 && !$this->isUpgraded() && !is_null($player->getNextWorkerInSupply())) {
      $workersNeeded = $player->countWorkersInSlot(5) + 1;
      if ($actualStrength >= 5 && $workers >= $workersNeeded) {
        $slots[BONUS_WORKER] = [
          'workers' => 1,
          'options' => [1],
        ];
        if ($isDoable) return true;
      }
    }


    return $isDoable ? $map4EdgeCase : $slots;
  }

  /**
   * DONATE
   */
  public function getAvailableDonation($player)
  {
    // Action card must be upgraded and cant be the first action
    if (!$this->isUpgraded() || empty($this->getPreviousActions())) {
      return [];
    }

    $possibleDonations = $this->findSmallestPossibleDonation($player);

    // ASSOCIATION3 - LVL2
    if ($this->getNumber() == 3 && $this->isUpgraded()) {
      $possibleDonations[1] = max(0, $possibleDonations[1] - $player->countXTokens());
    }

    return $player->getMoney() < $possibleDonations[1] ? [] : [$possibleDonations];
  }

  /**
   * @return array [slot, cost] of donation
   */
  public static function findSmallestPossibleDonation(Player $player): array
  {
    // Find remaining spots
    $costs = [2, 5, 5, 7, 7, 10, 10, 12];
    foreach (Meeples::getTokensOnDonation() as $token) {
      $key = explode('_', $token['location'])[2];
      if ($key != count($costs) - 1) {
        // Last slot is always available
        unset($costs[$key]);
      }
    }

    // Take the smallest one left
    $slot = array_keys($costs)[0];
    $cost = $costs[$slot];

    // Publication
    if ($player->hasPlayedCard('S273_Publications')) {
      $cost = max(0, $cost - $player->countCardIcon(SCIENCE));
    }

    return [$slot, $cost];
  }

  /**
   * +2 REP
   */
  public function canTakeReputation($player)
  {
    return $player->isCardUpgraded(CARDS) || $player->getReputation() < 9 ? [true] : [];
  }

  /**
   * ZOOs
   */
  public static function getAvailablePartnerZoosAux($player, $lvl, $number = 0)
  {
    // Maximum number of zoos reached ?
    $maxNumberOfZoos = [1 => 2, 2 => 4];
    if ($player->countPartnerZoo() >= $maxNumberOfZoos[$lvl]) {
      return [];
    }

    // ASSOCIATION1
    if ($number == 1) {
      $zoos = [];
      $continents = []; // AVOID TOO MUCH BUTTONS
      foreach (Meeples::getZoosInBox() as $meeple) {
        $continent = explode('-', $meeple['type'])[1];
        if (!in_array($continent, $continents)) {
          $zoos[] = $meeple['id'];
          $continents[] = $continent;
        }
      }
      return $zoos;
    }

    // For each zoo available, check whether the player already has the zoo or not
    $zoos = [];
    foreach (Meeples::getAvailableZoos() as $meeple) {
      $continent = explode('-', $meeple['type'])[1];
      if ($player->hasPartnerZoo($continent)) {
        continue;
      }
      $zoos[] = $meeple['id'];
    }

    return $zoos;
  }

  public function getAvailablePartnerZoos($player, $bypassLvl = null)
  {
    return self::getAvailablePartnerZoosAux($player, $bypassLvl ?? $this->getLevel(), $this->getNumber());
  }

  /**
   * UNIVERSITIES
   */
  public static function getAvailableUniversitiesAux($player, $lvl = 0, $number = 0)
  {
    // Maxiumum number of universities reached ?
    if ($player->countUniversities() == 3) {
      return [];
    }

    // ASSOCIATION1
    if ($number == 1 && $lvl == 2) {
      $universities = [];
      $types = [UNIVERSITY_SCIENCE_ANIMAL_GEN]; // AVOID TOO MUCH BUTTONS
      foreach (Meeples::geUniversitiesInBox() as $meeple) {
        $type = $meeple['type'];
        if (!in_array($type, $types)) {
          $universities[] = $meeple['id'];
          $types[] = $type;
        }
      }
      // Add specialized univs as they are considered on the side
      foreach (Meeples::getAvailableUniversities("side") as $meeple) {
        $universities[] = $meeple['id'];
      }

      return $universities;
    }


    // For each university available, check whether the player already has the univ or not
    $universities = [];
    foreach (Meeples::getAvailableUniversities() as $meeple) {
      if ($player->hasUniversity($meeple['type'])) {
        continue;
      }

      // Marine World university
      if ($meeple['type'] == UNIVERSITY_SCIENCE_ANIMAL_GEN) {
        if ($player->hasSpecializedUniversity()) {
          continue;
        }
        // Add specialized univs instead of the placeholder
        foreach (Meeples::getAvailableUniversities("side") as $meeple) {
          $universities[] = $meeple['id'];
        }
      } else {
        $universities[] = $meeple['id'];
      }
    }

    return $universities;
  }

  public function getAvailableUniversities($player)
  {
    return self::getAvailableUniversitiesAux($player, $this->getLevel(), $this->getNumber());
  }


  /**
   * CONSERVATION PROJECTS
   */
  public function getPlayableConservationProjects($player)
  {
    $cards = ZooCards::getAssociationProjects()
      ->merge(ZooCards::getBaseProjects())
      ->merge($player->getHand(CARD_PROJECT))
      ->merge($player->getHand(CARD_BASE_PROJECT));

    if ($this->isUpgraded()) {
      $cards = $cards->merge($player->getCardsInReputationRange(\CARD_PROJECT));
    }

    $projects = [];
    foreach ($cards as $cId => $card) {
      // Must be able to afford the cost
      $poolNumber = $card->getPoolNumber();
      if (!is_null($poolNumber) && $player->getMoney() < $poolNumber) {
        continue;
      }

      $slots = $card->getPlayableSlots($player);
      if (empty($slots)) {
        continue;
      }

      $projects[$cId] = $slots;
    }

    return $projects;
  }

  public function argsAssociation()
  {
    $player = Players::getActive();
    $data = [
      'i18n' => ['source'],
      '_private' => ['active' => ['slots' => $this->getAvailableSlots($player)]],
      'showFolderCosts' => $this->isUpgraded(),
      'bonusesSpaces' => $player->getOccupiedBonusesSpaces(),

      // Title
      'strengthLeft' => $this->getStrengthLeft(),
      'strength' => $this->getStrength(),
      'strength_icon' => '',
    ];

    // ASSOC 2 - LVL2
    if ($this->getNumber() == 2 && $this->isUpgraded()) {
      $data['useExtraWorkers'] = $player->countAvailableWorkers();
    }

    return $data;
  }

  public function actAssociation($strength, $option, $extraWorkers = 0)
  {
    // Sanity checks
    self::checkAction('actAssociation');
    $player = Players::getActive();
    $possibleSlots = $this->getAvailableSlots($player);
    if (!array_key_exists($strength, $possibleSlots)) {
      throw new \BgaVisibleSystemException('Cannot take that strength for worker placement. Should not happen');
    }
    $slot = $possibleSlots[$strength];
    if (!in_array($option, $slot['options'])) {
      throw new \BgaVisibleSystemException('Invalid option for this slot. Should not happen');
    }

    // ASSOC 2 - LVL1
    if ($strength == BONUS_WORKER) {
      $workers = $player->useWorkers(1, "association_5");
      $newWorker = $player->getNextWorkerInSupply();
      // Gain new worker and move it along the used one
      $bonuses = $player->gainWorker(false);
      Meeples::move($newWorker['id'], "association_5");
      $newWorker['location'] = 'association_5';
      $workers[] = $newWorker;
      Notifications::association2HireWorker($player, $workers);

      // Insert bonus
      $n = $player->countWorkersOnBoard();
      $msgs = [
        1 => clienttranslate('1st worker bonus'),
        2 => clienttranslate('2nd worker bonus'),
        3 => clienttranslate('last worker bonus'),
      ];
      $this->insertBonusesFlow($bonuses, $msgs[$n]);
      $this->resolveAction([BONUS_WORKER => 1]);
      return;
    }

    // Move workers
    $workers = new Collection([]);
    $nWorkers = $slot['workers'];
    if ($nWorkers + $extraWorkers > $player->countAvailableWorkers()) {
      throw new \BgaVisibleSystemException('Invalid number of workers for this slot. Should not happen');
    }
    if ($nWorkers > 0) {
      if ($extraWorkers > 0) {
        Notifications::message('${player_name} uses Association2 effect to reduce the strength of the task', ['player' => $player]);
        $nWorkers += $extraWorkers;
      }
      if ($nWorkers > 0) {
        $workers = $player->useWorkers($nWorkers, "association_$strength");
      }
      Notifications::placeWorkers($player, $strength, $workers);
    }

    // ASSOC3 - LVL1
    if ($this->getNumber() == 3 && !$this->isUpgraded() && $strength < $this->getStrength()) {
      $this->insertBonusesFlow([[XTOKEN => 1]], clienttranslate('Association3 effect'));
    }

    // ASSOC 4 - LVL2
    if ($strength == TAKE_IN_RANGE_OR_DECK) {
      $this->insertBonusesFlow([[TAKE_IN_RANGE_OR_DECK => 1]], clienttranslate('Association4'));
      // End action
      $this->resolveAction([TAKE_IN_RANGE_OR_DECK => 1]);
      return;
    }


    // DONATE
    if ($strength == 0) {
      Stats::incAssociationDonation($player);
      list($slot, $cost) = $option;
      $player->pay($cost, false);
      Stats::incMoneyUsedDonations($player, $cost);
      $bonuses = $player->incConservation(1, false);
      $meeple = Meeples::addTokenOnDonationSlot($player, $slot);
      Notifications::donation($player, $cost, $meeple);
      $this->insertBonusesFlow($bonuses, clienttranslate('conservation track bonus'), 'bonusTile');
      // End action
      $this->resolveAction(['donation' => $cost]);
      return;
    }
    // TAKE REPUTATION
    elseif ($strength == 2) {
      Stats::incAssociationReputation($player);
      $this->insertBonusesFlow([[REPUTATION => 2]], clienttranslate('association board'));
    }
    // PARTNER ZOO
    elseif ($strength == 3) {
      Stats::incAssociationPartner($player);
      $bonuses = $player->addPartnerZoo($option);
      $this->insertBonusesFlow($bonuses, \clienttranslate('partner zoo'));
    }
    // UNIVERSITY
    elseif ($strength == 4) {
      Stats::incAssociationUniversity($player);
      $univ = Meeples::getSingle($option);
      $fromSupply = $this->getNumber() == 1 && $this->isUpgraded();

      if (in_array($univ['type'], UNIVERSITIES_ANIMALS) && !$fromSupply) {
        $genericUniv = Meeples::getAvailableUniversities()->filter(fn($m) => $m['type'] == UNIVERSITY_SCIENCE_ANIMAL_GEN)->first();
        if (!is_null($genericUniv) && $genericUniv['location'] != 'box') {
          Meeples::move($genericUniv['id'], 'box');
          Notifications::takeSpecializedUniv($genericUniv);
        }
      }
      $bonuses = $player->addUniversity($option);
      $this->insertBonusesFlow($bonuses, \clienttranslate('university'));
    }

    // Are we done yet ?
    if ($this->isUpgraded()) {
      $actions = $this->getPreviousActions();
      $actions[] = $strength;
      $strengthUsed = $this->getCtxArg('previousStrength') ?? 0;
      $strengthUsed += max(0, $strength - 2 * $extraWorkers);

      // ASSOC 2 sanity check
      if ($this->getStrength() - $strengthUsed < 0) {
        throw new \BgaVisibleSystemException('Invalid number of workers for this slot. Should not happen');
      }

      $this->duplicateAction(['previous' => $actions, 'previousStrength' => $strengthUsed]);
    }

    $this->resolveAction(['strength' => $strength, 'option' => $option]);
  }

  public function actConservationProject($cardId, $sId, $bonusSpace, $animalId = null, $extraWorkers = 0)
  {
    $player = Players::getActive();
    $strength = 5;
    $strengthNeeded = $strength;
    if ($player->hasPlayedCard('S203_Veterinarian') && $strength == 5) {
      $strengthNeeded = 4;
    }

    // Sanity checks
    self::checkAction('actConservationProject');
    $possibleSlots = $this->getAvailableSlots($player);
    if (!array_key_exists($strength, $possibleSlots)) {
      throw new \BgaVisibleSystemException('Cannot take that strength for worker placement. Should not happen');
    }
    $slot = $possibleSlots[$strength];
    if (!array_key_exists($cardId, $slot['options'])) {
      throw new \BgaVisibleSystemException('Invalid option for this slot. Should not happen');
    }

    Stats::incAssociationConservation($player);
    $card = ZooCards::get($cardId);

    // Move workers
    $nWorkers = $slot['workers'];
    if ($nWorkers + $extraWorkers > $player->countAvailableWorkers()) {
      throw new \BgaVisibleSystemException('Invalid number of workers for this slot. Should not happen');
    }
    if ($extraWorkers > 0) {
      Notifications::message('${player_name} uses Association2 effect to reduce the strength of the task', ['player' => $player]);
      $nWorkers += $extraWorkers;
    }
    if ($nWorkers > 0) {
      $workers = $player->useWorkers($nWorkers, "association_$strength");
    }
    Notifications::placeWorkersProject($player, $strength, $workers, $card, $sId);

    // If hand => move & discard if needed
    $playedBonus = null;
    $cardSlot = $card->getSlots()[$sId];
    $poolNumber = $card->getPoolNumber();
    $fromDisplay = !is_null($poolNumber);
    if ($card->getLocation() == 'hand' || $fromDisplay) {
      ZooCards::insertProjectCard($cardId);
      Notifications::moveProjects($player, $card, ZooCards::getAssociationProjects(), $fromDisplay);

      // Gain 1 reputation for adding a release project or something else from MW PlanProject
      $playedBonus = $card->getPlayedBonus();

      // Paying cost
      if ($fromDisplay) {
        $player->pay($poolNumber, true, \clienttranslate('supporting conservation project from reputation range'));
        Stats::incMoneyUsedFromDisplay($player, $poolNumber);
      }
    }

    // Handle the case of using tokens from sponsors
    $nbTokensNeeded = 0;
    $tokensUsed = [];
    $usedCardIds = [];
    while (!$card->canPlaySlot($player, $cardSlot, $nbTokensNeeded) && $nbTokensNeeded < 4) {
      $nbTokensNeeded++;
      $token = $player->useReductionToken($usedCardIds);
      $tokensUsed[] = $token;
      $usedCardIds[] = $token['location'];
    }
    if ($nbTokensNeeded > 0) {
      Notifications::useReductionTokens($player, $tokensUsed);
    }

    // Move token
    $token = Meeples::moveToProject($player, $bonusSpace, $cardId, $sId);
    Notifications::slideMeeples([$token]);

    // if release animal, loose appeal
    if ($card->getCategory() == PROJECT_RELEASE) {
      $this->insertAsChild(['action' => RELEASE, 'args' => ['card' => $animalId]]);
    }

    // ASSOC3 - LVL1
    if ($this->getNumber() == 3 && !$this->isUpgraded() && $strengthNeeded < $this->getStrength()) {
      $this->insertBonusesFlow([[XTOKEN => 1]], clienttranslate('Association3 effect'));
    }

    // from hand => bonus of 1 rep
    if (!is_null($playedBonus) && !empty($playedBonus)) {
      $this->insertBonusesFlow([$playedBonus], clienttranslate('adding a new conservation project'));
    }

    // Gain bonus of conservation (and sometimes reputation)
    $bonuses = [];
    foreach ($cardSlot['gain'] as $type => $n) {
      // MW : handle REEF reward
      if ($type == REEF) {
        Notifications::message(clienttranslate('${player_name} triggers its reef abilities (conservation project reward)'), ['player' => $player]);
        $abilities = $player->getReefAbilities();
        $this->insertBonusesFlow($abilities);
        continue;
      }

      $bonuses[] = [$type => $n];
    }
    $this->insertBonusesFlow($bonuses, $card->getName());

    // Gain 1 additional conservation if release & player has Migration Recording
    if ($player->hasPlayedCard('S224_MigrationRecording') && $card->getCategory() == PROJECT_RELEASE) {
      $this->insertBonusesFlow([[CONSERVATION => 1]], clienttranslate('Migration Recording'));
    }
    // Gain bonus of token
    $bonusSpaces = $player->map()->getBonusSpaces();
    $this->insertBonusesFlow([$bonusSpaces[$bonusSpace]['bonus']], \clienttranslate('map bonus space'));

    // Are we done yet ?
    if ($this->isUpgraded()) {
      $actions = $this->getPreviousActions();
      $actions[] = $strength;

      $strengthUsed = $this->getCtxArg('previousStrength') ?? 0;
      $strengthUsed += max(0, $strengthNeeded - 2 * $extraWorkers);

      // ASSOC 2 sanity check
      if ($this->getStrength() - $strengthUsed < 0) {
        throw new \BgaVisibleSystemException('Invalid number of workers for this slot. Should not happen');
      }

      $this->duplicateAction(['previous' => $actions, 'previousStrength' => $strengthUsed]);
    }

    $this->resolveAction([
      'strength' => $strength,
      'cardId' => $cardId,
      'slotId' => $sId,
      'bonusSpace' => $bonusSpace,
      'animalId' => $animalId,
    ]);
  }
}
