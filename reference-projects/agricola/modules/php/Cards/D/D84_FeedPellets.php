<?php
namespace AGR\Cards\D;

class D84_FeedPellets extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D84_FeedPellets';
    $this->name = clienttranslate('Feed Pellets');
    $this->deck = 'D';
    $this->number = 84;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <SHEEP>. In the feeding phase of each harvest, you can exchange exactly 1 <VEGETABLE> for 1 animal of a type you already have.'
      ),
    ];
  }

  public function onBuy($player)
  {
    return $this->gainNode([SHEEP => 1]);
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'HarvestFeedingPhase';
  }

  public function onPlayerHarvestFeedingPhase($player, $event)
  {
    $childs = [];
    $resources = $player->getExchangeResources();
    foreach (ANIMALS as $type) {
      if ($resources[$type] > 0) {
        $childs[] = $this->gainNode([$type => 1]);
      }
    }

    if (empty($childs)) {
      return null;
    }

    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        $this->payNode([\VEGETABLE => 1]),
        [
          'type' => NODE_XOR,
          'childs' => $childs,
        ],
      ],
    ];
  }
}
