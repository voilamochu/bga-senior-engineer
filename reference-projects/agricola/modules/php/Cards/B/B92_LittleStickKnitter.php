<?php
namespace AGR\Cards\B;

use AGR\Core\Globals;
use AGR\Helpers\Utils;
use AGR\Managers\PlayerCards;

class B92_LittleStickKnitter extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B92_LittleStickKnitter';
    $this->name = clienttranslate('Little Stick Knitter');
    $this->deck = 'B';
    $this->number = 92;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'From Round 5 on, each time you use the __Sheep Market__ accumulation space, you can also take a __Family Growth with Room Only__ action.'
      ),
    ];
    $this->players = '1+';
    $this->holder = true;
    $this->isArtifexOrBubulcus = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'SheepMarket');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    if (Globals::getTurn() >= 5) {
      $flow = [
        'action' => WISHCHILDREN,
        'cardId' => $this->id,
        'optional' => true,
        'args' => ['constraints' => ['freeRoom'], 'cardLocation' => $this->id],
        'pId' => $this->pId,
        'source' => $this->name,
      ];
      if (
        $player->hasPlayedCard('E151_DeliveryNurse') &&
        !PlayerCards::get('E151_DeliveryNurse')->isFlagged() &&
        $player->checkHaveAllTypeAnimals() &&
        $player->countFarmers() >= $player->countRooms() + $player->countExtraRoom()
      ) {
        $originFlow = $flow;
        Utils::changeToNoNeedRoomRecursively($originFlow);
        $flow = [
          'type' => NODE_SEQ,
          'optional' => true,
          'childs' => [
            PlayerCards::get('E151_DeliveryNurse')->flagCardNode(),
            $originFlow,
          ],
        ];
      }
      return $flow;
    }
  }
}