<?php
namespace ARK\Cards\Projects;

class P114_ReleaseYosemite extends \ARK\Models\Projects\ReleaseProject
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'P114_ReleaseYosemite';
    $this->number = 114;
    $this->name = clienttranslate('Yosemite national park');
    $this->desc = clienttranslate('**Release** 1 animal with an **Americas** icon into the wild.');
    $this->icon = AMERICAS;
  }
}
