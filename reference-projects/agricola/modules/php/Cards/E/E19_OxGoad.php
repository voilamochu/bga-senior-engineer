<?php
namespace AGR\Cards\E;


class E19_OxGoad extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E19_OxGoad';
    $this->name = clienttranslate('Ox Goad');
    $this->deck = 'E';
    $this->author = 'beso';
    $this->number = 19;
    $this->category = 'FARMYARD_-_PLOWING';
    $this->desc = [
      clienttranslate(
        'Each time after you use the __Cattle Market__ accumulation space, you can pay 2 <FOOD> to plow 1 field.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 1,
    ];
    $this->prerequisite = clienttranslate('3 Occupations');
    $this->occupationPrerequisites = ['min' => 3];
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer') && $event['actionCardType'] == 'CattleMarket'; 
  }

  public function onPlayerAfterPlaceFarmer($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        $this->payNode([FOOD => 2]),
        [
          'action' => PLOW,
          'cardId' => $this->id,
        ],
      ],
    ];
  }
}
