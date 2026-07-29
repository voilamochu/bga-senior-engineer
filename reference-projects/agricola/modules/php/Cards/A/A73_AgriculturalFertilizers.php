<?php
namespace AGR\Cards\A;

use AGR\Helpers\UsedText;

class A73_AgriculturalFertilizers extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A73_AgriculturalFertilizers';
    $this->name = clienttranslate('Agricultural Fertilizers');
    $this->deck = 'A';
    $this->number = 73;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time after you turn at least 2 unused spaces into used spaces in one action, you get an additional __Sow__ action.'
      ),
    ];
    $this->prerequisite = clienttranslate('1 Pasture');
    $this->isArtifexOrBubulcus = true;
    $this->rulings = [
      clienttranslate('This is not triggered by cards that give you one plow in addition to a regular plow action, such as __Plow Maker__ (D090) or __Moldboard Plow__ (B019).'),
    ];
    $this->usedText = UsedText::get('SOWS');
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $pastures = count($player->board()->getPastures(true));
    if ($pastures == 0) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Plow') ||
      $this->isActionEvent($event, 'Construct') ||
      $this->isActionEvent($event, 'Fencing') ||
      $this->isActionEvent($event, 'Stables');
  }

  public function onPlayerAfterStables($player, $event)
  {
    if ($event['newUsedSpaces'] >= 2) {
      return [
        'countAsUse' => true,
        'action' => SOW, 
        'optional' => true        
      ];
    }
  }

  public function onPlayerAfterFencing($player, $event)
  {
    if ($event['newUsedSpaces'] >= 2) {
      return [
        'countAsUse' => true,
        'action' => SOW, 
        'optional' => true        
      ];
    }
  }

  public function onPlayerAfterConstruct($player, $event)
  {
    if (count($event['rooms']) >= 2) {
      return [
        'countAsUse' => true,
        'action' => SOW, 
        'optional' => true        
      ];
    }
  }

  public function onPlayerAfterPlow($player, $event)
  {
    $cardId = $event['cardId'] ?? null;

    if ($this->isFlagged() && in_array($cardId, ['A20_DoubleTurnPlow', 'C19_SwingPlow'])) {
      return [
        'countAsUse' => true,
        'type' => NODE_SEQ,
        'childs' => [
          $this->unflagCardNode(),
          [
            'action' => SOW, 
            'optional' => true
          ],
        ],
      ];
    }

    if (in_array($cardId, ['A20_DoubleTurnPlow', 'C19_SwingPlow'])) {
      return $this->flagCardNode();
    }
  }
}
