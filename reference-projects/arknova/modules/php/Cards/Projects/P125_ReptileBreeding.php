<?php
namespace ARK\Cards\Projects;

class P125_ReptileBreeding extends \ARK\Models\Projects\BreedProject
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'P125_ReptileBreeding';
    $this->number = 125;
    $this->name = clienttranslate('Reptile breeding program');
    $this->desc = clienttranslate('Requires 1 **reptile** and a **partner zoo** of the same continent.');
    $this->icon = REPTILE;
  }
}
