<?php
namespace ARK\Cards\Sponsors;

class S261_GuidedSchoolToours extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S261_GuidedSchoolToours';
    $this->number = 261;
    $this->name = clienttranslate('Guided School Toours*');
    $this->lvl = 3;
    $this->effects = [
      IMMEDIATE => [clienttranslate('')],
      ENDGAME => [clienttranslate('')],
    ];
    $this->implemented = false;
  }
}
