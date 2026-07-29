<?php
namespace ARK\Cards\Projects;

class P105_Australia extends \ARK\Models\Projects\ProjectIcon
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->type = \CARD_BASE_PROJECT;
    $this->id = 'P105_Australia';
    $this->number = 105;
    $this->name = clienttranslate('Australia');
    $this->desc = clienttranslate('Requires **Australia** icons in your zoo.');
    $this->icon = AUSTRALIA;
  }
}
