<?php
namespace AGR\Cards\B;

class B30_WoodPalisades extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B30_WoodPalisades';
    $this->name = clienttranslate('Wood Palisades');
    $this->deck = 'B';
    $this->number = 30;
    $this->category = POINTS_PROVIDER;
    $this->extraVp = true;
    $this->desc = [
      clienttranslate(
        'Instead of a fence piece, you can place 2 <WOOD> from your supply on the fence spaces at the edge of your farmyard. These fence spaces with 2 <WOOD> are each worth 1 <SCORE>.'
      ),
    ];
    $this->isArtifexOrBubulcus = true;
    $this->cost = [
      FOOD => '1',
    ];
    $this->rulings = [
      clienttranslate('Fence spaces with 2 <WOOD> enclose pastures, but are not fences for the purposes of other cards such as __Blackberry Farmer__ (E108).'),
      clienttranslate('Fence spaces with 2 <WOOD> are not removed or rebuilt by __Overhaul__ (C001).'),
    ];
  }

  public function computeBonusScore()
  {
    $count = $this->getPlayer()->board()->countWoodPalisades();
    if ($count > 0) {
      $this->addBonusScoringEntry($count);
    }
  }
}
