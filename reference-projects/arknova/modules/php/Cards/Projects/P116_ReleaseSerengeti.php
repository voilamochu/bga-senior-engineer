<?php
namespace ARK\Cards\Projects;

class P116_ReleaseSerengeti extends \ARK\Models\Projects\ReleaseProject
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'P116_ReleaseSerengeti';
    $this->number = 116;
    $this->name = clienttranslate('Serengeti national park');
    $this->desc = clienttranslate('**Release** 1 animal with an **Africa** icon into the wild.');
    $this->icon = AFRICA;
  }
}
