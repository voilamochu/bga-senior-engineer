<?php
namespace ARK\Cards\Projects;

class P122_ReleaseJungle extends \ARK\Models\Projects\ReleaseProject
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'P122_ReleaseJungle';
    $this->number = 122;
    $this->name = clienttranslate('Jungle');
    $this->desc = clienttranslate('**Release 1 primate** into the wild.');
    $this->icon = PRIMATE;
  }
}
