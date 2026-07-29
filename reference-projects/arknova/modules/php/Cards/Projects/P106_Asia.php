<?php
namespace ARK\Cards\Projects;

class P106_Asia extends \ARK\Models\Projects\ProjectIcon
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->type = \CARD_BASE_PROJECT;
    $this->id = 'P106_Asia';
    $this->number = 106;
    $this->name = clienttranslate('Asia');
    $this->desc = clienttranslate('Requires **Asia** icons in your zoo.');
    $this->icon = ASIA;
    $this->slots[1]['gain'][CONSERVATION] = 3;
  }
}
