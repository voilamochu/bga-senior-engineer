<?php
namespace AGR\Cards\E;

use AGR\Managers\Meeples;
use AGR\Managers\Stables;
use AGR\Core\Engine;
use AGR\Core\Notifications;

class E76_LumberPile extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E76_LumberPile';
    $this->name = clienttranslate('Lumber Pile');
    $this->deck = 'E';
    $this->author = 'henrysunset';
    $this->number = 76;
    $this->category = 'BUILDING_RESOURCES_-_WOOD_OR_CLAY';
    $this->desc = [
      clienttranslate(
        'When you play this card, you can immediately return up to 3 <STABLE> from your farmyard board to your supply and get 3 <WOOD> for each.'
      ),
    ];
  }

  public function onBuy($player)
  {
    $hasNormal = !empty($player->board()->getStables());
    $hasFarmHand = ($player->board()->getFarmHandTopLeft() !== null);

    // Nothing to return at all
    if (!$hasNormal && !$hasFarmHand) {
      return null;
    }

    // If Farm Hand exists, first ask: Return FH or Pass (into normal Lumber Pile flow)
    if ($hasFarmHand) {
      return [
        'action' => SPECIAL_EFFECT,
        'optional' => false,
        'args' => [
          'cardId' => $this->id,
          'method' => 'chooseFarmHandFirst',
        ],
      ];
    }

    // Otherwise: current behaviour (optional selection up to 3)
    return [
      'action' => SPECIAL_EFFECT,
      'optional' => true,
      'args' => [
        'cardId' => $this->id,
        'method' => 'returnStables',
      ],
    ];
  }

  /* -----------------------------
   * Helper: normal stable zones
   * ----------------------------- */
  private function getNormalStableZones($player)
  {
    return array_map(function ($s) {
      return ['x' => $s['x'], 'y' => $s['y']];
    }, $player->board()->getStables());
  }

  /* -----------------------------
   * Step 0: chooser when FH exists
   * ----------------------------- */

  public function getChooseFarmHandFirstDescription()
  {
    return clienttranslate('Return Farm Hand stable or pass');
  }

  public function argsChooseFarmHandFirst()
  {
    $player = $this->getPlayer();
    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} may return stables to their supply (Lumber Pile)'),
      'descriptionmyturn' => clienttranslate('${you} may return stables to your supply (Lumber Pile)'),
      'method' => 'chooseFarmHandFirst',
      'hasNormalStables' => !empty($player->board()->getStables()),
    ];
  }

  public function actChooseFarmHandFirst($choice)
  {
    $player = $this->getPlayer();

    if ($choice === 'pass') {
      // Go into the existing optional selection flow (up to 3 normal stables)
      Engine::insertAsChild([
        'action' => SPECIAL_EFFECT,
        'optional' => true,
        'args' => [
          'cardId' => $this->id,
          'method' => 'returnStables',
        ],
      ]);
      return;
    }

    if ($choice !== 'farmhand') {
      throw new \BgaUserException(clienttranslate('Invalid choice.'));
    }

    // Return FH stable
    $meeple = Stables::removeFarmHandStable($player);

    // Gain wood for the FH stable
    $flow = [
      $this->gainNode([WOOD => 3]),
    ];

    // If normal stables remain, offer optional selection of up to 2 more
    if (!empty($player->board()->getStables())) {
      $flow[] = [
        'action' => SPECIAL_EFFECT,
        'optional' => true,
        'args' => [
          'cardId' => $this->id,
          'method' => 'returnMoreStables', // up to 2
        ],
      ];
    }

    Engine::insertAsChild(['type' => NODE_SEQ, 'childs' => $flow]);
  }

  /* -----------------------------
   * Existing flow: return up to 3
   * ----------------------------- */

  public function getReturnStablesDescription()
  {
    return clienttranslate('Return stables to your supply');
  }

  public function argsReturnStables()
  {
    $player = $this->getPlayer();
    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} may return up to 3 stables to their supply (Lumber Pile)'),
      'descriptionmyturn' => clienttranslate('${you} may return up to 3 stables to your supply (Lumber Pile)'),
      'zones' => $this->getNormalStableZones($player),
      'method' => 'returnStables',
      'max' => 3,
    ];
  }

  public function actReturnStables($zones)
  {
    $this->returnNormalStablesAndGainWood($zones, 3);
  }

  /* -----------------------------
   * After FH: return up to 2 more
   * ----------------------------- */

  public function getReturnMoreStablesDescription()
  {
    return clienttranslate('Return stables to your supply');
  }

  public function argsReturnMoreStables()
  {
    $player = $this->getPlayer();
    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} may return up to 2 more stables to their supply (Lumber Pile)'),
      'descriptionmyturn' => clienttranslate('${you} may return up to 2 more stables to your supply (Lumber Pile)'),
      'zones' => $this->getNormalStableZones($player),
      'method' => 'returnMoreStables',
      'max' => 2,
    ];
  }

  public function actReturnMoreStables($zones)
  {
    $this->returnNormalStablesAndGainWood($zones, 2);
  }

  /* -----------------------------
   * Shared implementation
   * ----------------------------- */

  private function returnNormalStablesAndGainWood($zones, $max)
  {
    $player = $this->getPlayer();
    $stables = $player->board()->getStables();

    if (!is_array($zones)) {
      throw new \BgaUserException(clienttranslate('Invalid selection.'));
    }
    if (count($zones) > $max) {
      throw new \BgaUserException(clienttranslate('You selected too many stables.'));
    }

    $meeples = [];
    $stableIds = [];

    foreach ($stables as $stable) {
      $pos = ['x' => $stable['x'], 'y' => $stable['y']];
      if (in_array($pos, $zones)) {
        $meeples[] = Meeples::returnToReserve($player, $stable);
        $stableIds[] = $stable['id'];
      }
    }

    if (!empty($meeples)) {
      Notifications::returnToReserve($player, $meeples);
    }

    if (!empty($stableIds)) {
      $player->board()->clearStables($stableIds);
    }

    $player->board()->getPastures(true);
    Notifications::updateDropZones($player);

    $player->forceReorganizeIfNeeded();

    Engine::insertAsChild([
      'type' => NODE_SEQ,
      'childs' => [
        $this->gainNode([WOOD => count($zones) * 3]),
      ],
    ]);
  }
}
