<?php

namespace ARK\Cards\Actions;

use ARK\Core\Globals;

class ActionBuild extends \ARK\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->actionType = 'Build';
    $this->name = clienttranslate('Build');
    $this->descI = [
      clienttranslate('Build **1 building** with a maximum size of <STRENGTH:X>.'),
      clienttranslate('Pay <MONEY:2> per space.'),
      clienttranslate('Available: **Kiosk, pavilion, standard enclosures** and **petting zoo**.'),
    ];
    $this->descII = [
      clienttranslate('Build **1 or more different buildings** with a total maximum size of <STRENGTH:X>.'),
      clienttranslate('Pay <MONEY:2> per space.'),
      clienttranslate('Newly available: **Large Bird Aviary** and **Reptile House**.'),
    ];
    if (Globals::isMarineWorld()) {
      $this->descI[2] = clienttranslate('Available: **Kiosk, pavilion, aquariums, standard enclosures** and **petting zoo**.');
    }
  }
}
