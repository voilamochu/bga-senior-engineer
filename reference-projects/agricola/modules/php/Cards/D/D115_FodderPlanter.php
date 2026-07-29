<?php

namespace AGR\Cards\D;

use AGR\Core\Globals;
use AGR\Models\Occupation;
use const HARVEST;
use AGR\Helpers\CardRulings;

class D115_FodderPlanter extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D115_FodderPlanter';
    $this->name = clienttranslate('Fodder Planter');
    $this->deck = 'D';
    $this->number = 115;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In the breeding phase of each harvest, for each newborn animal you get, you can sow crops in exactly 1 field.'
      ),
    ];
    $this->players = '1+';

    $this->rulings = array_merge(
      CardRulings::fromKeys([
      'NEWBORN_ANIMAL_NEEDS_SPACE',
      'WOOD_STONE_NOT_CROPS',
      ]),
    );
  }

  public function isListeningTo($event)
  {
    return Globals::isBreedPhase() && $this->isActionEvent($event, 'Reorganize') && $event['trigger'] == HARVEST;
  }

  public function onPlayerAfterReorganize($player, $event)
  {
    $createdAnimals = Globals::getNumBredAnimals();
    $silentKills = Globals::getNumSilentKills();

    $sow = $createdAnimals - $silentKills;

    if ($sow <= 0) {
      return;
    }

    return [
      'countAsUse' => true,
      'optional' => true,
      'action' => SOW,
      'args' => [
        'max' => $sow,
        'type' => CROPS,
      ],
    ];
  }
}
