<?php
namespace AGR\Cards\C;
use AGR\Core\Engine;
use AGR\Core\Notifications;

class C148_MudWallower extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C148_MudWallower';
    $this->name = clienttranslate('Mud Wallower');
    $this->deck = 'C';
    $this->number = 148;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Every fourth time you use an accumulation space, you get 1 <PIG>, held by this card.'
      ),
    ];
    $this->players = '4+';
    $this->isCorbariusOrDulcinaria = true;
    $this->animalHolder = true;

    $this->rulings = [
      clienttranslate('This card only holds <PIG> received via this effect.'),
      clienttranslate('The <PIG> may participate in breeding (but the newborn <PIG> must find space elsewhere.)'),
      clienttranslate('If you move a <PIG> from this card, the capacity of this card is permanently reduced.'),
    ];
  }

  public function isListeningTo($event)
  {
    if ($this->isActionEvent($event, 'Exchange')) {
      $this->decreaseRoomAll($this->getPlayer());
      return false;
    }
    return $this->isBeforeCollectEvent($event) || $this->isActionEvent($event, 'Reorganize') || $this->isActionEvent($event,'PlaceFarmer') || $this->isActionEvent($event,'Pay');
  }

  public function onPlayerAfterPay($player, $event)
  {
    $pig_number = 0;
    foreach($event['paying'] as $id => $meeple){
      if($id === 'card'){
        continue;
      }
      if($meeple['type'] == 'pig'){
        $pig_number = $pig_number + 1;
        break;
      }
    }
    if($pig_number == 0){return;}
    return [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'decreaseRoom',
        'args' => [$player],
      ],
    ];
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'increaseCounter',
            'args' => [],
          ]
        ],
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'gainPig',
            'args' => [$player],
          ]
        ],
    ],];
  }
  public function increaseCounter()
  {
    $this->setExtraDatas('counter',$this->getExtraDatas('counter')+1);
    $this->updateInfobox($this->getExtraDatas('counter') . clienttranslate(' / 4'));
  }

  public function gainPig($player)
  {
    if($this->getExtraDatas('counter')>=4){
      $this->setExtraDatas('counter',$this->getExtraDatas('counter')-4);
      $this->updateInfobox($this->getExtraDatas('counter') . clienttranslate(' / 4'));
      $this->setExtraDatas('held',$this->getExtraDatas('held')+1);
      Notifications::updateDropZones($player);
      Engine::insertAsChild([
      'type' => NODE_SEQ,
      'childs' => [
         $this->gainNode([PIG => 1, 'meepleStates' => [PIG => MUD_WALLOWER_PIG]]),
        [
          'action' => REORGANIZE,
          'args' => [
          ]
        ],
     ]]);
    }
  }
  public function decreaseRoom($player)
  {
    $n = 0;
    $player = $this->getPlayer();
    $zones = $player->board()->getAnimalsDropZonesWithAnimals();
    
    foreach ($zones as &$zone) {
      if (isset($zone['card_id']) && $zone['card_id'] == $this->id) {          
        $n = $zone['capacity'] - $zone['animals'];
        break;
      }
    }
    if($n > 0){
      $this->setExtraDatas('held',$this->getExtraDatas('held')-$n);
      Notifications::updateDropZones($player);
    }
  }
  public function decreaseRoomAll($player)
  {
    $n = 0;
    $player = $this->getPlayer();
    $zones = $player->board()->getAnimalsDropZonesWithAnimals();
    
    foreach ($zones as &$zone) {
      if (isset($zone['card_id']) && $zone['card_id'] == $this->id) {          
        $n = $zone['capacity'] - $player->countAnimalsOnBoard()[PIG];
        break;
      }
    }
    if($n > 0){
      $this->setExtraDatas('held',$this->getExtraDatas('held')-$n);
      Notifications::updateDropZones($player);
    }
  }

  public function onBuy($player)
  {
    $this->setExtraDatas('counter',0);
    $this->setExtraDatas('held',0);
    $this->setInfobox($this->getExtraDatas('counter') . clienttranslate(' / 4'));
  }

  public function getInvalidAnimals($zone, $raiseException)
  {
    return [];
  }

  public function onPlayerComputeDropZones($player, &$args)
  {
    $rooms = $this->getExtraDatas('held');

    if ($rooms >0) {
      $args['zones'][] = [
        'type' => 'card',
        'card_id' => $this->id,
        'constraints' => [PIG],
        'capacity' => $rooms,
        'locations' => [['type' => 'card', 'card_id' => $this->id]],
      ];
    }
  }

  public function onPlayerAfterReorganize($player, $event)
  {
    return [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'decreaseRoom',
            'args' => [$player],
          ]
        ];
  }
  public function onPlayerAfterPlaceFarmer($player, $event)
  {
    return [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'decreaseRoom',
            'args' => [$player],
          ]
        ];
  }

}
