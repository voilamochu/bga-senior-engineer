<?php
namespace AGR\Cards\E;

class E82_Profiteering extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E82_Profiteering';
    $this->name = clienttranslate('Profiteering');
    $this->deck = 'E';
    $this->author = 'chris';
    $this->number = 82;
    $this->category = 'BUILDING_RESOURCES_-_ALL';
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <FOOD>. Each time you use the __Day Laborer__ action space, you can exchange 1 building resource for another building resource.'
      ),
    ];
    $this->implemented = true;
  }
  public function onBuy($player)
  {
    return $this->gainNode([FOOD => 1]);
  }
  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'DayLaborer');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    $resources = [WOOD, CLAY, REED, STONE];
    $childs = [];
    foreach ($resources as $pay) {
      $gainChilds = [];
      foreach ($resources as $gain) {
        if ($gain == $pay) continue;
        $gainChilds[] = $this->gainNode([$gain => 1]);
      }
      $childs[] = [
        'type' => NODE_SEQ,
        'customDescription' => 'Pay 1 <' . strtoupper($pay) . '>',
        'childs' => [
          $this->payNode([$pay => 1]),
          [
            'type' => NODE_XOR,
            'childs' => $gainChilds,
          ],
        ],
      ];
    }
    return [
      'type' => NODE_XOR,
      'optional' => true,
      'countAsUse' => true,
      'childs' => $childs,
    ];
  }
}