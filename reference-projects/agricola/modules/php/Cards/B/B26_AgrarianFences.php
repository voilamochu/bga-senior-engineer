<?php
namespace AGR\Cards\B;

use AGR\Actions\Exchange;
use AGR\Core\Engine;
use AGR\Helpers\Utils;
use AGR\Managers\ActionCards;

class B26_AgrarianFences extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B26_AgrarianFences';
    $this->name = clienttranslate('Agrarian Fences');
    $this->deck = 'B';
    $this->number = 26;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Grain Utilization__ action space, you can take a __Build Fences__ action instead of one of the two actions provide by the action space.'
      ),
    ];
    $this->isArtifexOrBubulcus = true;

    $this->rulings = [
      clienttranslate('You can take a __Build Fences__ action in this way without the ability to sow or bake.'),
    ];
  }

  public function isFencingDoable()
  {
    $player = $this->getPlayer();

    $fencingNode = [
      'action' => FENCING,
      'args' => [
        'costs' => Utils::formatCost([WOOD => 1]),
      ],
    ];
    $tree = Engine::buildTree($fencingNode);

    return $tree->isDoable($player,false);
  }

  public function onPlayerComputeArgsPlaceFarmer($player)
  {
    $added = [];
    $cId = 'ActionGrainUtilization';
    $space = ActionCards::get($cId);

    if ($space->isVisible() && $this->isFencingDoable()) {
      $added[] = ['actionCardId' => $cId, 'ignoreDoable' => true];
    }

    return $added;
  }

  public function onPlayerComputePlaceFarmerFlow($player, &$args)
  {
    if ($args['actionCardType'] != 'GrainUtilization') {
      return;
    }

    $fencing = [
      'action' => FENCING,
      'source' => $this->name,
      'args' => ['costs' => Utils::formatCost([WOOD => 1])],
    ];

    $sow = ['action' => SOW];

    $bake = [
      'action' => EXCHANGE,
      'cardId' => 'ActionGrainUtilization',
      'args' => [
        'trigger' => BREAD,
      ],
    ];

    $args['flow'] = [
      'type' => NODE_XOR,
      'pId' => $player->getId(),
      'childs' => [
        $args['flow'],
        [
          'type' => NODE_OR,
          'childs' => [$fencing, $sow],
        ],
        [
          'type' => NODE_OR,
          'childs' => [$fencing, $bake],
        ],
      ],
    ];
  }
}
