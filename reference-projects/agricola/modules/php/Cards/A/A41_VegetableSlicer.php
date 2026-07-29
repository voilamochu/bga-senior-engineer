<?php

namespace AGR\Cards\A;

use AGR\Models\MinorImprovement;

class A41_VegetableSlicer extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A41_VegetableSlicer';
    $this->name = clienttranslate('Vegetable Slicer');
    $this->deck = 'A';
    $this->number = 41;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you upgrade a Fireplace to a Cooking Hearth, you immediately get 2 <WOOD> and 1 <VEGETABLE> (not retroactively).'
      ),
    ];
    $this->cost = [
      WOOD => '1',
    ];
    $this->isArtifexOrBubulcus = true;
  }
}
