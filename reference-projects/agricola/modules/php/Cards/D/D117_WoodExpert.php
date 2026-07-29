<?php
namespace AGR\Cards\D;

class D117_WoodExpert extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D117_WoodExpert';
    $this->name = clienttranslate('Wood Expert');
    $this->deck = 'D';
    $this->number = 117;
    $this->isCorbariusOrDulcinaria = true;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 2 <WOOD>. Each improvement costs you up to 2 <WOOD> less, if you pay 1 <FOOD> instead.'
      ),
    ];
    $this->players = '1+';
  }

  public function onBuy($player)
  {
    return $this->gainNode([WOOD => 2]);
  }

  public function onPlayerComputeCardCosts($player, &$args)
  {
    if (!in_array($args['card']->getType(), [MAJOR, MINOR])) {
      return;
    }

    foreach ($args['costs']['trades'] as $trade) {
      if (isset($trade[WOOD])) {
        $trade[WOOD] -= 2;
        if ($trade[WOOD] <= 0) {
          unset($trade[WOOD]);
        }
        $trade[FOOD] = ($trade[FOOD] ?? 0) + 1;
        $trade['sources'][] = $this->id;
        $args['costs']['trades'][] = $trade;
      }
    }
  }
}
