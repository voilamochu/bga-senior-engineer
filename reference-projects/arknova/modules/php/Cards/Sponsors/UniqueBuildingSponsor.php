<?php

namespace ARK\Cards\Sponsors;

use ARK\Actions\Build;

class UniqueBuildingSponsor extends \ARK\Models\Sponsor
{
  protected $enclosure;

  public function canBePlayed($player, $icons, $nCanIgnore = 0)
  {
    return parent::canBePlayed($player, $icons, $nCanIgnore) && Build::getPlayableBuildingsAux($player, true, [$this->enclosure]);
  }

  public function getImmediate()
  {
    return [[BUILD => $this->enclosure]];
  }
}
