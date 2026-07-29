<?php
namespace AGR\Cards\C;
use AGR\Helpers\CardRulings;

class C61_BeerStein extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C61_BeerStein';
    $this->name = clienttranslate('Beer Stein');
    $this->deck = 'C';
    $this->number = 61;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you take a __Bake Bread__ action, you can use this card once to turn 1 <GRAIN> into 2 <FOOD> and 1 bonus <SCORE>.'
      ),
    ];
    $this->cost = [
      CLAY => 1,
    ];
    $this->extraVp = true;
    $this->isCorbariusOrDulcinaria = true;

    $this->rulings = CardRulings::fromKeys([
      'MUST_BAKE_NORMALLY',
    ]);
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Exchange') && $event['trigger'] == BREAD;
  }

  public function onPlayerAfterExchange($player, $event)
  {
    return $this->payGainNode([GRAIN => 1], [FOOD => 2, SCORE => 1]);
  }  
}
