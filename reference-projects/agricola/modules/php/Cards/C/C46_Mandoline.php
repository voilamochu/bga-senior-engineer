<?php
namespace AGR\Cards\C;

class C46_Mandoline extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C46_Mandoline';
    $this->name = clienttranslate('Mandoline');
    $this->deck = 'C';
    $this->number = 46;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Once per round, you can pay 1 <VEGETABLE> to get 1 bonus <SCORE>. If you do, place 1 <FOOD> on each of the next 2 round spaces. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->extraVp = true;
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event)
  {
    return ($this->isAnytime($event) && !$this->isFlagged()) || // Use the card
      ($this->isPlayerEvent($event) && $event['type'] == 'StartOfTurn'); // Unuse the card
  }

  public function onPlayerAtAnytime($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->flagCardNode(),
        $this->payGainNode([VEGETABLE => 1], [SCORE => 1], null, false),
        $this->futureMeeplesNode([FOOD => 1], 2),
      ]
    ];
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    return $this->unflagCardNode();
  }
}
