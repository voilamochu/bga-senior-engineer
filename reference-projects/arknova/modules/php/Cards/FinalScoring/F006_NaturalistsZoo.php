<?php
namespace ARK\Cards\FinalScoring;

class F006_NaturalistsZoo extends \ARK\Models\FinalScoring
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'F006_NaturalistsZoo';
    $this->number = 6;
    $this->name = clienttranslate('Naturalists\' Zoo');
    $this->icon = 'ENCLOSURE-EMPTY';
    $this->desc = clienttranslate('Gain <CONSERVATION> for **empty building spaces** in your zoo');
    $this->scoreMap = [6 => 1, 12 => 2, 18 => 3, 24 => 4];
  }

  public function getQuantity()
  {
    return $this->getPlayer()
      ->map()
      ->countEmptySpaces();
  }
}
