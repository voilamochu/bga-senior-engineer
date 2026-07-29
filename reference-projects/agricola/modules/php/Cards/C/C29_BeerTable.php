<?php
namespace AGR\Cards\C;
use AGR\Core\Engine;
use AGR\Managers\Players;

class C29_BeerTable extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C29_BeerTable';
    $this->name = clienttranslate('Beer Table');
    $this->deck = 'C';
    $this->number = 29;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'At the end of the field phase of each harvest, you can pay 1 <GRAIN> from your supply to get 2 bonus <SCORE>. If you do, all other players get 1 <FOOD> each.'
      ),
    ];
    $this->cost = [
      WOOD => 2,
    ];
    $this->extraVp = true;
    $this->prerequisite = clienttranslate('No Grain in Your Supply');
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if ($player->countReserveResource(GRAIN) > 0) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }
  
  public function preFeedingGoodsWanted()
  {
    return [GRAIN];
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'EndHarvestFieldPhase';
  }

  public function onPlayerEndHarvestFieldPhase($player)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'countAsUse' => true,
      'childs' => [
        $this->payGainNode([GRAIN => 1], [SCORE => 2], null, false),
        [
          'action' => SPECIAL_EFFECT,
          'auto' => true,
          'args' => [
            'cardId' => $this->id,
            'method' => 'giveFood',
          ],
        ],
      ]
    ];
  }

  public function getGiveFoodDescription()
  {
    return [
      'log' => clienttranslate('${resources_desc} for other players'),
      'args' => [
        'resources_desc' => '1 <FOOD>',
      ],
    ];
  }
  
  public function giveFood()
  {
    $player = $this->getPlayer();
    $flow = [];
    foreach (Players::getAll() as $pId2 => $player2) {
      if ($this->pId == $pId2) {
        continue;
      }
      $flow[] = $this->gainNode([FOOD => 1], $pId2);
    }

    if ($flow != []) {
      Engine::insertAsChild(['type' => NODE_SEQ, 'childs' => $flow]);
    }
  }
}
