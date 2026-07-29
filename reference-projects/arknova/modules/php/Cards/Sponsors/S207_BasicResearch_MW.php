<?php
namespace ARK\Cards\Sponsors;

class S207_BasicResearch extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S207_BasicResearch';
    $this->number = 207;
    $this->name = clienttranslate('Basic Research*');
    $this->lvl = 4;
    $this->effects = [
      IMMEDIATE => [clienttranslate('')],
    ];
    $this->implemented = false;
  }
}
