<?php
namespace AGR\Cards\C;

class C98_CubeCutter extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C98_CubeCutter';
    $this->name = clienttranslate('Cube Cutter');
    $this->deck = 'C';
    $this->number = 98;
    $this->isCorbariusOrDulcinaria = true;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <WOOD>. In the field phase of each harvest, you can use this card to exchange exactly 1 <WOOD> and 1 <FOOD> for 1 bonus <SCORE>.'
      ),
    ];
    $this->players = '1+';
    $this->extraVp = true;
  }

  public function onBuy($player)
  {
    return $this->gainNode([WOOD => 1]);
  }

  public function preFeedingGoodsWanted()
  {
    return [WOOD];
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'HarvestFieldPhase';
  }

  public function onPlayerHarvestFieldPhase($player)
  {
    return $this->payGainNode([WOOD => 1, FOOD => 1],[SCORE => 1]);
  }
}
