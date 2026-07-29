<?php
namespace ARK\Cards\Projects;

class P123_BirdBreeding extends \ARK\Models\Projects\BreedProject
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'P123_BirdBreeding';
    $this->number = 123;
    $this->name = clienttranslate('Bird breeding program');
    $this->desc = clienttranslate('Requires 1 **bird** and a **partner zoo** of the same continent.');
    $this->icon = BIRD;
  }
}
