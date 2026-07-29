<?php
namespace AGR\Cards\E;

class E6_Recount extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E6_Recount';
    $this->name = clienttranslate('Recount');
    $this->deck = 'E';
    $this->author = 'guy';
    $this->number = 6;
    $this->category = 'PASSING_-_BUILDING_RESOURCES_';
    $this->desc = [
      clienttranslate(
        'You immediately get 1 building resource of each type of which you have 4 or more resources in your supply already.'
      ),
    ];
    $this->passing = true;
  }

  public function onBuy($player)
  {
     $meeples = [WOOD => $player->countReserveResource(WOOD), CLAY => $player->countReserveResource(CLAY), REED => $player->countReserveResource(REED), STONE => $player->countReserveResource(STONE)];
   
    $gains = [];
    foreach ($meeples as $type => $n) {
      if ($n >= 4) {
        $gains[$type] = 1;
      }
    }

    return $this->gainNode($gains);
  }
}
