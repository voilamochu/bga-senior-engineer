<?php
namespace AGR\Cards\B;

class B135_NutritionExpert extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B135_NutritionExpert';
    $this->name = clienttranslate('Nutrition Expert');
    $this->deck = 'B';
    $this->number = 135;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'At the start of each round, you can exchange a set comprised of 1 animal of any type, 1 <GRAIN>, and 1 <VEGETABLE> for 5 <FOOD> and 2 bonus <SCORE>.'
      ),
    ];
    $this->players = '1+';
    $this->extraVp = true;
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) &&
      $event['type'] == 'StartOfTurn';
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    return [
      'type' => NODE_XOR,
      'optional' => true,
      'countAsUse' => true,
      'doableRequiresChild' => true,
      'customDescription' => 'Pay <GRAIN>, <VEGETABLE> and animal',
      'childs' => [
        $this->payGainNode([SHEEP => 1, GRAIN => 1, VEGETABLE => 1],[FOOD => 5, SCORE => 2], null, false),
        $this->payGainNode([PIG => 1, GRAIN => 1, VEGETABLE => 1],[FOOD => 5, SCORE => 2], null, false),
        $this->payGainNode([CATTLE => 1, GRAIN => 1, VEGETABLE => 1],[FOOD => 5, SCORE => 2], null, false),
      ]
    ];
  }
}
