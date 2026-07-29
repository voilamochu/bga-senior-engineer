<?php
namespace AGR\Cards\B;
use AGR\Managers\Farmers;
use AGR\Managers\ActionCards;

class B4_WoodPile extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B4_WoodPile';
    $this->name = clienttranslate('Wood Pile');
    $this->deck = 'B';
    $this->number = 4;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'You immediately get a number of <WOOD> equal to the number of people you have on accumulation spaces.'
      ),
    ];
    $this->passing = true;
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onBuy($player)
  {
    $pId = $player->getId();
    $spaces = ActionCards::getAccumulationSpaces();
    $n = 0;

    foreach ($spaces as $space) {
      if (!Farmers::getOnCard($space->getId(), $pId)->empty()) {
        $n++;
      }
    }

    if ($n > 0) {
      return $this->gainNode([WOOD => $n]);
    }
  }
}
