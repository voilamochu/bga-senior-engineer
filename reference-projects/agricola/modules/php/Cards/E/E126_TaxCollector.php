<?php
namespace AGR\Cards\E;

class E126_TaxCollector extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E126_TaxCollector';
    $this->name = clienttranslate('Tax Collector');
    $this->deck = 'E';
    $this->author = 'barbarossa89';
    $this->number = 126;
    $this->category = 'BUILDING_RESOURCES_-_ALL';
    $this->desc = [
      clienttranslate(
        'Once you live in a stone house, at the start of each round, you get your choice of 2 <WOOD>, 2 <CLAY>, 1 <REED>, or 1 <STONE>.'
      ),
    ];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) &&
      $event['type'] == 'StartOfTurn' &&
      $this->getPlayer()->getRoomType() == 'roomStone';
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    return [
      'type' => NODE_XOR,
      'optional' => true,
      'childs' => [
        $this->gainNode([WOOD => 2]),
        $this->gainNode([CLAY => 2]),
        $this->gainNode([REED => 1]),
        $this->gainNode([STONE => 1]),
      ]
    ];
  }
}