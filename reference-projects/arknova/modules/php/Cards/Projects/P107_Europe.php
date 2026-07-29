<?php
namespace ARK\Cards\Projects;

class P107_Europe extends \ARK\Models\Projects\ProjectIcon
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->type = \CARD_BASE_PROJECT;
    $this->id = 'P107_Europe';
    $this->number = 107;
    $this->name = clienttranslate('Europe');
    $this->desc = clienttranslate('Requires **Europe** icons in your zoo.');
    $this->icon = EUROPE;
  }
}
