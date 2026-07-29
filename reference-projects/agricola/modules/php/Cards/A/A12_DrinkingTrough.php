<?php
namespace AGR\Cards\A;

class A12_DrinkingTrough extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A12_DrinkingTrough';
    $this->name = clienttranslate('Drinking Trough');
    $this->deck = 'A';
    $this->number = 12;
    $this->category = FARM_PLANNER;
    $this->desc = [clienttranslate('Each of your pastures (with or without a stable) can hold up to 2 more animals.')];
    $this->cost = [
      CLAY => 1,
    ];

    $this->rulings = [
      clienttranslate('Cards holding animals are not pastures unless explicitly stated.'),
    ];
  }

  protected function onBuy($player)
  {
    $this->refreshDropZones();
  }

  public function onPlayerComputeDropZones($player, &$args)
  {
    foreach ($args['zones'] as &$zone) {
      if ($zone['type'] == 'pasture') {
        $zone['capacity'] += 2;
      }
    }
  }
}
