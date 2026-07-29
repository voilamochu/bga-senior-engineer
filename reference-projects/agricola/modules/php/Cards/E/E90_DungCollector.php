<?php
namespace AGR\Cards\E;

use AGR\Core\Globals;
use AGR\Helpers\CardRulings;

class E90_DungCollector extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E90_DungCollector';
    $this->name = clienttranslate('Dung Collector');
    $this->deck = 'E';
    $this->author = 'power_sharpner';
    $this->number = 90;
    $this->category = 'FARMYARD_-_PLOWING';
    $this->desc = [
      clienttranslate('Each time you get 2 or more newborn animals, you can pay 1 <FOOD> to plow 1 field.'),
    ];
    $this->players = '1+';
    $this->implemented = true;

    $this->rulings = CardRulings::fromKeys([
      'NEWBORN_ANIMAL_NEEDS_SPACE',
    ]);
  }

  public function isListeningTo($event)
  {
    return Globals::isBreedPhase() && $this->isActionEvent($event, 'Reorganize') && $event['trigger'] == HARVEST;
  }

  public function onPlayerAfterReorganize($player, $event)
  {
    $createdAnimals = Globals::getNumBredAnimals();
    $silentKills = Globals::getNumSilentKills();

    if ($createdAnimals - $silentKills >= 2) {
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
}
