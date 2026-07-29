<?php
namespace AGR\Actions;
use AGR\Core\Globals;
use AGR\Managers\Meeples;
use AGR\Managers\Players;
use AGR\Managers\PlayerCards;
use AGR\Core\Notifications;
use AGR\Core\Stats;
use AGR\Helpers\Utils;

class Gain extends \AGR\Models\Action
{
  public function getState()
  {
    return ST_GAIN;
  }

  public function isIndependent($player = null)
  {
    $args = $this->getCtxArgs();
    foreach ($args as $resource => $amount) {
      if (\in_array($resource, [SHEEP, PIG, CATTLE])) {
        return false;
      }
    }

    if ($this->ctx->forceConfirmation()) {
      return false;
    }

    return true;
  }

  public function getPlayer()
  {
    $args = $this->getCtxArgs();
    $pId = $args['pId'] ?? Players::getActiveId();
    return Players::get($pId);
  }

  public function stGain()
  {
    $player = $this->getPlayer();
    $args = $this->getCtxArgs();
    $cardId = $this->ctx->getCardId();
    $source = $this->ctx->getSource();
    $meepleStates = $args['meepleStates'] ?? [];

    // Create resources
    $meeples = [];
    foreach ($args as $resource => $amount) {
      if (in_array($resource, ['cardId', 'skipReorganize', 'pId', 'meepleStates'])) {
        continue;
      }

      // SCORE won't create real meeples but still need to be here in the log and with animation
      if ($resource == SCORE) {
        $card = PlayerCards::get($cardId);
        $card->incBonusScore($amount);
        for ($i = 0; $i < $amount; $i++) {
          $meeples[] = ['type' => SCORE, 'location' => $cardId, 'pId' => $player->getId(), 'destroy' => true];
        }
        continue;
      }

      $created = $player->createResourceInReserve($resource, $amount);
      if (isset($meepleStates[$resource])) {
        foreach ($created as &$meeple) {
          Meeples::setState($meeple['id'], $meepleStates[$resource]);
          $meeple['state'] = $meepleStates[$resource];
        }
        unset($meeple);
      }
      $meeples = array_merge($meeples, $created);

      $statName = 'inc' . ($source == null ? 'Board' : 'Cards') . ucfirst($resource);
      Stats::$statName($player, $amount);
      $cardStats = null;
      if (!is_null($source) && !is_null($cardId)) {
        $card = PlayerCards::get($cardId);
        if ($card->getPId() === $player->getId()) {
          $cardStats = $card->incStats('gain', $resource, $amount);
        }
      }

      if ($resource == GRAIN && $player->hasPlayedCard('C86_LivestockFeeder')) {
        Notifications::updateDropZones($player);
      }
    }
    
    $eventData = [
      'actionCardId' => $cardId,
      'meeples' => $meeples,
      'fromActionSpace' => Utils::isActionSpace($cardId),
    ];

    // Auto reorganize if needed (return true if need to enter the state to confirm)
    $this->checkListeners('Gain', $player, $eventData);
    $reorganize = $player->checkAutoReorganize($meeples);
    // Notify
    Notifications::gainResources($player, $meeples, $cardId, $source);
    if ($player->hasInHand('A53_Claypipe') && Globals::isWorkPhase()) {
      $player->updateObtainedResources($meeples);
    }
    if (!($args['skipReorganize'] ?? false)) {
      $player->checkAnimalsInReserve($reorganize);
    }
    $this->checkAfterListeners($player, $eventData);
    $this->resolveAction();
  }

  public function getDescription($ignoreResources = false)
  {
    return [
      'log' => clienttranslate('Gain ${resources_desc}'),
      'args' => [
        'resources_desc' => Utils::resourcesToStr($this->ctx->getArgs()),
      ],
    ];
  }

  public function isAutomatic($player = null)
  {
    return true;
  }
}
