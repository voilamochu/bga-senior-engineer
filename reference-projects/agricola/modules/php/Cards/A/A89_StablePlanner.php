<?php
namespace AGR\Cards\A;
use AGR\Managers\Meeples;
use AGR\Core\Notifications;
use AGR\Core\Globals;

class A89_StablePlanner extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A89_StablePlanner';
    $this->name = clienttranslate('Stable Planner');
    $this->deck = 'A';
    $this->number = 89;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Add 3, 6, and 9 to the current round. You can place 1 stable on each corresponding round space. At the start of these rounds (not earlier), you can build the stable at no cost.'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;

    $this->rulings = [
      clienttranslate('Stables built this way are not built on your turn and do not trigger __Stable Tree__ (A074) or __Farmyard Manure__ (A043).'),
    ];
  }

  public function onBuy($player)
  {
    $childs = [];
    $turn = Globals::getTurn();
    $i = 0;

    foreach ([3, 6, 9] as $turnInc) {
      if ($turn + $turnInc > 14) {
        break;
      }
      $i++;

      $childs[] = [
        'action' => SPECIAL_EFFECT,
        'args' => [
          'cardId' => $this->id,
          'method' => 'placeFutureStables',
          'args' => [$i],
        ],
      ];
    }

    if ($childs == []) {
      return;
    }

    return [
      'type' => NODE_XOR,
      'optional' => true,
      'childs' => $childs,
    ];
  }

  public function getPlaceFutureStablesDescription($n)
  {
    $player = $this->getPlayer();

    return [
      'log' => clienttranslate('Place ${resources_desc} for future rounds'),
      'args' => [
        'resources_desc' => $n . ' <' . strtoupper(STABLE) . '>',
        'playerColor' => $player ? $player->getColor() : null,
      ],
    ];
  }

  public function placeFutureStables($n)
  {
    $player = $this->getPlayer();
    $stables = $player->getStablesInReserve();
    $meepleIds = [];
    $turns = [];

    $i = 1;
    foreach ($stables as $stable) {
      if ($i > $n) {
        break;
      }

      $turn = Globals::getTurn() + ($i*3);
      $meepleIds[] = $stable['id'];
      $turns[] = $turn;

      Meeples::moveToCoords($stable['id'], 'turn_' . $turn);
      $i++;
    }

    $meeples = Meeples::getMany($meepleIds);
    Notifications::placeMeeplesForFuture($player, [STABLE => 1], $turns, $meeples);
  }
}
