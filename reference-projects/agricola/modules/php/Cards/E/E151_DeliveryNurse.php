<?php
namespace AGR\Cards\E;

use AGR\Helpers\Utils;

class E151_DeliveryNurse extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E151_DeliveryNurse';
    $this->name = clienttranslate('Delivery Nurse');
    $this->deck = 'E';
    $this->author = 'beso';
    $this->number = 151;
    $this->category = 'ACTION';
    $this->desc = [
      clienttranslate(
        'Once this game, if you have all types of animals, you can use any __Wish for Children__ action space even without room.'
      ),
    ];
    $this->players = '4+';
  }

  public function onPlayerComputePlaceFarmerFlow($player, &$args)
  {
    if ($args['actionCardType'] == 'WishChildren' && $player->checkHaveAllTypeAnimals() && !$this->isFlagged()) {
      $originFlow = $args['flow'];
      $needRoom = Utils::checkNeedRoomConstraintRecursively($originFlow);
      if ($needRoom) {
        if ($player->countFarmers() >= $player->countRooms() + $player->countExtraRoom()) {
          Utils::changeToNoNeedRoomRecursively($originFlow);
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
