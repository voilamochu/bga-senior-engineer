<?php
namespace AGR\Cards\D;

class D139_Chairman extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D139_Chairman';
    $this->name = clienttranslate('Chairman');
    $this->deck = 'D';
    $this->number = 139;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time another player uses the __Meeting Place__ action space, both they and you get 1 <FOOD> (before taking the actions). If you use it, you get 1 <FOOD>.'
      ),
    ];
    $this->players = '3+';
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'MeetingPlace', null);
  }

  public function onOpponentPlaceFarmer($player, $event)
  {
    $opponent = $event['pId'];
    
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->gainNode([FOOD => 1], $opponent),
        $this->gainNode([FOOD => 1]),
      ]
    ];
  }
  
  public function onPlayerPlaceFarmer($player, $event)
  {
    return $this->gainNode([FOOD => 1]);
  }
}
