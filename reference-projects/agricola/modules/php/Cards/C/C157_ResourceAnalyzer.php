<?php

namespace AGR\Cards\C;

use AGR\Managers\Players;
use AGR\Models\Occupation;

class C157_ResourceAnalyzer extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C157_ResourceAnalyzer';
    $this->name = clienttranslate('Resource Analyzer');
    $this->deck = 'C';
    $this->number = 157;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Before the start of each round, if you have more building resources than all other players of at least two types, you get 1 <FOOD>.'
      ),
    ];
    $this->players = '4+';
    $this->isCorbariusOrDulcinaria = true;
    $this->bannedWeak3or4p = true;
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) &&
      $event['type'] == 'BeforeStartOfTurn';
  }

  public function onPlayerBeforeStartOfTurn($player, $event)
  {
    $resources = [STONE, CLAY, REED, WOOD];
    $more = [];
    $more['num'] = 0;
    foreach ($resources as $res) {
      $more[$res] = 0;
      foreach (Players::getAll() as $player2) {
        if ($player2->countReserveResource($res) >= $player->countReserveResource($res)) {
          $more[$res] = $more[$res] + 1;
        }
      }
      if ($more[$res] == 1) {
        $more['num'] = $more['num'] + 1;
      }
    }
    if ($more['num'] >= 2) {
      return $this->gainNode([FOOD => 1]);
    }
  }
}