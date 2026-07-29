<?php
namespace AGR\Cards\B;
use AGR\Managers\Players;

class B60_BrewingWater extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B60_BrewingWater';
    $this->name = clienttranslate('Brewing Water');
    $this->deck = 'B';
    $this->number = 60;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Fishing__ accumulation space, you can pay 1 <GRAIN> to place 1 <FOOD> on each of the next 6 round spaces. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->isArtifexOrBubulcus = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'Fishing');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'countAsUse' => true,
      'childs' => [
        $this->payNode([GRAIN => 1]),
        $this->futureMeeplesNode([FOOD => 1], 6),
      ]
    ];
  }
}
