<?php
namespace ARK\Cards\Projects;

class P124_PredatorBreeding extends \ARK\Models\Projects\BreedProject
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'P124_PredatorBreeding';
    $this->number = 124;
    $this->name = clienttranslate('Predator breeding program');
    $this->desc = clienttranslate('Requires 1 **predator** and a **partner zoo** of the same continent.');
    $this->icon = PREDATOR;
  }
}
