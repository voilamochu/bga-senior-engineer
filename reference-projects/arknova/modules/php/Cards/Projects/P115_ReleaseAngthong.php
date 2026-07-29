<?php
namespace ARK\Cards\Projects;

class P115_ReleaseAngthong extends \ARK\Models\Projects\ReleaseProject
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'P115_ReleaseAngthong';
    $this->number = 115;
    $this->name = clienttranslate('Angthong national park');
    $this->desc = clienttranslate('**Release** 1 animal with an **Asia** icon into the wild.');
    $this->icon = ASIA;
  }
}
