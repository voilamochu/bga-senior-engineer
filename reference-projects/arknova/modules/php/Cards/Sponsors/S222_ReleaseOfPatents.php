<?php
namespace ARK\Cards\Sponsors;

use ARK\Managers\Players;

class S222_ReleaseOfPatents extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S222_ReleaseOfPatents';
    $this->number = 222;
    $this->name = clienttranslate('Release Of Patents');
    $this->lvl = 5;
    $this->prerequisites = [\MAX_25_APPEAL => 1];
    $this->effects = [
      IMMEDIATE => [
        clienttranslate(
          'Gain 1 conservation point for each research icon in your zoo (up to a maximum of 3). All other players gain 2 money each for each conservation point you gain this way.'
        ),
      ],
    ];
  }

  public function getImmediate()
  {
    $n = min(3, $this->countIcon(SCIENCE));
    return $n == 0 ? [] : [[CONSERVATION => $n], [MONEY => 2 * $n, 'pId' => EVERYONE_ELSE]];
  }
}
