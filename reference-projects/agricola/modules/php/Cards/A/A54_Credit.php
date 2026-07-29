<?php
namespace AGR\Cards\A;
use AGR\Core\Globals;

class A54_Credit extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A54_Credit';
    $this->name = clienttranslate('Credit');
    $this->deck = 'A';
    $this->number = 54;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 5 <FOOD>. At the end of each round that does not end with a harvest, you must pay 1 <FOOD>, or else take a <BEGGING> marker.'
      ),
    ];
    $this->prerequisite = clienttranslate('At Most 3 Occupations');
    $this->occupationPrerequisites = ['max' => 3];
    $this->isArtifexOrBubulcus = true;
  }

  public function onBuy($player)
  {
    return $this->gainNode([FOOD => 5]);
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) &&
      $event['type'] == 'EndOfRound';
  }

  public function onPlayerEndOfRound($player, $event)
  {
    $turn = Globals::getTurn();
    $harvestRounds = [4, 7, 9, 11, 13, 14];

    if (!in_array($turn, $harvestRounds)) {
      return [
        'type' => NODE_XOR,
        'childs' => [
          $this->payNode([FOOD => 1]),
          $this->gainNode([BEGGING => 1]),
        ]
      ];
    }
  }
}
