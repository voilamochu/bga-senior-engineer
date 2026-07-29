<?php
namespace AGR\Cards\E;

class E160_KelpGatherer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E160_KelpGatherer';
    $this->name = clienttranslate('Kelp Gatherer');
    $this->deck = 'E';
    $this->author = 'luki';
    $this->number = 160;
    $this->category = 'CROPS';
    $this->desc = [
      clienttranslate(
        'Each time another player uses the __Fishing__ accumulation space, they get 1 additional <FOOD> and you get 1 <VEGETABLE>.'
      ),
    ];
    $this->players = '4+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'Fishing', 'opponent');
  }

  public function onOpponentPlaceFarmer($player, $args)
  {
    $flow = [];
    $flow[] = $this->gainNode([FOOD => 1], $args['pId']);
    $flow[] = $this->gainNode([VEGETABLE => 1], $this->pId);
    return ['type' => NODE_SEQ, 'childs' => $flow];
  }
}
