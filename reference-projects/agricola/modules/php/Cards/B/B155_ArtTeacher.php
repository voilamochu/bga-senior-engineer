<?php
namespace AGR\Cards\B;

class B155_ArtTeacher extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B155_ArtTeacher';
    $this->name = ('Art Teacher');
    $this->deck = 'B';
    $this->number = 155;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <WOOD> and 1 <REED>. Each time you pay an occupation cost, you can use <FOOD> from the __Traveling Players__ accumulation space.'
      ),
    ];
    $this->players = '4+';
    $this->isArtifexOrBubulcus = true;
  }

  public function onBuy($player)
  {
    return $this->gainNode([ WOOD => 1, REED => 1]);
  }

  public function onPlayerComputeCostsOccupation($player, &$args)
  {
    foreach ($args['costs']['trades'] as $trade) {
      if (isset($trade[FOOD])) {
        $food = $trade[FOOD];
        for ($i = 1; $i <= $food; $i++) {
          $trade[FOOD] -= $i;
          if ($trade[FOOD] <= 0) {
            unset($trade[FOOD]);
          }
          $trade[FOOD_TRAVEL] = $i;
          $trade['sources'][] = $this->id;
          $args['costs']['trades'][] = $trade;
        }
      }
    }
  }
}
