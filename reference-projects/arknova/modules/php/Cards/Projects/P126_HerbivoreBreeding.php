<?php
namespace ARK\Cards\Projects;

class P126_HerbivoreBreeding extends \ARK\Models\Projects\BreedProject
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'P126_HerbivoreBreeding';
    $this->number = 126;
    $this->name = clienttranslate('Herbivore breeding program');
    $this->desc = clienttranslate('Requires 1 **herbivore** and a **partner zoo** of the same continent.');
    $this->icon = \HERBIVORE;
  }
}
