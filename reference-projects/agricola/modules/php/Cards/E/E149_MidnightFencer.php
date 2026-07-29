<?php
namespace AGR\Cards\E;

use AGR\Core\Globals;
use AGR\Helpers\Utils;
use AGR\Managers\Fences;
use AGR\Managers\Players;

class E149_MidnightFencer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E149_MidnightFencer';
    $this->name = clienttranslate('Midnight Fencer');
    $this->deck = 'E';
    $this->author = 'cunning';
    $this->number = 149;
    $this->category = 'FARMYARD';
    $this->desc = [
      clienttranslate(
        'At the start of the last harvest, you can take up to 2 of each other player\'s unbuilt fences and build them on your farm at no cost. (Your farm can then have over 15 fences.)'
      ),
    ];
    $this->players = '4+';
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'StartHarvest';
  }

  public function onPlayerStartHarvest($player, $event)
  {
    if (Globals::getTurn() != 14) {
      return;
    }

    // Max fences you can steal: up to 2 from each opponent, limited by their reserve fences
    $max = 0;
    foreach (Players::getAll() as $p) {
      if ($p->getId() == $player->getId()) continue;
      $max += min(2, Fences::countAvailable($p->getId()));
    }

    if ($max == 0) {
      return;
    }

    // Insert a fencing action with a special mode flag
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        [
          'action' => \FENCING,
          'args' => [
            'midnightFencer' => true,
            'max' => $max,
            // costs object must exist structurally, but we will not actually charge in midnight mode
            'costs' => Utils::formatCost([WOOD => 1]),
          ],
        ],
      ],
    ];
  }  


}
