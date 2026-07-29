<?php
namespace AGR\Cards\A;

class A149_HouseArtist extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A149_HouseArtist';
    $this->name = clienttranslate('House Artist');
    $this->deck = 'A';
    $this->number = 149;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Traveling Players__ accumulation space, you also get a __Build Rooms__ action. Each room you build during the action costs you 1 <REED> less.'
      ),
    ];
    $this->players = '4+';
    $this->isArtifexOrBubulcus = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'TravelingPlayers');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'countAsUse' => true,
      'childs' => [
        [
          'action' => CONSTRUCT,
          'args' => ['actionCardId' => 'A149_HouseArtist'],
        ]
      ],
    ];
  }

  public function orderComputeCostsConstruct()
  {
    return [['<', 'B145_BrushwoodCollector']];
  }

  public function onPlayerComputeCostsConstruct($player, &$args)
  {
    if ($args['actionCardId'] != 'A149_HouseArtist') {
      return;
    }

    foreach ($args['costs']['trades'] as $trade) {
      if (isset($trade[REED])) {
        $trade[REED]--;
        if ($trade[REED] <= 0) {
          unset($trade[REED]);
        }
        $trade['sources'][] = $this->id;
        $args['costs']['trades'][] = $trade;
      }
    }
  }
}
