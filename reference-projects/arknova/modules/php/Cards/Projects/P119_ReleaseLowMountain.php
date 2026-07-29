<?php
namespace ARK\Cards\Projects;

class P119_ReleaseLowMountain extends \ARK\Models\Projects\ReleaseProject
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'P119_ReleaseLowMountain';
    $this->number = 119;
    $this->name = clienttranslate('Low Mountain Range');
    $this->desc = clienttranslate('**Release 1 bird** into the wild.');
    $this->icon = BIRD;
  }
}
