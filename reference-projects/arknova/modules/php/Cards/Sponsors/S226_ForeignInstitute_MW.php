<?php
namespace ARK\Cards\Sponsors;

class S226_ForeignInstitute extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S226_ForeignInstitute';
    $this->number = 226;
    $this->name = clienttranslate('Foreign Institute*');
    $this->lvl = 6;
    $this->effects = [
      IMMEDIATE => [clienttranslate('')],
      ENDGAME => [clienttranslate('')],
    ];
    $this->implemented = false;
  }
}
