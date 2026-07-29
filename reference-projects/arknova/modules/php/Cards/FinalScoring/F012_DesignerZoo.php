<?php

namespace ARK\Cards\FinalScoring;

class F012_DesignerZoo extends \ARK\Models\FinalScoring
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'F012_DesignerZoo';
    $this->number = 12;
    $this->name = clienttranslate('Designer Zoo');
    $this->icon = 'DIFFERENT-SHAPES';
    $this->desc = clienttranslate('Gain <CONSERVATION> for **different shaped buildings** in your zoo');
    $this->scoreMap = [4 => 1, 6 => 2, 7 => 3, 8 => 4];
  }

  public function getQuantity()
  {
    $shapes = [];
    foreach ($this->getPlayer()->map()->getBuildings() as $building) {
      foreach (BUILDINGS_BY_SHAPES as $shape => $buildings) {
        if (!in_array($shape, $shapes) && in_array($building['type'], $buildings)) {
          $shapes[] = $shape;
        }
      }
    }

    return count($shapes);
  }
}
