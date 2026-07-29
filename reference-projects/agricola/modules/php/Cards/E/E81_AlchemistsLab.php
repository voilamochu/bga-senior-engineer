<?php
namespace AGR\Cards\E;
use AGR\Managers\Players;
use AGR\Core\Engine;

class E81_AlchemistsLab extends \AGR\Models\PlayerActionCard
{
  protected $type = MINOR;
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E81_AlchemistsLab';
    $this->name = clienttranslate('Alchemists Lab');
    $this->deck = 'E';
    $this->author = 'oxmond';
    $this->number = 81;
    $this->category = 'BUILDING_RESOURCES_-_ALL';
    $this->desc = [
      clienttranslate(
        'This card is an action space for all. A player who uses it gets 1 building resource of each type they already have. If another player uses it, they must first pay you 1 <FOOD>.'
      ),
    ];
    $this->vp = 1;
    $this->prerequisite = clienttranslate('3 Occupations');
    $this->occupationPrerequisites = ['min' => 3];
    $this->flow = [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'method' => 'activate',
        'cardId' => $this->id,
        'args' => [],
      ],
    ];
  }

  public function getResources($player)
  {
    $resources = [];

    foreach ([WOOD, CLAY, REED, STONE] as $res) {
      if ($player->countReserveResource($res) > 0) {
        $resources[$res] = 1;
      }
    }

    return $resources;    
  }

  public function activate()
  {
    $player = Players::getActive();
    $owner = $this->getPlayer()->getId();
    $usedByOwner = $player->getId() == $owner ? 1 : 0;
    $flow = [];

    if (!$usedByOwner) {
      $flow[] = $this->payNode([FOOD => 1], null, 1, $owner);
    }

    $flow[] = $this->gainNode($this->getResources($player), $player->getId());

    Engine::insertAsChild(['type' => NODE_SEQ, 'childs' => $flow]);
  }
}