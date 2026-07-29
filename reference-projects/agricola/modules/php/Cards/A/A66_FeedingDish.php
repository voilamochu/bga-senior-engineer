<?php
namespace AGR\Cards\A;

class A66_FeedingDish extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A66_FeedingDish';
    $this->name = clienttranslate('Feeding Dish');
    $this->deck = 'A';
    $this->number = 66;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use an animal accumulation space while already having an animal of that type, you get 1 <GRAIN>.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->isArtifexOrBubulcus = true;

    $this->rulings = [
      clienttranslate('If you use __Animal Dealer__ (A147) to buy an animal before taking the animal from the space, this card will trigger.'),
    ];
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'SheepMarket') ||
      $this->isActionCardEvent($event, 'PigMarket') ||
      $this->isActionCardEvent($event, 'CattleMarket');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    $animal = '';

    if ($args['actionCardType'] == 'SheepMarket') {
      $animal = SHEEP;
    } elseif ($args['actionCardType'] == 'PigMarket') {
      $animal = PIG;
    } elseif ($args['actionCardType'] == 'CattleMarket') {
      $animal = CATTLE;
    } else {
      return null;
    }
	
    $n = $player->getExchangeResources()[$animal];
	if ($n < 1) {
      return null;
	}
	
    return $this->gainNode([GRAIN => 1]);
  }
}
