<?php
namespace ARK\Cards\Projects;

class P111_Herbivores extends \ARK\Models\Projects\ProjectIcon
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->type = \CARD_BASE_PROJECT;
    $this->id = 'P111_Herbivores';
    $this->number = 111;
    $this->name = clienttranslate('Herbivores');
    $this->desc = clienttranslate('Requires **herbivore** icons in your zoo.');
    $this->icon = \HERBIVORE;
  }
}
