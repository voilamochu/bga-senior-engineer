<?php
namespace AGR\Cards\A;

class A5_ClayEmbankment extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A5_ClayEmbankment';
    $this->name = clienttranslate('Clay Embankment');
    $this->deck = 'A';
    $this->number = 5;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [clienttranslate('You immediately get 1 <CLAY> for every 2 <CLAY> you already have in your supply.')];
    $this->passing = true;
    $this->cost = [
      FOOD => 1,
    ];
  }

  public function onBuy($player)
  {
    $clay = $player->countReserveResource(CLAY);

    if ($clay >= 2) {
      return $this->gainNode([CLAY => intdiv($clay, 2)]);
    }
  }
}
