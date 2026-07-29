<?php
namespace AGR\Cards\D;

use AGR\Helpers\Utils;

class D16_WoodenWheyBucket extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D16_WoodenWheyBucket';
    $this->name = clienttranslate('Wooden Whey Bucket');
    $this->deck = 'D';
    $this->number = 16;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Each time before you use the __Sheep Market__/__Cattle Market__ accumulation space, you can build exactly 1 stable for 1 <WOOD>/at no cost.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
      FOOD => 1,
    ];
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'SheepMarket') ||
      $this->isActionCardEvent($event, 'CattleMarket');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    $cost = ['max' => 1];

    if ($event['actionCardType'] == 'SheepMarket') {
      $cost[WOOD] = 1;
    }

    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        [
          'action' => STABLES,
          'cardId' => $this->id,
          'args' => ['costs' => Utils::formatCost($cost), 'max' => 1],
        ],
      ],
    ];
  }
}
