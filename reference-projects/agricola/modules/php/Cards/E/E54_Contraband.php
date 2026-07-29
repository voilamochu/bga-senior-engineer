<?php
namespace AGR\Cards\E;
use AGR\Managers\PlayerCards;

class E54_Contraband extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E54_Contraband';
    $this->name = clienttranslate('Contraband');
    $this->deck = 'E';
    $this->author = 'azwandahlan';
    $this->number = 54;
    $this->category = FOOD;
    $this->desc = [
      clienttranslate(
        'Each time you play or build an improvement after this, you can pay 1 additional building resource of a type in the printed cost to get 3 <FOOD>.'
      ),
    ];
    $this->cost = [
      FOOD => 1,
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Improvement');
  }

  public function onPlayerAfterImprovement($player, $event)
  {
    if ($event['cardId'] == 'E54_Contraband') {
      return;
    }

    $card = PlayerCards::get($event['cardId']);
    $costs = $card->getBaseCostsWithFee();
    $resources = [];

    if ($costs == []) {
      return;
    }

    foreach ($costs as $cost) {
      foreach ([WOOD, CLAY, REED, STONE] as $resType) {
        if (isset($cost[$resType]) && !in_array($resType, $resources)) {
          $resources[] = $resType;
        }
      }
    }

    $childs = [];

    foreach ($resources as $resType) {
      $childs[] = $this->payGainNode([$resType => 1], [FOOD => 3], null, false);
    }

    if ($childs != []) {
      return [
        'type' => NODE_XOR,
        'optional' => true,
        'countAsUse' => true,
        'doableRequiresChild' => true,
        'childs' => $childs,
      ];
    }
  }
}
