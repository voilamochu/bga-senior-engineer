<?php
namespace AGR\Cards\D;

class D162_ClayFirer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D162_ClayFirer';
    $this->name = clienttranslate('Clay Firer');
    $this->deck = 'D';
    $this->number = 162;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 2 <CLAY>. At any time, you can turn <CLAY> into <STONE>: you get 1 <STONE> for 2 <CLAY>, and 2 <STONE> for 3 <CLAY>.'
      ),
    ];
    $this->players = '4+';
  }

  public function onBuy($player)
  {
    return $this->gainNode([CLAY => 2]);
  }

  public function getExchanges()
  {
    return [
      [
        'source' => $this->name,
        'triggers' => null,
        'max' => 9999,
        'from' => [
          CLAY => 2,
        ],
        'to' => [STONE => 1],
      ],
      [
        'source' => $this->name,
        'triggers' => null,
        'max' => 9999,
        'from' => [
          CLAY => 3,
        ],
        'to' => [STONE => 2],
      ],
    ];
  }
}
