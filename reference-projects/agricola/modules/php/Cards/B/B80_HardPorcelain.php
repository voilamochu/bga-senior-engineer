<?php
namespace AGR\Cards\B;

use AGR\Helpers\Utils;

class B80_HardPorcelain extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B80_HardPorcelain';
    $this->name = clienttranslate('Hard Porcelain');
    $this->deck = 'B';
    $this->number = 80;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [clienttranslate('At any time, you can exchange 2/3/4 <CLAY> for 1/2/3 <STONE>.')];
    $this->cost = [
      CLAY => 1,
    ];
  }

  public function getExchanges()
  {
    return [
      [
        'source' => $this->name,
        'triggers' => null,
        'max' => INFTY,
        'from' => [
          CLAY => 2,
        ],
        'to' => [STONE => 1],
      ],
      [
        'source' => $this->name,
        'triggers' => null,
        'max' => INFTY,
        'from' => [
          CLAY => 3,
        ],
        'to' => [STONE => 2],
      ],
      [
        'source' => $this->name,
        'triggers' => null,
        'max' => INFTY,
        'from' => [
          CLAY => 4,
        ],
        'to' => [STONE => 3],
      ],
    ];
  }
}
