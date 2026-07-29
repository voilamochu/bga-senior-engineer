<?php
namespace AGR\Cards\C;

class C17_NewlyPlowedField extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C17_NewlyPlowedField';
    $this->name = clienttranslate('Newly-Plowed Field');
    $this->deck = 'C';
    $this->number = 17;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you can immediately plow 1 field, which needs not be adjacent to another field.'
      ),
    ];
    $this->prerequisite = clienttranslate('Exactly 3 Field Tiles');
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $fields = $player->board()->getFieldTiles();
    if (count($fields) != 3) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    return [
      'action' => PLOW,
      'optional' => true,
      'args' => ['unrestricted' => true],
    ];
  }
}
