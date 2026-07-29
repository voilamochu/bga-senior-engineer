<?php
namespace AGR\Cards\B;

use AGR\Managers\Players;
use AGR\Managers\Stables as StableManager;
use AGR\Core\Engine;

class B85_FarmHand extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B85_FarmHand';
    $this->name = clienttranslate('Farm Hand');
    $this->deck = 'B';
    $this->number = 85;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Once this game, if you have 4 field tiles in a 2x2, you can build a stable in the center of the 2x2 during a __Build Stables__ action. This stable provides room for a person but not animals.'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;

    $this->rulings = [
      clienttranslate('You can only build the Farm Hand stable during a __Build Stables__ action exactly, which does not include cards such as __Lazybones__ (E148) or __Stable Planner__ (A089) that only let you build a stable.'),
      clienttranslate('You can return the Farm Hand stable using __Sample Stable Maker__ (D102) or __Lumber Pile__ (E076). If you do, any person who was living in the Farm Hand stable moves into your other rooms.'),
    ];
  }

  public static function wrapStablesWithFarmHandIfPlayed($player, $stablesNode)
  {
    if (!$player->hasPlayedCard('B85_FarmHand')) {
      return $stablesNode;
    }

    $stablesNode['pId'] = $stablesNode['pId'] ?? $player->getId();
    $pId = $stablesNode['pId'];
    $baseArgs = $stablesNode['args'] ?? [];

    $selectionNode = $stablesNode;
    $selectionNode['args'] = array_merge($baseArgs, [
      'selectionOnly' => true,
    ]);

    $settlementNode = $stablesNode;
    $settlementNode['args'] = array_merge($baseArgs, [
      'settlementOnly' => true,
    ]);
    // The second SEQ child (settlement) only becomes truly doable after OR selection.
    $settlementNode['willBeDoable'] = true;

    return [
      'type' => NODE_SEQ,
      'pId' => $pId,
      'customDescription' => clienttranslate('Build stables'),
      'childs' => [
        [
          'type' => NODE_OR,
          'pId' => $pId,
          'customDescription' => clienttranslate('Build stables'),
          'childs' => [
            [
              'action' => SPECIAL_EFFECT,
              'pId' => $pId,
              'args' => [
                'cardId' => 'B85_FarmHand',
                'method' => 'FarmHandStable',
                'args' => [$baseArgs],
              ],
            ],
            $selectionNode,
          ],
        ],
        $settlementNode,
      ],
    ];
  }

  // Once-per-game flag
  public function hasBuiltFarmHandStable()
  {
    return $this->getExtraDatas('farmHand_built') == 1;
  }

  public function setHasBuiltFarmHandStable($built)
  {
    $this->setExtraDatas('farmHand_built', $built ? 1 : 0);
  }

  // Has the Farm Hand stable already been paid for?
  public function isFarmHandPaid()
  {
    return $this->getExtraDatas('farmHand_paid') == 1;
  }

  public function setFarmHandPaid($paid)
  {
    $this->setExtraDatas('farmHand_paid', $paid ? 1 : 0);
  }

  public function isFarmHandStableDoable($player = null, $ignoreResources = false, $stablesArgs = [])
  {
    if ($player === null || !is_object($player)) {
      $player = Players::getActive();
    }

    if ($this->hasBuiltFarmHandStable()) {
      return false;
    }

    if (!$player->canUseFarmHandStable()) {
      return false;
    }

    if ($ignoreResources) {
      return true;
    }

    if (!is_array($stablesArgs)) {
      return false;
    }

    $pending = $this->getExtraDatas('farmHandDeferredStables');
    $normalCount = (is_array($pending) && is_array($pending['stables'] ?? null)) ? count($pending['stables']) : 0;

    $ctx = Engine::buildTree([
      'action' => STABLES,
      'pId' => $player->getId(),
      'args' => $stablesArgs,
    ]);

    $action = new \AGR\Actions\Stables($ctx);
    return $action->canAffordStablePlan($player, $normalCount, 1);
  }

  public function getFarmHandStableDescription()
  {
    $player = Players::getActive();

    // If the effect is not usable right now, don't contribute any text
    // to the composite button label.
    if ($this->hasBuiltFarmHandStable() || !$player->canUseFarmHandStable()) {
      return '';
    }

    return clienttranslate('Build Farm Hand stable');
  }

  public function argsFarmHandStable()
  {
    $player = Players::getActive();

    $desc = clienttranslate('${actplayer} may choose the top-left field of a 2x2 block of fields for the Farm Hand stable');
    $descMyTurn = clienttranslate('Choose the top-left field of a 2x2 block of fields for the Farm Hand stable');

    // If the effect is not usable right now, return no zones.
    if (!$player->canUseFarmHandStable() || $this->hasBuiltFarmHandStable()) {
      return [
        'cardId' => $this->id,
        'description' => $desc,
        'descriptionmyturn' => $descMyTurn,
        'zones' => [],
      ];
    }

    return [
      'cardId' => $this->id,
      'description' => $desc,
      'descriptionmyturn' => $descMyTurn,
      'zones' => $player->board()->getFarmHandCandidates(),
    ];
  }

  // For Farm Expansion only: wrap any STABLES leaf into the Farm Hand OR + settlement flow.
  public function onPlayerComputePlaceFarmerFlow($player, &$args)
  {
    if (($args['actionCardId'] ?? null) !== 'ActionFarmExpansion') {
      return;
    }

    if (!isset($args['flow']) || !is_array($args['flow'])) {
      return;
    }

    $this->wrapStablesWithFarmHand($args['flow'], $player);
  }

  private function wrapStablesWithFarmHand(&$node, $player)
  {
    if (!is_array($node)) {
      return;
    }

    // If this node is a STABLES leaf, wrap it.
    if (($node['action'] ?? null) === STABLES) {
      $node = self::wrapStablesWithFarmHandIfPlayed($player, $node);
      return;
    }

    // Otherwise recurse into children
    if (isset($node['childs']) && is_array($node['childs'])) {
      foreach ($node['childs'] as &$child) {
        $this->wrapStablesWithFarmHand($child, $player);
      }
      unset($child);
    }
  }

  /**
   * Handle the player's choice from the SPECIAL_EFFECT UI.
   * We only validate and remember the chosen position here.
   * The actual stable creation and payment will be handled in the Stables action.
   *
   * @param array $zones List with one element: ['x' => ..., 'y' => ...] top-left of 2x2.
   */
  public function actFarmHandStable($zones)
  {
    $player = Players::getActive();
    $board = $player->board();

    if ($this->hasBuiltFarmHandStable()) {
      throw new \BgaUserException(clienttranslate('You have already used Farm Hand this game.'));
    }

    if (!$player->canUseFarmHandStable()) {
      throw new \BgaUserException(
        clienttranslate('You cannot use Farm Hand right now.')
      );
    }

    if (empty($zones) || !is_array($zones[0])) {
      throw new \BgaUserException(clienttranslate('You must choose a place for the Farm Hand stable.'));
    }

    $pos = $zones[0];
    $x = $pos['x'];
    $y = $pos['y'];

    // Validate against current candidates
    $valid = false;
    foreach ($board->getFarmHandCandidates() as $c) {
      if ($c['x'] == $x && $c['y'] == $y) {
        $valid = true;
        break;
      }
    }

    if (!$valid) {
      throw new \BgaUserException(clienttranslate('Invalid Farm Hand stable position.'));
    }

    $this->setHasBuiltFarmHandStable(true);
    $this->setFarmHandPaid(false);

    // Immediately create the special Farm Hand stable in the DB
    // This uses the special "farmhand" location so animal logic ignores it.
    StableManager::createFarmHandStable($player->getId(), $x, $y);
  }
}

