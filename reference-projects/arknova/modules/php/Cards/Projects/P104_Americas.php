<?php
namespace ARK\Cards\Projects;

class P104_Americas extends \ARK\Models\Projects\ProjectIcon
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->type = \CARD_BASE_PROJECT;
    $this->id = 'P104_Americas';
    $this->number = 104;
    $this->name = clienttranslate('Americas');
    $this->desc = clienttranslate('Requires **Americas** icons in your zoo.');
    $this->icon = AMERICAS;
    $this->slots[1]['gain'][CONSERVATION] = 3;
  }
}
