<?php
namespace ARK\Cards\Projects;

class P103_Africa extends \ARK\Models\Projects\ProjectIcon
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->type = \CARD_BASE_PROJECT;
    $this->id = 'P103_Africa';
    $this->number = 103;
    $this->name = clienttranslate('Africa');
    $this->desc = clienttranslate('Requires **Africa** icons in your zoo.');
    $this->icon = AFRICA;
    $this->slots[1]['gain'][CONSERVATION] = 3;
  }
}
