<?php
namespace AGR\Cards\A;

class A147_AnimalDealer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A147_AnimalDealer';
    $this->name = clienttranslate('Animal Dealer');
    $this->deck = 'A';
    $this->number = 147;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Sheep Market__, __Pig Market__, or __Cattle Market__ accumulation space, you can buy 1 additional animal of the respective type for 1 <FOOD>.'
      ),
    ];
    $this->players = '3+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'SheepMarket') ||
      $this->isActionCardEvent($event, 'PigMarket') ||
      $this->isActionCardEvent($event, 'CattleMarket');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    $animal = '';

    if ($args['actionCardType'] == 'SheepMarket') {
      $animal = SHEEP;
    } elseif ($args['actionCardType'] == 'PigMarket') {
      $animal = PIG;
    } elseif ($args['actionCardType'] == 'CattleMarket') {
      $animal = CATTLE;
    } else {
      return null;
    }

    return [
      'type' => NODE_SEQ,
      //'actionId' => 'AnimalDealer', TODO : remove ???
      'pId' => $player->getId(),
      'optional' => true,
      'childs' => [
        $this->payNode([FOOD => 1]),
        [
          'action' => GAIN,
          'pId' => $player->getId(),
          'args' => [$animal => 1, 'skipReorganize' => true],
          'source' => $this->name,
        ],
      ],
    ];
  }
}
