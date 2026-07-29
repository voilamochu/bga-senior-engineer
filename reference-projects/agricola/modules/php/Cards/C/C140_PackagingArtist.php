<?php
namespace AGR\Cards\C;

use AGR\Managers\ActionCards;

class C140_PackagingArtist extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C140_PackagingArtist';
    $this->name = clienttranslate('Packaging Artist');
    $this->deck = 'C';
    $this->number = 140;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <GRAIN>. Each time you get a __Minor Improvement__ action, you can take a __Bake Bread__ action instead.'
      ),
    ];
    $this->players = '3+';
    $this->isCorbariusOrDulcinaria = true;
    $this->implemented = true;
  }
  public function onBuy($player)
  {
    return $this->gainNode([GRAIN => 1]);
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
    return in_array(MINOR, $args['types'] ?? []) && (!isset($args['trueAction']) || $args['trueAction']) && !($args['checkedReplaceAction'] ?? false);
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
    $cIds = ['ActionMajorImprovement'];
    foreach ($cIds as $cId) {
      if (ActionCards::get($cId) && ActionCards::get($cId)->isVisible()) {
        $added[] = ['actionCardId' => $cId, 'ignoreResources' => true];
      }
    }
    return $added;
  }
  public function onPlayerComputeReplaceImprovement($player, $event)
  {
    return $this->bakeBreadNode();
  }
}
