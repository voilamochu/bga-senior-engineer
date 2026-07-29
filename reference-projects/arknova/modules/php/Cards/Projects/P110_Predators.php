<?php
namespace ARK\Cards\Projects;

class P110_Predators extends \ARK\Models\Projects\ProjectIcon
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->type = \CARD_BASE_PROJECT;
    $this->id = 'P110_Predators';
    $this->number = 110;
    $this->name = clienttranslate('Predators');
    $this->desc = clienttranslate('Requires **predator** icons in your zoo.');
    $this->icon = \PREDATOR;
  }
}
