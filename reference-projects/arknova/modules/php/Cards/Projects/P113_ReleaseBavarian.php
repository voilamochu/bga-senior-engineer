<?php
namespace ARK\Cards\Projects;

class P113_ReleaseBavarian extends \ARK\Models\Projects\ReleaseProject
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'P113_ReleaseBavarian';
    $this->number = 113;
    $this->name = clienttranslate('Bavarian Forest national park');
    $this->desc = clienttranslate('**Release** 1 animal with a **Europe** icon into the wild.');
    $this->icon = EUROPE;
  }
}
