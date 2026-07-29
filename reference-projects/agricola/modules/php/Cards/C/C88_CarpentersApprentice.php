<?php
namespace AGR\Cards\C;

class C88_CarpentersApprentice extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C88_CarpentersApprentice';
    $this->name = clienttranslate("Carpenter's Apprentice");
    $this->deck = 'C';
    $this->number = 88;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Wood rooms cost you 2 <WOOD> less. Your 3rd and 4th stable each cost you 1 <WOOD> less. Your 13th to 15th fence each cost you nothing.'
      ),
    ];
    $this->players = '1+';
    $this->isCorbariusOrDulcinaria = true;
  }

  // Stable and fence discounts moved to Stables.php and Fencing.php

  public function onPlayerComputeCostsConstruct($player, &$args)
  {
    if (($args['type'] ?? null) !== 'roomWood') {
      return;
    }

    if (!isset($args['costs']['trades'])) {
      return;
    }

    foreach ($args['costs']['trades'] as $trade) {
      if (isset($trade[WOOD])) {
        $trade[WOOD] = max(0, $trade[WOOD] - 2);
        if ($trade[WOOD] == 0) {
          unset($trade[WOOD]);
        }
        $trade['sources'][] = $this->id;
        $args['costs']['trades'][] = $trade;
      }
    }
  }
}
