<?php

namespace AGR\Cards\C;

use AGR\Core\Globals;
use AGR\Models\MinorImprovement;
use AGR\Helpers\CardRulings;

class C71_Slurry extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C71_Slurry';
    $this->name = clienttranslate('Slurry');
    $this->deck = 'C';
    $this->number = 71;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In the breeding phase of each harvest, if you get newborn animals of at least two types, you also get a __Sow__ action.'
      ),
    ];
    $this->isCorbariusOrDulcinaria = true;

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
      return ['optional' => true, 'action' => SOW];
    }
  }
}
