<?php
namespace AGR\Models;

/*
 * MinorImprovement: all utility functions concerning a major improvement card
 */

class MinorImprovement extends PlayerCard
{
  protected $type = MINOR;
  protected $passing = false;
  protected $occupationPrerequisites = null;
  protected $improvementPrerequisites = null;

  public function jsonSerialize()
  {
    $data = parent::jsonSerialize();
    $data['cook'] = $this->canCook();
    $data['bread'] = $this->canBake();
    $data['passing'] = $this->passing;
    return $data;
  }

  public function getTypeStr()
  {
    return clienttranslate('minor improvement');
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    // cannot be bought by another player
    if ($this->pId != $player->getId()) {
      return false;
    }

    $prerequisites = $this->occupationPrerequisites ?? [];
    foreach ($prerequisites as $type => $amount) {
      $n = $player->countOccupations();
      if (
        ($type == 'min' && $n < $amount) ||
        ($type == 'max' && $n > $amount) ||
        ($type == 'equal' && $n != $amount)
      ) {
        return false;
      }
    }

    $prerequisites = $this->improvementPrerequisites ?? [];
    foreach ($prerequisites as $type => $amount) {
      $n = $player->countAllImprovements();
      if (
        ($type == 'min' && $n < $amount) ||
        ($type == 'max' && $n > $amount) ||
        ($type == 'equal' && $n != $amount)
      ) {
        return false;
      }
    }


    return parent::isBuyable($player, $ignoreResources, $args);
  }
}
