<?php
namespace ARK\Cards\Sponsors;

class S227_WazaSpecialAssignment extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S227_WazaSpecialAssignment';
    $this->number = 227;
    $this->name = clienttranslate('Waza Special Assignment*');
    $this->lvl = 6;
    $this->effects = [
      IMMEDIATE => [clienttranslate('')],
      PASSIVE => [clienttranslate('')],
    ];
    $this->implemented = false;
  }
}
