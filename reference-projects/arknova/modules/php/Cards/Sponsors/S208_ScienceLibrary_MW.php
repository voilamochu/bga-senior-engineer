<?php
namespace ARK\Cards\Sponsors;

class S208_ScienceLibrary extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S208_ScienceLibrary';
    $this->number = 208;
    $this->name = clienttranslate('Science Library*');
    $this->lvl = 4;
    $this->effects = [
      IMMEDIATE => [clienttranslate('')],
      PASSIVE => [clienttranslate('')],
      ENDGAME => [clienttranslate('')],
    ];
    $this->implemented = false;
  }
}
