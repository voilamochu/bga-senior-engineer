<?php
namespace AGR\Cards\E;


class E91_PlowBuilder extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E91_PlowBuilder';
    $this->name = clienttranslate('Plow Builder');
    $this->deck = 'E';
    $this->author = 'chris';
    $this->number = 91;
    $this->category = 'FARMYARD_-_PLOWING';
    $this->desc = [
      clienttranslate(
        'You can build the Joinery when taking a __Minor Improvement__ action. If you use the Joinery (or an upgrade thereof) during the harvest, you can pay 1 <FOOD> to plow 1 field.'
      ),
    ];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    if ($this->isActionEvent($event, 'Exchange')) {
      $this->checkJoineryUsage($event);
      return false;
    }

    return ($this->isAnytime($event) && $this->isFlagged('usedJoinery') && !$this->isFlagged()) ||
      ($this->isPlayerEvent($event) && $event['type'] == 'EndHarvest');
  }

  public function onBuy($player)
  {
    $this->setExtraDatas('usedJoinery', false);
  }

  public function onPlayerAtAnytime($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->flagCardNode(),
        $this->payNode([FOOD => 1]),
        [
          'action' => PLOW,
          'cardId' => $this->id,
        ]
      ]
    ];
  }

  public function onPlayerEndHarvest($player)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->unflagCardNode('usedJoinery'),
        $this->unflagCardNode(),
      ]
    ];
  }

  public function checkJoineryUsage($event) {
    $trades = $event['trades'];
    $exchanges = $event['exchanges'];
    $usedJoinery = false;

    foreach ($trades as $tradeIndex) {
      $exchange = $exchanges[$tradeIndex] ?? null;

      if ($exchange) {
        $source = $exchange['source'] ?? null;

        if ($source && $source == 'Joinery') {
          $usedJoinery = true;
        }
      }
    }

    if ($usedJoinery) {
      $this->setExtraDatas('usedJoinery', 1);
    }
  }
}
