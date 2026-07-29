<?php
namespace ARK\Cards\Projects;

class P108_Primates extends \ARK\Models\Projects\ProjectIcon
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->type = \CARD_BASE_PROJECT;
    $this->id = 'P108_Primates';
    $this->number = 108;
    $this->name = clienttranslate('Primates');
    $this->desc = clienttranslate('Requires **primate** icons in your zoo.');
    $this->icon = PRIMATE;
  }
}
