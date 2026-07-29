<?php
namespace AGR\Cards\B;

use AGR\Managers\ActionCards;
use AGR\Managers\Players;

class B103_FieldMerchant extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B103_FieldMerchant';
    $this->name = clienttranslate('Field Merchant');
    $this->deck = 'B';
    $this->number = 103;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <WOOD> and 1 <REED>. Each time you decline a __Minor/Major Improvement__ action, you get 1 <FOOD>/<VEGETABLE> instead.'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
    $this->implemented = true;

    $this->rulings = [
      clienttranslate('__Merchant__ (C096) does not double a decline.'),
      clienttranslate('Improvements played with __Stone Company__ (A023) are conditional on spending <STONE> and can\'t be declined.'),
    ];
  }
  public function onBuy($player)
  {
    return $this->gainNode([WOOD => 1, REED => 1]);
  }
  public function isListeningTo($event)
  {
    if ($this->isPlayerEvent($event) && $event['method'] == 'computeReplaceImprovement' && isset($event['args'])) {
      $args = $event['args'];
      return $this->checkArgs($args);
    }
    return false;
  }
  function checkArgs($args) {
    return (!isset($args['trueAction']) || $args['trueAction']) && !($args['checkedReplaceAction'] ?? false);
  }
  public function onPlayerIsDoable($player, &$args)
  {
    if ($args['isDoable']) {
      return;
    }
    if ($args['action'] == IMPROVEMENT) {
      if (is_object($args['ctx']) && !is_null($args['ctx']->getArgs())) {
        if ($this->checkArgs($args['ctx']->getArgs())) {
          $args['isDoable'] = true;
        }
      }
    }
  }
  public function onPlayerComputeArgsPlaceFarmer($player)
  {
    $added = [];
    if (Players::count() == 1) {
      $cIds = ['ActionMajorImprovement', 'ActionMeetingPlaceSolo'];
    } else {
      $cIds = ['ActionMajorImprovement'];
    }
    foreach ($cIds as $cId) {
      if (ActionCards::get($cId) && ActionCards::get($cId)->isVisible()) {
        $added[] = ['actionCardId' => $cId, 'ignoreResources' => true];
      }
    }
    return $added;
  }
  public function onPlayerComputeReplaceImprovement($player, $event)
  {
    $args = $event['args'];
    $childs = [];
    if (in_array(MINOR, $args['types'] ?? [])) {
      $childs[] = $this->gainNode([FOOD => 1]);
    }
    if (in_array(MAJOR, $args['types'] ?? [])) {
      $childs[] = $this->gainNode([VEGETABLE => 1]);
    }
    return [
      'type' => NODE_XOR,
      'childs' => $childs,
    ];
  }
}
