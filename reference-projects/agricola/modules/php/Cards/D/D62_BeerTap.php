<?php
namespace AGR\Cards\D;

class D62_BeerTap extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D62_BeerTap';
    $this->name = clienttranslate('Beer Tap');
    $this->deck = 'D';
    $this->number = 62;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 2 <FOOD>. In the feeding phase of each harvest, you can turn 2/3/4 <GRAIN> into 3/6/9 <FOOD>.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function onBuy($player) {
    return $this->gainNode([FOOD => 2]);
  }
  
  public function getExchanges()
  {
    return [
      [
        'source' => $this->name,
        'triggers' => [HARVEST],
        'max' => INFTY,
        'from' => [
          GRAIN => 2,
        ],
        'to' => [FOOD => 3],
      ],
      [
        'source' => $this->name,
        'triggers' => [HARVEST],
        'max' => INFTY,
        'from' => [
          GRAIN => 3,
        ],
        'to' => [FOOD => 6],
      ],
      [
        'source' => $this->name,
        'triggers' => [HARVEST],
        'max' => INFTY,
        'from' => [
          GRAIN => 4,
        ],
        'to' => [FOOD => 9],
      ],
    ];
  }  
}
