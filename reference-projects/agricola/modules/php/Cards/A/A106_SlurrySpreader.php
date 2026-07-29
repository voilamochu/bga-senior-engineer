<?php

namespace AGR\Cards\A;

use AGR\Models\Occupation;

class A106_SlurrySpreader extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A106_SlurrySpreader';
    $this->name = clienttranslate('Slurry Spreader');
    $this->deck = 'A';
    $this->number = 106;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In the field phase of each harvest, each time you take the last <GRAIN>/<VEGETABLE> from a field, you also get 2 <FOOD>/1 <FOOD>.'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
  }
}
