<?php

namespace AGR\Cards\A;

use AGR\Actions\Sow;
use AGR\Models\MinorImprovement;

class A65_SeedPellets extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A65_SeedPellets';
    $this->name = clienttranslate('Seed Pellets');
    $this->deck = 'A';
    $this->number = 65;
    $this->category = CROP_PROVIDER;
    $this->desc = [clienttranslate('Each time before you take an unconditional __Sow__ action, you get 1 <GRAIN>.')];
    $this->prerequisite = clienttranslate('3 Fields');
    $this->isArtifexOrBubulcus = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if ($player->board()->countFields() < 3) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function isListeningTo($event)
  {
    return $this->isBeforeEvent($event, 'Sow') &&
      (!isset($event['args']) || Sow::isUnconditional($event['args']));
  }

  public function onPlayerBeforeSow($player, $event)
  {
    return $this->gainNode([GRAIN => 1]);
  }
}
