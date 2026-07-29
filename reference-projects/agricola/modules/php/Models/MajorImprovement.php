<?php
namespace AGR\Models;

/*
 * MajorImprovement: all utility functions concerning a major improvement card
 */

class MajorImprovement extends PlayerCard
{
  protected $type = MAJOR;
  protected $returnCards = [];
  protected $category = FOOD_PROVIDER;

  public function jsonSerialize()
  {
    $data = parent::jsonSerialize();
    $data['cook'] = $this->canCook();
    $data['bread'] = $this->canBake();
    return $data;
  }

  public function getCode()
  {
    return $this->number;
  }

  public function getTypeStr()
  {
    return clienttranslate('major improvement');
  }

  public function getCosts($player, $args = [])
  {
    $costs = parent::getCosts($player, $args);
    $costs['cards'] = ['type' => 'Major', 'list' => $this->returnCards];
    return $costs;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if ($this->pId != null) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }
}
