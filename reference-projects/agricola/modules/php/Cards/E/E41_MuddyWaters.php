<?php
namespace AGR\Cards\E;

use AGR\Core\Globals;

class E41_MuddyWaters extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E41_MuddyWaters';
    $this->name = clienttranslate('Muddy Waters');
    $this->deck = 'E';
    $this->author = 'vancoch';
    $this->number = 41;
    $this->category = 'GOODS_-_GET';
    $this->desc = [
      clienttranslate(
        'Alternate placing 1 <FOOD> and 1 <CLAY> on each remaining even-numbered round space, starting with <FOOD>. At the start of these rounds, you get the respective good.'
      ),
    ];
    $this->vp = 1;
    $this->prerequisite = clienttranslate('5 Cards in Play');
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if ($player->getPlayedCards()->count() < 5) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    if (Globals::getTurn()%2==0){
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->futureMeeplesNode([FOOD => 1], ['+2', '+6', '+10']),
          $this->futureMeeplesNode([CLAY => 1], ['+4', '+8', '+12']),
        ]
      ];
    }
    else {
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->futureMeeplesNode([FOOD => 1], ['+1', '+5', '+9', '+13']),
          $this->futureMeeplesNode([CLAY => 1], ['+3', '+7', '+11']),
        ]
      ];
    }
  }
}
