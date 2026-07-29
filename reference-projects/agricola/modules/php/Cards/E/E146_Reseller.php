<?php
namespace AGR\Cards\E;

use AGR\Managers\PlayerCards;

class E146_Reseller extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E146_Reseller';
    $this->name = clienttranslate('Reseller');
    $this->deck = 'E';
    $this->author = 'luki';
    $this->number = 146;
    $this->category = 'BUILDING_RESOURCES_-_ALL';
    $this->desc = [
      clienttranslate(
        'Once this game, immediately after playing or building an improvement, you can choose to get its printed cost from the general supply.'
      ),
    ];
    $this->players = '3+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Improvement', 'player', true) && !$this->isFlagged();
  }

  public function onPlayerImmediatelyAfterImprovement($player, $event)
  {
    $card = PlayerCards::get($event['cardId']);
    $costs = $card->getBaseCostsWithFee(true);

    if ($costs == []) {
      return;
    }

    $childs = [];

    foreach ($costs as $cost) {
      if (isset($cost['stable']) || $cost == []) {
        continue;
      }
      $childs[] = $this->gainNode($cost);
    }

    if ($childs != []) {
      return [
        'type' => NODE_SEQ,
        'optional' => true,
        'childs' => [
          $this->flagCardNode(),
          [
            'type' => NODE_XOR,
            'childs' => $childs,
          ]
        ]
      ];
    }
  }
}
