<?php
namespace AGR\Cards\C;

use AGR\Core\Globals;

class C20_MolePlow extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C20_MolePlow';
    $this->name = clienttranslate('Mole Plow');
    $this->deck = 'C';
    $this->number = 20;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Farmland__ or __Cultivation__ action space, you can plow 1 additional field.'
      ),
    ];
    $this->cost = [
      WOOD => 3,
      FOOD => 1,
    ];
    $this->prerequisite = clienttranslate('Play in Round 9 or Later');
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $turn = Globals::getTurn();
    if ($turn < 9) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'Farmland') || $this->isActionCardEvent($event, 'Cultivation');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    return [
      'action' => PLOW,
      'optional' => true,
      'args' => ['source' => $this->name, 'cardId' => $this->id],
    ];
  }
}
