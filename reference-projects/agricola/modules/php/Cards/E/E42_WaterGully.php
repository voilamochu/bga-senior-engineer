<?php
namespace AGR\Cards\E;

class E42_WaterGully extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E42_WaterGully';
    $this->name = clienttranslate('Water Gully');
    $this->deck = 'E';
    $this->author = 'azwandahlan';
    $this->number = 42;
    $this->category = 'GOODS_-_GET';
    $this->desc = [
      clienttranslate(
        'Place 1 <CATTLE>, 1 <GRAIN>, and 1 <CATTLE> on the next 3 round spaces (in that order). At the start of these rounds, you get the respective good.'
      ),
    ];
    $this->cost = [
      STONE => 1,
    ];
    $this->prerequisite = clienttranslate('Major Well');
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
  
    if (!($player->hasPlayedCard('Major_Well'))) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->futureMeeplesNode([CATTLE => 1], ['+1']),
        $this->futureMeeplesNode([GRAIN => 1], ['+2']),
        $this->futureMeeplesNode([CATTLE => 1], ['+3']),
      ]
    ];
  }
}
