<?php
namespace ARK\Cards\FinalScoring;

class F004_ArchitecturalZoo extends \ARK\Models\FinalScoring
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'F004_ArchitecturalZoo';
    $this->number = 4;
    $this->name = clienttranslate('Architectural Zoo');
    $this->icon = 'ENCLOSURE-REGULAR';
    $this->desc = clienttranslate('Gain <CONSERVATION> for **building** of your zoo.');
    $this->scoreMap = null;
  }

  public function getQuantity()
  {
    $map = $this->getPlayer()->map();
    return [
      $map->areAllTerrainHexConnected(WATER),
      $map->areAllTerrainHexConnected(ROCK),
      $map->countEmptySpaces() === 0,
      $map->areBorderCellsCovered(),
    ];
  }

  public function getScoreBonus()
  {
    $qty = $this->getQuantity();
    $bonus = 0;
    foreach ($qty as $v) {
      if ($v) {
        $bonus++;
      }
    }

    if ($bonus != 0) {
      return [CONSERVATION => $bonus];
    } else {
      return null;
    }
  }
}
