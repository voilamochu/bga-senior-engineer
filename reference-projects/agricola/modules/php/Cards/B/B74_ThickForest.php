<?php
namespace AGR\Cards\B;

class B74_ThickForest extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B74_ThickForest';
    $this->name = clienttranslate('Thick Forest');
    $this->deck = 'B';
    $this->number = 74;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 1 <WOOD> on each remaining even-numbered round space. At the start of these rounds, you get the <WOOD>.'
      ),
    ];
    $this->costText = clienttranslate('5 Clay in Your Supply');
  }

  public function onBuy($player)
  {
    return $this->futureMeeplesNode([WOOD => 1], [2, 4, 6, 8, 10, 12, 14]);
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if ($player->countReserveResource(CLAY) < 5) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }
}
