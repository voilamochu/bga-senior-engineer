<?php
namespace ARK\Cards\Sponsors;

class S225_QuarantineLab extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S225_QuarantineLab';
    $this->number = 225;
    $this->name = clienttranslate('Quarantine Lab*');
    $this->lvl = 3;
    $this->effects = [
      IMMEDIATE => [clienttranslate('')],
      PASSIVE => [clienttranslate('')],
      ENDGAME => [clienttranslate('')],
    ];
    $this->implemented = false;
  }
}
