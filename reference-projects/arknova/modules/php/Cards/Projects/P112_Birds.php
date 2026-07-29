<?php
namespace ARK\Cards\Projects;

class P112_Birds extends \ARK\Models\Projects\ProjectIcon
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->type = \CARD_BASE_PROJECT;
    $this->id = 'P112_Birds';
    $this->number = 112;
    $this->name = clienttranslate('Birds');
    $this->desc = clienttranslate('Requires **bird** icons in your zoo.');
    $this->icon = BIRD;
  }
}
