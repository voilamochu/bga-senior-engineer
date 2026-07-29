<?php

namespace ARK\Actions;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Stats;
use ARK\Helpers\Utils;
use ARK\Models\Player;
use ARK\Core\Globals;

class Gain extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_GAIN;
  }

  public function getDescription(): string|array
  {
    $player = $this->getPlayer();
    $gain = $this->getGain();
    $desc = Utils::resourcesToStr([$gain[0] => $gain[1]], true);

    if ($player->getId() == Players::getActiveId()) {
      return [
        'log' => clienttranslate('Gain ${resources_desc}'),
        'args' => [
          'resources_desc' => $desc,
        ],
      ];
    }
    // The reward is for someone else
    else {
      return [
        'log' => clienttranslate('Let ${player_name} gain ${resources_desc}'),
        'args' => [
          'player_name' => $player->getName(),
          'resources_desc' => $desc,
        ],
      ];
    }
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function isIndependent(?Player $player = null): bool
  {
    list($resource, $amount) = $this->getGain();
    return in_array($resource, [MONEY, XTOKEN]);
  }

  public function getPlayer(): Player
  {
    $args = $this->getCtxArgs();
    $pId = $args['pId'] ?? Players::getActiveId();
    return Players::get($pId);
  }

  public function getGain()
  {
    $args = $this->getCtxArgs();
    foreach ($args as $resource => $amount) {
      if (in_array($resource, ['cardId', 'pId', 'sourceId', 'source', 'income', 'map'])) {
        continue;
      }

      if (!in_array($resource, [MONEY, REPUTATION, APPEAL, CONSERVATION, XTOKEN])) {
        die('GAIN: unrecognized resource' . $resource);
      }

      // Dynamic gain
      if (in_array($amount, ALL_PREREQUISITES)) {
        $player = $this->getPlayer();
        $amount = $player->countCardIcon($amount);
      }
      // AD-HOC case for ScienceMuseum
      else if ($amount == SCIENCE_SCIENCE) {
        $player = $this->getPlayer();
        $amount = 2 * $player->countCardIcon(SCIENCE);
      }
      // AD-HOC case for PlanProect
      else if ($amount == "SCIENCE/2") {
        $player = $this->getPlayer();
        $amount = intdiv($player->countCardIcon(SCIENCE), 2);
      }
      // AD-HOC case for LandscapeGardener
      else if ($amount == PAVILION) {
        $player = $this->getPlayer();
        $amount = $player->map()->getBuildingsOfType(PAVILION)->count();
      }

      // MAP
      if (isset($args['map'])) {
        $amount = $args['map'][$amount] ?? max($args['map']);
      }

      return [$resource, $amount];
    }
    die('GAIN: resource not found');
  }

  public function stGain()
  {
    $player = $this->getPlayer();
    $args = $this->getCtxArgs();
    list($resource, $amount) = $this->getGain();

    $source = $this->ctx->getSource() ?? null;
    $sourceId = $this->ctx->getSourceId() ?? null;
    if (is_null($source) && !is_null($sourceId)) {
      if ($sourceId == 'S276_LandscapeGardener' && $resource == XTOKEN) {
        Globals::setLandscapeGardener(true);
      }

      $source = ZooCards::getSingle($sourceId);
    }

    // Increase resource and notify
    // Get the previous amount
    $getMethod = $resource == XTOKEN ? 'getXToken' : 'get' . ucfirst($resource);
    $previousAmount = $player->$getMethod();

    $method = 'inc' . ucfirst($resource);
    $bonuses = $player->$method($amount, false);
    if ($resource == MONEY && ($args['income'] ?? false)) {
      Stats::incMoneyGainedIncome($player, $amount);
    }

    // Get the new amount and update the real bonus
    $newAmount = $player->$getMethod();
    $gains = [];
    $gains[$resource] = $newAmount - $previousAmount;

    // Notify
    Notifications::gain($player, $gains, $source);
    Players::checkEndOfGamePlayer($player);

    // Check potential bonuses for reputation and conservation
    $names = [
      REPUTATION => clienttranslate('reputation track bonus'),
      CONSERVATION => clienttranslate('conservation track bonus'),
    ];
    if ($amount != 0) {
      $this->insertBonusesFlow($bonuses, $names[$resource] ?? '', 'bonusTile');
    }

    $this->checkAfterListeners($player, ['gain' => $args]);
    $this->resolveAction();
  }
}
