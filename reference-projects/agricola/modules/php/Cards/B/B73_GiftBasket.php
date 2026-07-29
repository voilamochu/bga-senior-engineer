<?php
namespace AGR\Cards\B;

class B73_GiftBasket extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B73_GiftBasket';
    $this->name = clienttranslate('Gift Basket');
    $this->deck = 'B';
    $this->number = 73;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, if you have exactly 2/3/4/5 rooms, you immediately get 1 <VEGETABLE>/<FOOD>/<GRAIN>/<VEGETABLE>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      REED => 1,
    ];
    $this->prerequisite = clienttranslate('3 Occupations');
    $this->occupationPrerequisites = ['min' => 3];
    $this->isArtifexOrBubulcus = true;
  }

  public function onBuy($player)
  {
    $rooms = $player->countRooms();
	  
  	if ($rooms == 2) {
      $args[VEGETABLE] = 1;
	} 
	elseif ($rooms == 3) {
      $args[FOOD] = 1;
	}
	elseif ($rooms == 4) {
      $args[GRAIN] = 1;
	}
	elseif ($rooms == 5) {
      $args[VEGETABLE] = 1;
	} 
    else {
      return null;
	}

    return $this->gainNode($args);  
  }
}
