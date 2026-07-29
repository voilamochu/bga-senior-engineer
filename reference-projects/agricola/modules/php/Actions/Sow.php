<?php
namespace AGR\Actions;

use AGR\Core\Notifications;
use AGR\Managers\Meeples;
use AGR\Managers\PlayerCards;
use AGR\Managers\Players;
use AGR\Core\Engine;
use AGR\Helpers\Utils;

class Sow extends \AGR\Models\Action
{
  public function __construct($row)
  {
    parent::__construct($row);
  }

  public function getState()
  {
    return ST_SOW;
  }

  public function getDescription($ignoreResources = false)
  {
    $args = $this->getCtxArgs();
    if (!Utils::hasReplace($this->getClassName()) || ($args['checkedReplaceAction'] ?? false)) {
      return clienttranslate('Sow');
    } else {
      return clienttranslate('Sow / Replace sow');
    }
  }

  public function isDoable($player, $ignoreResources = false)
  {
    $reserve = $player->getAllReserveResources();
    return $player->board()->canSow($reserve, $ignoreResources, true);
  }

  public static function isUnconditional($args): bool
  {
    $hasCondition = isset($args['max']) || isset($args['type']);
    return !$hasCondition;
  }
  protected function isSkimmerPlow($player)
  {
    return $player->hasPlayedCard('E17_SkimmerPlow');
  }

  // Auto-resolve when the context opts in and leaves no real choice: one field, one allowed
  // crop, max 1 (e.g. Crop Rotation Field). Goes through actSow so listeners/notifs still fire.
  function stSow()
  {
    if (!($this->getCtxArgs()['auto'] ?? false)) {
      return;
    }
    // Still optional = the player hasn't opted in via a choice yet — let them decline first
    if (is_object($this->ctx) && $this->ctx->isOptional()) {
      return;
    }

    $args = $this->argsSow();
    $types = $args['types'] ?? [];
    if (count($types) != 1 || count($args['zones']) != 1 || $args['max'] != 1) {
      return;
    }
    $type = $types[0];
    if ($args[$type] < 1) {
      return;
    }
    $zone = reset($args['zones']);
    $this->actSow([['id' => $zone['uid'] ?? $zone['id'], 'crop' => $type]]);
  }

  function argsSow()
  {
    $player = Players::getActive();
    $reserve = $player->getAllReserveResources();
    $type = $this->getCtxArgs()['type'] ?? null;
    return [
      VEGETABLE => $reserve[VEGETABLE],
      GRAIN => $reserve[GRAIN],
      WOOD => $reserve[WOOD],
      STONE => $reserve[STONE],
      // 'SkimmerPlow' => $this->isSkimmerPlow($player),
      'zones' => $this->sowable($player, $reserve),
      'max' => $this->maxZones(),
      'types' => $type === null ? null : (is_array($type) ? $type : [$type]),
    ];
  }

  function sowable($player, $reserve)
  {
    $fields = $player->board()->getSowableFields($reserve, false, true);
    $ctx = $this->getCtxArgs();

    // Positive whitelist (pre-existing behaviour)
    if (\array_key_exists('location', $ctx) && \is_array($ctx['location'])) {
      $allowed = $ctx['location'];

      Utils::filter($fields, function ($field) use ($allowed) {
        if (!isset($field['uid'])) {
          return false;
        }

        return \in_array($field['uid'], $allowed, true);
      });
    }

    // Filter out fields whose constraints conflict with the action type
    $type = $ctx['type'] ?? null;
    if ($type !== null) {
      $allowedTypes = is_array($type) ? $type : [$type];
      Utils::filter($fields, function ($field) use ($allowedTypes) {
        if (!isset($field['constraints'])) {
          return true;
        }
        return in_array($field['constraints'], $allowedTypes);
      });
    }

    // exclude specific field uids
    $excluded = $this->getExcludedFields();
    if (!empty($excluded)) {
      Utils::filter($fields, function ($field) use ($excluded) {
        return !\in_array($field['uid'], $excluded, true);
      });
    }

    return $fields;
  }

  /**
   * Return a flat list of field uids that must not be sowable.
   *
   * Cards should pass an array of field uids under 'excludedFields'
   * in the action context (same identifiers used by 'location').
   */
  private function getExcludedFields()
  {
    $excluded = $this->getCtxArgs()['excludedFields'] ?? [];

    if (!is_array($excluded)) {
      $excluded = [];
    }

    $excluded = array_unique($excluded);
    return array_values($excluded);
  }


  function maxZones()
  {
    return $this->getCtxArgs()['max'] ?? 99;
  }

  function actSow($crops)
  {
    $player = Players::getActive();
    self::checkAction('actSow');
    $unconditional = $this->isUnconditional($this->getCtxArgs());

    $this->checkListeners('Sow', $player, ['unconditional' => $unconditional]);

    // Sanity checks
    $args = $this->argsSow();
    $fields = [];
    foreach ($args['zones'] as $field) {
      $fields[$field['uid'] ?? $field['id']] = $field;
    }
    $choices = [GRAIN => 0, VEGETABLE => 0, WOOD => 0, STONE => 0];
    foreach ($crops as $crop) {
      if (!is_array($crop) || !array_key_exists($crop['id'] ?? null, $fields)) {
        throw new \BgaVisibleSystemException('You can\'t sow a crop here');
      }

      $actionType = $this->getCtxArgs()['type'] ?? null;
      if ($actionType !== null) {
        $allowedTypes = is_array($actionType) ? $actionType : [$actionType];
        if (!in_array($crop['crop'], $allowedTypes)) {
          throw new \BgaVisibleSystemException('You are not allowed to sow this crop type on this action');
        }
      }
      $choices[$crop['crop']]++;
    }

    if ($choices[GRAIN] + $choices[VEGETABLE] + $choices[WOOD] + $choices[STONE] == 0) {
      throw new \BgaVisibleSystemException('You must sow at least one crop');
    }
    if ($choices[GRAIN] > $args[GRAIN]) {
      throw new \BgaVisibleSystemException('You can\'t sow that much grain');
    }
    if ($choices[VEGETABLE] > $args[VEGETABLE]) {
      throw new \BgaVisibleSystemException('You can\'t sow that many vegetables');
    }
    if ($choices[WOOD] > $args[WOOD]) {
      throw new \BgaVisibleSystemException('You can\'t sow that much wood');
    }
    if ($choices[STONE] > $args[STONE]) {
      throw new \BgaVisibleSystemException('You can\'t sow that much stone');
    }

    if ($choices[GRAIN] + $choices[VEGETABLE] + ($choices[WOOD] > 0) + ($choices[STONE] > 0) > $this->maxZones()) {
      throw new \BgaVisibleSystemException("You can't sow this many fields");
    }

    $nbrs = [GRAIN => 2, VEGETABLE => 1, WOOD => 2, STONE => 1];
    if ($this->isSkimmerPlow($player)) {
      foreach ($nbrs as $key => $value) {
        $nbrs[$key] = $value - 1;
      }
    }
    // Add them to board (update $pos variable to add info about the meeple)
    $sows = [];
    foreach ($crops as $crop) {
      $field = $fields[$crop['id']];

      if (isset($field['constraints'])) {
        if ($crop['crop'] != $field['constraints']) {
          throw new \BgaVisibleSystemException('You must respect the contraints of the card');
        }
      }

      // Move existing crop
      $seed = $player->getNextCropToSow($crop['crop']);
      $location = $field['type'] == 'fieldCard' ? $field['id'] : 'board';
      Meeples::moveToCoords($seed['id'], $location, $field);
      $seed = Meeples::get($seed['id']);

      $grownCount = $nbrs[$crop['crop']];
      if ($grownCount > 0) {
        // Sow new crops
        $ids = Meeples::createResourceInLocation(
          $crop['crop'],
          $location,
          $field['pId'],
          $field['x'],
          $field['y'],
          $nbrs[$crop['crop']]
        );
        $sows[] = [
          'field' => $field,
          'type' => $crop['crop'],
          'seed' => $seed,
          'crops' => Meeples::getMany($ids)->toArray(),
        ];
      } else {
        $sows[] = [
          'field' => $field,
          'type' => $crop['crop'],
          'seed' => $seed,
          'crops' => [],
        ];
      }
    }

    // Notify
    Notifications::sow($player, $sows);
    Notifications::updateDropZones($player);
    $player->forceReorganizeIfNeeded();

    $cardId = $this->getCtxArgs()['cardId'] ?? null;
    $sourceId = $this->ctx->getSourceId();
    if (!is_null($cardId) && $sourceId !== $cardId) {
      $cards = PlayerCards::getMany([$cardId], false);
      if ($cards->count() === 1) {
        $card = $cards->first();
        if ($card->getPId() === $player->getId()) {
          $card->incStats('used');
        }
      }
    }

    $this->checkAfterListeners($player, ['sows' => $sows, 'unconditional' => $unconditional]);
    $this->resolveAction(['sows' => $sows]);
  }
}
