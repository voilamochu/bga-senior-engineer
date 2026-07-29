<?php
namespace AGR\Cards\E;

use AGR\Managers\PlayerCards;

class E128_Saddler extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E128_Saddler';
    $this->name = clienttranslate('Saddler');
    $this->deck = 'E';
    $this->author = 'beso';
    $this->number = 128;
    $this->category = 'FARMYARD';
    $this->desc = [
      clienttranslate('Each time after you build a major improvement, you can pay 1 <FOOD> to plow 1 field.'),
    ];
    $this->players = '3+';
    $this->implemented = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Improvement') &&
      PlayerCards::get($event['cardId'])->hasType(MAJOR);

  }

  public function onPlayerAfterImprovement($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        $this->payNode([FOOD => 1]),
        [
          'action' => PLOW,
          'cardId' => $this->id,
        ],
      ],
    ];
  }
}
