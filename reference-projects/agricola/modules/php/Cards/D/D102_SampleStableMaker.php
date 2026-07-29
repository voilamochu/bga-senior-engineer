<?php
namespace AGR\Cards\D;

use AGR\Managers\Meeples;
use AGR\Managers\Stables;
use AGR\Core\Engine;
use AGR\Core\Notifications;
use AGR\Helpers\Utils;

class D102_SampleStableMaker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D102_SampleStableMaker';
    $this->name = clienttranslate('Sample Stable Maker');
    $this->deck = 'D';
    $this->number = 102;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'At the start of each returning home phase, you can return a built stable to your supply to get 1 <WOOD>, 1 <GRAIN>, 1 <FOOD>, and a __Minor Improvement__ action.'
      ),
    ];
    $this->players = '1+';
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event)
  {
    $player = $this->getPlayer();
    $hasNormal = !empty($player->board()->getStables());
    $hasFarmHand = ($player->board()->getFarmHandTopLeft() !== null);

    if (!$hasNormal && !$hasFarmHand) {
      return false;
    }

    return $this->isPlayerEvent($event) && $event['type'] == 'StartReturnHome';
  }

  public function onPlayerStartReturnHome($player, $event)
  {
    $normalStables = $player->board()->getStables();
    $hasNormal = !empty($normalStables);
    $hasFarmHand = ($player->board()->getFarmHandTopLeft() !== null);

    if (!$hasNormal && !$hasFarmHand) {
      return null;
    }

    // Auto-path: exactly 1 stable total
    if ($player->board()->countStablesForCards() == 1) {
      if ($hasFarmHand) {
        return [
          'action' => SPECIAL_EFFECT,
          'optional' => true,
          'args' => [
            'cardId' => $this->id,
            'method' => 'returnFarmHandStable',
          ],
        ];
      }

      $zone = ['x' => $normalStables[0]['x'], 'y' => $normalStables[0]['y']];
      return [
        'action' => SPECIAL_EFFECT,
        'optional' => true,
        'args' => [
          'cardId' => $this->id,
          'method' => 'returnSingleStable',
          'args' => [$zone],
        ],
      ];
    }

    // Build the XOR choices shown only if the player chooses to use the effect
    $choices = [];

    if ($hasFarmHand) {
      $choices[] = [
        'action' => SPECIAL_EFFECT,
        'args' => [
          'cardId' => $this->id,
          'method' => 'returnFarmHandStable',
        ],
      ];
    }

    if ($hasNormal) {
      $choices[] = [
        'action' => SPECIAL_EFFECT,
        'args' => [
          'cardId' => $this->id,
          'method' => 'returnStables',
        ],
      ];
    }

    // Optional wrapper node: gives the initial "use it or pass" choice
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        [
          'type' => NODE_XOR,
          'childs' => $choices,
        ],
      ],
    ];
  }

  /* ---- Single stable auto-path ---- */

  public function getReturnSingleStableDescription($zone)
  {
    return clienttranslate('Return stable to your supply');
  }

  public function returnSingleStable($zone)
  {
    $this->actReturnStables([$zone]);
  }

  /* ---- Normal stable path ---- */

  public function getReturnStablesDescription()
  {
    return clienttranslate('Return one built stable to your supply');
  }

  public function argsReturnStables()
  {
    $player = $this->getPlayer();

    // Only normal stables are selectable here
    $zones = array_map(function ($s) {
      return ['x' => $s['x'], 'y' => $s['y']];
    }, $player->board()->getStables());

    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} may return one built stable to your supply (Sample Stable Maker)'),
      'descriptionmyturn' => clienttranslate('${you} may return one built stable to your supply (Sample Stable Maker)'),
      'zones' => $zones,
      'method' => 'returnStables',
    ];
  }

  public function actReturnStables($zones)
  {
    $player = $this->getPlayer();

    if (!is_array($zones) || count($zones) !== 1) {
      throw new \BgaUserException(clienttranslate('Select exactly 1 stable.'));
    }

    $selected = $zones[0];
    $meeples = [];
    $stableIds = [];

    foreach ($player->board()->getStables() as $stable) {
      if ($stable['x'] == $selected['x'] && $stable['y'] == $selected['y']) {
        $meeples[] = Meeples::returnToReserve($player, $stable);
        $stableIds[] = $stable['id'];
        break;
      }
    }

    if (!empty($stableIds)) {
      $player->board()->clearStables($stableIds);
    }

    Notifications::returnToReserve($player, $meeples);

    $player->board()->getPastures(true);
    Notifications::updateDropZones($player);

    $player->forceReorganizeIfNeeded();

    $this->insertRewards();
  }

  /* ---- Farm Hand path ---- */

  public function returnFarmHandStable()
  {
    $this->actReturnFarmHandStable();
  }

  public function getReturnFarmHandStableDescription()
  {
    return clienttranslate('Return Farm Hand stable to your supply');
  }

  public function argsReturnFarmHandStable()
  {
    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} may return the Farm Hand stable to their supply (Sample Stable Maker)'),
      'descriptionmyturn' => clienttranslate('${you} may return the Farm Hand stable to your supply (Sample Stable Maker)'),
      'method' => 'returnFarmHandStable',
    ];
  }

  public function actReturnFarmHandStable()
  {
    $player = $this->getPlayer();

    $meeple = Stables::removeFarmHandStable($player);

    $player->board()->getPastures(true);
    Notifications::updateDropZones($player);
    $player->forceReorganizeIfNeeded();

    $this->insertRewards();
  }

  /* ---- Shared rewards ---- */

  private function insertRewards()
  {
    $flow = [
      $this->gainNode([WOOD => 1, GRAIN => 1, FOOD => 1]),
      Utils::wrapOptional([
        'action' => IMPROVEMENT,
        'args' => ['types' => [MINOR]],
      ]),
    ];
    Engine::insertAsChild(['type' => NODE_SEQ, 'childs' => $flow]);
  }
}
