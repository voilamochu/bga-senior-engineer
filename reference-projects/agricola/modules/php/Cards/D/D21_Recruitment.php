<?php
namespace AGR\Cards\D;

use AGR\Managers\ActionCards;
use AGR\Managers\Players;
use AGR\Core\Globals;

class D21_Recruitment extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D21_Recruitment';
    $this->name = clienttranslate('Recruitment');
    $this->deck = 'D';
    $this->number = 21;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'From round 5 on, provided you have room in your house, each time you get a __Minor Improvement__ action, you can take a __Family Growth__ action instead.'
      ),
    ];
    $this->cost = [
      FOOD => 1,
    ];
    $this->prerequisite = clienttranslate('No People Left in the House');
    $this->holder = true;
    $this->isCorbariusOrDulcinaria = true;
    $this->bannedStrong1or2p = true;
    $this->bannedStrong3or4p = true;
  }

  public function onBuy($player)
  {
    if (!is_null($player->getNextFarmerAvailable())) {
      throw new \BgaVisibleSystemException('No People Should Be Left in the House');
    }
  }

  public function isListeningTo($event)
  {
    if ($this->isPlayerEvent($event) && $event['method'] == 'computeReplaceImprovement' && isset($event['args'])) {
      $args = $event['args'];
      return $this->checkArgs($args);
    }
    return false;
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
  function checkArgs($args)
  {
    return in_array(MINOR, $args['types'] ?? []) && (!isset($args['trueAction']) || $args['trueAction']) && Globals::getTurn() >= 5 && !($args['checkedReplaceAction'] ?? false);
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
  public function onPlayerComputeReplaceImprovement($player, $event)
  {
    return [
      'action' => WISHCHILDREN,
      'cardId' => $this->id,
      'optional' => true,
      'args' => ['constraints' => ['freeRoom'], 'cardLocation' => $this->id],
      'source' => $this->name,
    ];
  }
}