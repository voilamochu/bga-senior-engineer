<?php
namespace AGR\Cards\B;
use AGR\Managers\Players;
use AGR\Core\Engine;
use AGR\Core\Globals;

class B42_ForestInn extends \AGR\Models\PlayerActionCard
{
  protected $type = MINOR;
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B42_ForestInn';
    $this->name = clienttranslate('Forest Inn');
    $this->deck = 'B';
    $this->number = 42;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'This is an action space for all. A player who uses it can exchange 5/7/9 <WOOD> for 8 <WOOD> and 2/4/7 <FOOD>. When another player uses it, they must first pay you 1 <FOOD>.'
      ),
    ];
    $this->cost = [
      CLAY => 1,
      REED => 1,
    ];
    $this->prerequisite = clienttranslate('Play in Round 6 or Before');
    $this->vp = 1;
    $this->flow = [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'method' => 'activate',
        'cardId' => $this->id,
        'args' => [],
      ],
    ];
    $this->isArtifexOrBubulcus = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if (Globals::getTurn() > 6) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function activate()
  {
    $player = Players::getActive()->getId();
    $owner = $this->getPlayer()->getId();
    $usedByOwner = $player == $owner ? 1 : 0;
    $flow = [];

    if (!$usedByOwner) {
      $flow[] = $this->payNode([FOOD => 1], null, 1, $owner, $player);
    }

    $flow[] = [
      'type' => NODE_XOR,
      'pId' => $player,
      'childs' => [
        $this->payGainNode([WOOD => 5], [WOOD => 8, FOOD => 2], null, false, $player),
        $this->payGainNode([WOOD => 7], [WOOD => 8, FOOD => 4], null, false, $player),
        $this->payGainNode([WOOD => 9], [WOOD => 8, FOOD => 7], null, false, $player),
      ],
    ];

    Engine::insertAsChild(['type' => NODE_SEQ, 'pId' => $player, 'childs' => $flow]);
  }
}
