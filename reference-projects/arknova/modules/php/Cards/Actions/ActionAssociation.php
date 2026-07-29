<?php

namespace ARK\Cards\Actions;

class ActionAssociation extends \ARK\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->actionType = 'Association';
    $this->name = clienttranslate('Association');
    $this->descI = [clienttranslate('Perform **1 association task** with a maximum value of <STRENGTH:X>.')];
    $this->descII = [
      clienttranslate('Perform **1 or more different association tasks** with a total maximum value of <STRENGTH:X>.'),
      clienttranslate('In addition, you may make 1 **donation**.'),
      \clienttranslate('You may play **Conservation project** cards from within **reputation range** (with additional costs).'),
    ];
    $this->tooltip = [
      clienttranslate('At level I, you can support conservation projects either on the board or from your hand <HAND-CARDS>. At level II, you can also support conservation project in your reputation range by paying **the folder number as additional cost** <HAND-CARDS> <TAKE-IN-RANGE-FOLDER-COST>.')
    ];
  }
}
