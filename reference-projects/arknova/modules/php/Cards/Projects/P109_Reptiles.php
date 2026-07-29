<?php
namespace ARK\Cards\Projects;

class P109_Reptiles extends \ARK\Models\Projects\ProjectIcon
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->type = \CARD_BASE_PROJECT;
    $this->id = 'P109_Reptiles';
    $this->number = 109;
    $this->name = clienttranslate('Reptiles');
    $this->desc = clienttranslate('Requires **reptile** icons in your zoo.');
    $this->icon = REPTILE;
  }
}
