<?php

namespace ARK\Cards\Sponsors;

class S279_UnderwaterTunnel extends UniqueBuildingSponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'S279_UnderwaterTunnel';
    $this->number = 279;
    $this->name = clienttranslate('Underwater Tunnel');
    $this->lvl = 4;
    $this->enclosure = 'underwater-tunnel';
    $this->effects = [
      IMMEDIATE => [clienttranslate('Place the Underwater tunnel unique building on your zoo map, covering 2 water spaces (adjacent to another building, as usual).')],
      PASSIVE => [clienttranslate('You may accommodate sea animals in this building. Treat it as an aquarium special enclosure for all purposes (including moving animals). It provides place for up to 2 player tokens.')],
      ENDGAME => [clienttranslate('Gain 3 appeal for 1 adjacent aquarium special enclosure (large or small) or 5 appeal for both adjacent aquarium special enclosures.')],
    ];
    $this->prerequisites = [
      UPGRADED_SPONSORS_CARD => 1
    ];
    $this->appeal = 2;
    $this->wave = true;
  }

  public function score()
  {
    $player = $this->getPlayer();
    $map = $player->map();
    $building = $map->getBuildingsOfType(UNDERWATER_TUNNEL)->first();
    if (is_null($building)) return;


    $neighboursAquariums = [];
    foreach ($map->getCoveredHexes($building['type'], $building, $building['rotation'], false) as $hex) {
      foreach ($map->getNeighbours($hex) as $cell) {
        $building2 = $map->getBuildingAtPos($cell);
        // Only count each building once as a neighbourd of current building
        if (is_null($building2) || in_array($building2['id'], $neighboursAquariums) || $building2['id'] == $building['id']) {
          continue;
        }
        // Only aquariums count
        if (!in_array($building2['type'], [SMALL_AQUARIUM, LARGE_AQUARIUM])) {
          continue;
        }

        $neighboursAquariums[] = $building2['id'];
      }
    }

    $rewards = [
      0 => 0,
      1 => 3,
      2 => 5
    ];
    $reward = $rewards[count($neighboursAquariums)];
    if ($reward > 0) {
      $player->incAppeal($reward, true, $this->getName());
    }
  }
}
