<?php
namespace AGR\Cards\B;

class B59_FoodChest extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B59_FoodChest';
    $this->name = clienttranslate('Food Chest');
    $this->deck = 'B';
    $this->number = 59;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'If you play this card on the __Major Improvement__ action space, you immediately get 4 <FOOD>. Otherwise, you get only 2 <FOOD>.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->isArtifexOrBubulcus = true;
  }

  public function onBuyWithData($player, $event)
  {
    $n = 2;
    if ($event['actionCardId'] == 'ActionMajorImprovement') {
      $n = 4;
    }

    return $this->gainNode([FOOD => $n]);
  }
}
