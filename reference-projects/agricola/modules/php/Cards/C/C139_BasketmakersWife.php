<?php
namespace AGR\Cards\C;

class C139_BasketmakersWife extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C139_BasketmakersWife';
    $this->name = clienttranslate("Basketmaker's Wife");
    $this->deck = 'C';
    $this->number = 139;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <REED> and 1 <FOOD>. At any time, you can turn 1 <REED> into 2 <FOOD>.'
      ),
    ];
    $this->players = '3+';
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function onBuy($player)
  {
    return $this->gainNode([REED => 1, FOOD => 1]);
  }
  
  public function getExchanges()
  {
    return [
      [
        'source' => $this->name,
        'triggers' => null,
        'max' => INFTY,
        'from' => [
          REED => 1,
        ],
        'to' => [FOOD => 2],
      ]
    ];
  }
}
