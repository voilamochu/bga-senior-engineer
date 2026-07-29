<?php
namespace AGR\Cards\E;

class E39_Paintbrush extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E39_Paintbrush';
    $this->name = clienttranslate('Paintbrush');
    $this->deck = 'E';
    $this->author = 'inoshishi';
    $this->number = 39;
    $this->category = 'BONUS_POINTS_-_GET';
    $this->desc = [
      clienttranslate('Each harvest, you can exchange exactly 1 <CLAY> for your choice of 2 <FOOD> or 1 bonus <SCORE>.'),
    ];
    $this->extraVp = true;
    $this->cost = [
      WOOD => 1,
    ];
    $this->prerequisite = clienttranslate('1 pig');
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    return $player->countAnimalsOnBoard()[PIG] >= 1 && parent::isBuyable($player, $ignoreResources, $args);
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'HarvestFeedingPhase';
  }

  public function onPlayerHarvestFeedingPhase($player, $event)
  {
    return [
      'type' => NODE_XOR,
      'optional' => true,
      'countAsUse' => true,
      'doableRequiresChild' => true,
      'childs' => [
        $this->payGainNode([CLAY => 1], [FOOD => 2], null, false),
        $this->payGainNode([CLAY => 1], [SCORE => 1], null, false),
      ]
    ];
  }
}