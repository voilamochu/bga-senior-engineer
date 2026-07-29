<?php

namespace ARK\Cards\Sponsors;

class S272_ExpansionArea extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'S272_ExpansionArea';
    $this->number = 272;
    $this->name = clienttranslate('Expansion Area');
    $this->lvl = 5;
    $this->effects = [
      IMMEDIATE => [clienttranslate('You may place a 3-space standard enclosure for free (the usual building rules apply).')],
      PASSIVE => [clienttranslate('Treat any 3-space enclosure on at least 1 border space as if it is a 5-space enclosure (after it is placed there).')],
      ENDGAME => [clienttranslate('Gain 1 conservation point if all border spaces on your zoo map that are building spaces are covered.')],
    ];
    $this->prerequisites = [
      UPGRADED_SPONSORS_CARD => 1
    ];
  }

  public function getImmediate()
  {
    return [['size-3' => 1]];
  }

  public function score()
  {
    if ($this->getPlayer()->map()->areBorderCellsCovered()) {
      $this->scoreConservation(1);
    }
  }
}
