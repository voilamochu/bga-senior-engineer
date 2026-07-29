<?php
namespace ARK\Cards\Projects;

class P127_PrimateBreeding extends \ARK\Models\Projects\BreedProject
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'P127_PrimateBreeding';
    $this->number = 127;
    $this->name = clienttranslate('Primate breeding program');
    $this->desc = clienttranslate('Requires 1 **primate** and a **partner zoo** of the same continent.');
    $this->icon = PRIMATE;
  }
}
