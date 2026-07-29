<?php
namespace ARK\Cards\Sponsors;

class S274_MascotStatue extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S274_MascotStatue';
    $this->number = 274;
    $this->name = clienttranslate('Mascot Statue');
    $this->lvl = 3;
    $this->effects = [
      IMMEDIATE => [clienttranslate('')],
      PASSIVE => [clienttranslate('')],
      ENDGAME => [clienttranslate('')],
    ];
    $this->implemented = false;
  }
}
