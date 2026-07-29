<?php
namespace AGR\Cards\B;

class B47_HerringPot extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B47_HerringPot';
    $this->name = clienttranslate('Herring Pot');
    $this->deck = 'B';
    $this->number = 47;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Fishing__ accumulation space, place 1 <FOOD> on each of the next 3 round spaces. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->cost = [
      CLAY => 1,
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'Fishing');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    return $this->futureMeeplesNode([FOOD => 1], 3);
  }
}
