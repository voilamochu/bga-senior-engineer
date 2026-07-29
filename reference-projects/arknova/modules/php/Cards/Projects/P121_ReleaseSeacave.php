<?php
namespace ARK\Cards\Projects;

class P121_ReleaseSeacave extends \ARK\Models\Projects\ReleaseProject
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'P121_ReleaseSeacave';
    $this->number = 121;
    $this->name = clienttranslate('Sea Cave');
    $this->desc = clienttranslate('**Release 1 reptile** into the wild.');
    $this->icon = REPTILE;
  }
}
