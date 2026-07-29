<?php
namespace AGR\Cards\C;

class C47_GardenClaw extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C47_GardenClaw';
    $this->name = clienttranslate('Garden Claw');
    $this->deck = 'C';
    $this->number = 47;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 1 <FOOD> on each remaining round space, up to three times the number of planted fields you have. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->isCorbariusOrDulcinaria = true;
  }

  public function onBuy($player)
  {
    $fields = $player->board()->countPlantedLogicalFields();

    if ($fields > 0) {
      return $this->futureMeeplesNode([FOOD => 1], $fields * 3);
    }
  }
}
