<?php
namespace AGR\Cards\E;

use AGR\Managers\PlayerCards;

class E18_SeedAlmanac extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E18_SeedAlmanac';
    $this->name = clienttranslate('Seed Almanac');
    $this->deck = 'E';
    $this->author = 'nwoll';
    $this->number = 18;
    $this->category = 'FARMYARD_-_PLOWING';
    $this->desc = [
      clienttranslate(
        'Each time after you play a minor improvement after this one, you can pay 1 <FOOD> to plow 1 field.'
      ),
    ];
    $this->cost = [
      REED => 1,
    ];
    $this->prerequisite = clienttranslate('4 Occupations');
    $this->occupationPrerequisites = ['min' => 4];
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Improvement') && PlayerCards::get($event['cardId'])->getType() == MINOR;
  }

  public function onPlayerAfterImprovement($player, $event)
  {
    if($event['cardId'] == 'E18_SeedAlmanac'){return;}
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
