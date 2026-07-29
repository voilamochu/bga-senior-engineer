<?php
namespace AGR\Cards\C;
use AGR\Managers\ActionCards;


class C125_Nightworker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C125_Nightworker';
    $this->name = clienttranslate('Nightworker');
    $this->deck = 'C';
    $this->number = 125;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Before the start of each work phase, you can place a person on an accumulation space of a building resource not in your supply. (Then proceed with the start player.)'
      ),
    ];
    $this->players = '1+';
    $this->isCorbariusOrDulcinaria = true;
    $this->bannedStrong1or2p = true;
    $this->bannedStrong3or4p = true;
  }

  public function isListeningTo($event)
  {
       return $this->isPlayerEvent($event) &&
          $event['type'] == 'startOfWork';
  }

  public function onPlayerStartOfWork($player, $args)
  {
    $valid = [];
    foreach ([WOOD, CLAY, REED, STONE] as $type) {
      if($player->countReserveResource($type)){continue;}
      $spaces = ActionCards::getAccumulationSpaces($type);
      foreach ($spaces as $space) {
        $valid[] = $space->getId();
      }
    }
    if ($valid == []) {
      return;
    }
    return [
      'countAsUse' => true,
      'action' => PLACE_FARMER,
      'optional' => true,
      'args' => ['constraints' => $valid],
      'source' => $this->name,
    ];
  }
}
