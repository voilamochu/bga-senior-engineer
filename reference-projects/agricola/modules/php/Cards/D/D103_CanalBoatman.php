<?php
namespace AGR\Cards\D;
use AGR\Managers\PlayerCards;
use AGR\Managers\Farmers;
use AGR\Core\Notifications;

class D103_CanalBoatman extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D103_CanalBoatman';
    $this->name = clienttranslate('Canal Boatman');
    $this->deck = 'D';
    $this->number = 103;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use __Fishing__ or __Reed Bank__, you can pay 1 <FOOD> to immediately place another person on this card. If you do, you get your choice of 3 <STONE> or 1 <GRAIN> plus 1 <VEGETABLE>.'
      ),
    ];
    $this->players = '1+';
    $this->holder = true;
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer') &&
      ($event['actionCardType'] == 'Fishing' || $event['actionCardType'] == 'ReedBank');
  }

  public function onPlayerAfterPlaceFarmer($player, $event)
  {
    $space = $event['actionCardId'];
    $farmer = Farmers::getOnCard($space, $player->getId())->last();

    if (is_null($player->getNextFarmerAvailable())) {
      return;
    }

    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'countAsUse' => true,
      'childs' => [
        $this->payNode([FOOD => 1]),
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'placeFarmerOnCard',
            'args' => [],
          ],
        ],
        [
          'type' => NODE_XOR,
          'childs' => [
            $this->gainNode([STONE => 3]),
            $this->gainNode([GRAIN => 1, VEGETABLE => 1]),
          ],
        ],
      ],
    ];
  }

  public function getPlaceFarmerOncardDescription()
  {
    return [
      'log' => clienttranslate('Place your next farmer on ${action_space}'),
      'args' => [
        'i18n' => ['action_space'],
        'action_space' => clienttranslate($this->name),
      ],
    ];      
  }

  public function placeFarmerOnCard()
  {
    $player = $this->getPlayer();
    $fId = $player->moveNextFarmerAvailable($this->id);
    Notifications::placeFarmer($player, $fId, PlayerCards::get($this->id));
  }
}
