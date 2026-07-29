<?php
namespace AGR\Cards\E;
use AGR\Managers\Players;

class E62_SourDough extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E62_SourDough';
    $this->name = clienttranslate('Sour Dough');
    $this->deck = 'E';
    $this->author = 'chris';
    $this->number = 62;
    $this->category = 'FOOD_-_GRAIN';
    $this->desc = [
      clienttranslate(
        'Once per round, if all players have at least 1 person left to place, you can skip placing a person and take a __Bake Bread__ action instead.'
      ),
    ];
    $this->vp = 1;
    $this->prerequisite = clienttranslate('3 Occupations and 1 Baking Improvement');
    $this->occupationPrerequisites = ['min' => 3];
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if (!$player->canBake()) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function canBeActivated()
  {
    $allPlayersCanPlace = true;

    foreach (Players::getAll() as $player2) {
      if (!$player2->hasFarmerAvailable()) {
        $allPlayersCanPlace = false;
      }
    }

    if ($this->isFlagged() || !$allPlayersCanPlace) {
      return false;
    }

    return true;
  }

  public function isListeningTo($event)
  {
    return ($this->isPlayerEvent($event) && $event['type'] == 'StartOfTurn');
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    return $this->unflagCardNode();
  }
}
