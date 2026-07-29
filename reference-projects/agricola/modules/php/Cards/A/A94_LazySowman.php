<?php
namespace AGR\Cards\A;

use AGR\Helpers\CardRulings;
use AGR\Managers\ActionCards;
use AGR\Models\Occupation;
use AGR\Core\Globals;
use AGR\Actions\Sow;

class A94_LazySowman extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A94_LazySowman';
    $this->name = clienttranslate('Lazy Sowman');
    $this->deck = 'A';
    $this->number = 94;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each time you decline an unconditional __Sow__ action on your turn, you can immediately place another person on an action space of your choice (even if it is occupied).'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;

    $this->rulings = array_merge(
      CardRulings::fromKeys([
      'MEETING_PLACE_NEVER_REUSED',
      ]),
      [
      clienttranslate('You may repeat this effect on action spaces that allow you to decline such a “Sow” action (e.g. only plowing on __Cultivation__ multiple times.)'),
      clienttranslate('You can only use this effect if you have a person not yet placed on an action space this round. This effect does not allow you to place a person from your supply.'),
      ]
    );
  }
  public function isListeningTo($event)
  {
    if ($this->isPlayerEvent($event) && $event['method'] == 'computeReplaceSow') {
      $args = $event['args'];
      return $this->checkArgs($args) && $this->getPlayer()->hasFarmerAvailable();
    }
    return false;
  }

  public function onPlayerComputeArgsPlaceFarmer($player)
  {
    $added = [];
    $cIds = ['ActionCultivation', 'ActionGrainUtilization'];
    foreach ($cIds as $cId) {
      if (ActionCards::get($cId) && ActionCards::get($cId)->isVisible()) {
        $added[] = ['actionCardId' => $cId, 'ignoreResources' => true];
      }
    }
    return $added;
  }
  function checkArgs($args) {
    // SOW action can have empty args
    return is_null($args) || (Sow::isUnconditional($args) && Globals::isWorkPhase() && !($args['checkedReplaceAction'] ?? false));
  }
  public function onPlayerIsDoable($player, &$args)
  {
    if ($args['isDoable']) {
      return;
    }
    if ($args['action'] == SOW) {
      if (is_object($args['ctx'])) {
        if ($this->checkArgs($args['ctx']->getArgs()) && $player->hasFarmerAvailable()) {
          $args['isDoable'] = true;
        }
      }
    }
  }
  public function onPlayerComputeReplaceSow($player, $event)
  {
    $added = [];
    $cards = ActionCards::getVisible($player);
    foreach ($cards as $card) {
      if ($card->getActionCardType() == 'MeetingPlace') {
        continue;
      }
      $added[] = $card->getId();
    }

    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'countAsUse' => true,
      'childs' => [
        $this->setTurnIdNode(),
        [
          'action' => PLACE_FARMER,
          'args' => ['added' => $added],
          'source' => $this->name,
        ],
      ],
    ];
  }
}
