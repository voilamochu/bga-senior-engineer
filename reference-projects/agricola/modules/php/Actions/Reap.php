<?php

namespace AGR\Actions;

use AGR\Core\Engine;
use AGR\Core\Notifications;
use AGR\Core\Stats;
use AGR\Helpers\Utils;
use AGR\Managers\Meeples;
use AGR\Managers\Players;
use AGR\Models\Action;

class Reap extends Action
{
  public function getState()
  {
    return ST_REAP;
  }

  public function isAutomatic($player = null)
  {
    return true;
  }

  public function getDescription($ignoreResources = false)
  {
    $player = Players::getActive();
    $crops = $player->board()->getHarvestCrops();
    $res = Utils::reduceResources($crops);
    return [
      'log' => clienttranslate('Reap ${resources_desc}'),
      'args' => [
        'resources_desc' => Utils::resourcesToStr($res),
      ],
    ];
  }

  public function stReap()
  {
    $args = $this->argsReap();

    // Get growing crops
    $player = Players::getActive();
    $crops = $player->board()->getHarvestCrops($args['zones']);

    // Move them to player reserve
    foreach ($crops as &$crop) {
      Meeples::moveToCoords($crop['id'], 'reserve');
      $crop['originalLocation'] = $crop['location'];
      $crop['location'] = 'reserve';

      // A106_SlurrySpreader bonus
      if ($player->hasPlayedCard('A106_SlurrySpreader')) {
        if (isset($crop['isLastCrop']) && $crop['isLastCrop']) {
          if ($crop['type'] == GRAIN) {
            Engine::insertAsChild([
              'action' => GAIN,
              'args' => [FOOD => 2],
              'source' => clienttranslate('Slurry Spreader')
            ]);
          } elseif ($crop['type'] == VEGETABLE) {
            Engine::insertAsChild([
              'action' => GAIN,
              'args' => [FOOD => 1],
              'source' => clienttranslate('Slurry Spreader')
            ]);
          }
        }
      }

      if (in_array($crop['type'], CROPS)) {
        $statName = 'incHarvested' . ucfirst($crop['type']);
        Stats::$statName($player);
      }
    }

    // Notify
    Notifications::harvestCrop($player, $crops);
    Notifications::updateDropZones($player);

    $eventData = [
      'crops' => $crops,
      'trigger' => $args['trigger'] ?? null,
    ];
    $this->checkAfterListeners($player, $eventData);
    $this->resolveAction();
  }

  public function argsReap()
  {
    $args = $this->getCtxArgs();
    $zones = $args['zones'] ?? null;

    return [
      'i18n' => ['source'],
      'zones' => $zones,
      'trigger' => $args['trigger'] ?? null,
    ];
  }
}
