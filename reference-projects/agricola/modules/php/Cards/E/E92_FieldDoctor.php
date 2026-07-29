<?php
namespace AGR\Cards\E;

use AGR\Helpers\Utils;

class E92_FieldDoctor extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E92_FieldDoctor';
    $this->name = clienttranslate('Field Doctor');
    $this->deck = 'E';
    $this->author = 'chris';
    $this->number = 92;
    $this->category = 'ACTION_-_FAMILY_GROWTH';
    $this->desc = [
      clienttranslate(
        'Once this game, if you live in a house with exactly 2 rooms surrounded by 4 field tiles, you can use any __Wish for Children__ action space even without room.'
      ),
    ];
    $this->players = '1+';
    $this->holder = true;
  }

  public function checkRoomsSurrondedByFields($player)
  {
    if ($player->countRooms() != 2) {
      return false;
    }
    $positions = [
      ['x' => 3, 'y' => 5],
      ['x' => 3, 'y' => 3],
      ['x' => 3, 'y' => 1],
      ['x' => 1, 'y' => 1],
    ];
    foreach ($positions as $position) {
      if (!$player->board()->containsField($position['x'], $position['y'])) {
        return false;
      }
    }
    return true;
  }

  public function onPlayerComputePlaceFarmerFlow($player, &$args)
  {
    if ($args['actionCardType'] == 'WishChildren' && $this->checkRoomsSurrondedByFields($player) && !$this->isFlagged()) {
      $originFlow = $args['flow'];
      $needRoom = Utils::checkNeedRoomConstraintRecursively($originFlow);
      if ($needRoom) {
        if ($player->countFarmers() >= $player->countRooms() + $player->countExtraRoom()) {
          Utils::changeToNoNeedRoomRecursively($originFlow, false);
          $args['flow'] = [
            'type' => NODE_SEQ,
            'childs' => [
              $originFlow,
              $this->flagCardNode(),
            ]
          ];
        }
      }
    }
  }
}
