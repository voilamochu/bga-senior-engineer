<?php
namespace AGR\Models;

/*
 * Occupation: all utility functions concerning an occupation card
 */

class Occupation extends PlayerCard
{
  protected $type = OCCUPATION;

  public function jsonSerialize()
  {
    $data = parent::jsonSerialize();
    return $data;
  }

  public function getTypeStr()
  {
    return clienttranslate('occupation');
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    // cannot be bought by another player
    if ($this->pId != $player->getId()) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }
}
