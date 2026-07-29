<?php
namespace ARK\Cards\Projects;

class P117_ReleaseBlueMountain extends \ARK\Models\Projects\ReleaseProject
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'P117_ReleaseBlueMountain';
    $this->number = 117;
    $this->name = clienttranslate('Blue mountains national park');
    $this->desc = clienttranslate('**Release** 1 animal with an **Australia** icon into the wild.');
    $this->icon = \AUSTRALIA;
  }
}
