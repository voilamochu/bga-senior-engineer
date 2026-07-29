<?php

namespace ARK\Cards\Sponsors;

class S217_Engineer extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S217_Engineer';
    $this->number = 217;
    $this->name = clienttranslate('Engineer');
    $this->lvl = 4;
    $this->effects = [
      PASSIVE => [
        clienttranslate(
          'Each time you take the __Build__ action, you may build exactly 1 more of any of the built buildings. This does not apply to special enclosures, of which you may still only have a maximum of 1 of each type in your zoo. Pay the normal cost for the additional building. If you build several buildings at once with one action, you may still only build 1 additional building with the engineer; however, you are free to choose of which one you build another. The usual building rules apply.'
        ),
      ],
      ENDGAME => [
        clienttranslate(
          'Gain 5 appeal if you have covered your zoo map completely (all spaces except the rock and water spaces).'
        ),
      ],
    ];
    $this->person = true;
  }

  public function score()
  {
    $player = $this->getPlayer();
    if ($player->map()->countEmptySpaces() == 0) {
      $player->incAppeal(5, true, $this->getName());
    }
  }
}
