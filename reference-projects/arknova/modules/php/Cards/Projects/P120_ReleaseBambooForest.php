<?php
namespace ARK\Cards\Projects;

class P120_ReleaseBambooForest extends \ARK\Models\Projects\ReleaseProject
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'P120_ReleaseBambooForest';
    $this->number = 120;
    $this->name = clienttranslate('Bamboo Forest');
    $this->desc = clienttranslate('**Release 1 herbivore** into the wild.');
    $this->icon = \HERBIVORE;
  }
}
