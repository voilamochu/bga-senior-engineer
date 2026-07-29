<?php
namespace AGR\Cards\C;

class C121_ClayKneader extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C121_ClayKneader';
    $this->name = clienttranslate('Clay Kneader');
    $this->deck = 'C';
    $this->number = 121;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <WOOD> and 2 <CLAY>. Each time after you use __Grain Seeds__ or __Vegetable Seeds__ action space, you get 1 <CLAY>.'
      ),
    ];
    $this->players = '1+';
  }

  public function onBuy($player)
  {
    return $this->gainNode([WOOD => 1, CLAY => 2]);
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer') &&
      in_array($event['actionCardType'], ['GrainSeeds', 'VegetableSeeds']);
  }

  public function onPlayerAfterPlaceFarmer($player, $args)
  {
    return $this->gainNode([CLAY => 1]);
  }
}
