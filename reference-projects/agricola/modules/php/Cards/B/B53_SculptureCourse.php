<?php
namespace AGR\Cards\B;
use AGR\Core\Globals;

class B53_SculptureCourse extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B53_SculptureCourse';
    $this->name = clienttranslate('Sculpture Course');
    $this->deck = 'B';
    $this->number = 53;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'At the end of each round that does not end with a harvest, you can use this card to exchange your choice of 1 <WOOD> for 2 <FOOD>, or 1 <STONE> for 4 <FOOD>.'
      ),
    ];
    $this->cost = [
      GRAIN => 1,
    ];
    $this->isArtifexOrBubulcus = true;
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) &&
      $event['type'] == 'EndOfRound';
  }

  public function onPlayerEndOfRound($player, $event)
  {
    $turn = Globals::getTurn();
    if (in_array($turn, [4, 7, 9, 11, 13, 14])) {
      return null;
    }

    return [
      'type' => NODE_XOR,
      'optional' => true,
      'countAsUse' => true,
      'doableRequiresChild' => true,
      'childs' => [
        $this->payGainNode([WOOD => 1], [FOOD => 2], null, false),
        $this->payGainNode([STONE => 1], [FOOD => 4], null, false),
      ]
    ];
  }
}
