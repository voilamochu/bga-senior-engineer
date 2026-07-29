<?php

namespace AGR\Cards\E;

use AGR\Models\MinorImprovement;

class E67_GrainBag extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E67_GrainBag';
    $this->name = clienttranslate('Grain Bag');
    $this->deck = 'E';
    $this->author = 'beso';
    $this->number = 67;
    $this->category = 'CROPS_-_GRAIN';
    $this->desc = [
      clienttranslate(
        'Each time you use the __Grain Seeds__ action space, you get 1 additional <GRAIN> for each <BAKE>-improvement you have.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      REED => 1,
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'GrainSeeds');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    $bakeImpromentNum = 0;
    $cards = [];

    foreach ($player->getCards(MAJOR, true) as $card) {
      if ($card->canBake() && !in_array($card->id, $cards)) {
        $cards[] = $card->id;
        $bakeImpromentNum++;
      }
    }
    foreach ($player->getCards(MINOR, true) as $card) {
      if ($card->canBake() && !in_array($card->id, $cards)) {
        $cards[] = $card->id;
        $bakeImpromentNum++;
      }
    }

    if ($bakeImpromentNum > 0)
      return $this->gainNode([GRAIN => $bakeImpromentNum]);
  }
}