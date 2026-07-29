<?php

namespace AGR\Cards\C;

use AGR\Core\Globals;
use AGR\Models\Occupation;

class C124_StoneImporter extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C124_StoneImporter';
    $this->name = clienttranslate('Stone Importer');
    $this->deck = 'C';
    $this->number = 124;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In the breeding phase of the 1st/2nd/3rd/4th/5th/6th harvest, you can use this card to buy exactly 2 <STONE> for 2/2/3/3/4/1 <FOOD>.'
      ),
    ];
    $this->players = '1+';
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'EndHarvest';
  }

  public function onPlayerEndHarvest($player)
  {
    $turn = Globals::getTurn();
    $map = [
      4 => 2,
      7 => 2,
      9 => 3,
      11 => 3,
      13 => 4,
      14 => 1,
    ];

    $harvest = $map[$turn];
    return $this->payGainNode([FOOD => $harvest], [STONE => 2]);
  }
}
