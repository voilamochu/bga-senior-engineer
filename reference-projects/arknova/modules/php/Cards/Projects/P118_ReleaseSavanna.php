<?php
namespace ARK\Cards\Projects;

class P118_ReleaseSavanna extends \ARK\Models\Projects\ReleaseProject
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'P118_ReleaseSavanna';
    $this->number = 118;
    $this->name = clienttranslate('Savanna');
    $this->desc = clienttranslate('**Release 1 predator** into the wild.');
    $this->icon = \PREDATOR;
  }
}
