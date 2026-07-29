<?php
namespace AGR\Cards\B;
use AGR\Helpers\Utils;

class B126_Carpenter extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B126_Carpenter';
    $this->name = clienttranslate('Carpenter');
    $this->deck = 'B';
    $this->number = 126;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Every new room only costs you 3 of the appropriate building resource and 2 <REED> (e.g. if you live in a wooden house, 3 <WOOD> and 2 <REED>).'
      ),
    ];
    $this->players = '1+';
    $this->replacesCostFor = ['ComputeCostsConstruct'];
  }

  public function onPlayerComputeCostsConstruct($player, &$args)
  {
    $resMap = [
      'roomWood' => WOOD,
      'roomClay' => CLAY,
      'roomStone' => STONE,
    ];
    $res = $resMap[$args['type']];

    Utils::addCost($args['costs'], [$res => 3, REED => 2], $this->id);
  }
}
