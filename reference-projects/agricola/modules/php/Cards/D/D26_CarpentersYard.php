<?php
namespace AGR\Cards\D;

use AGR\Helpers\UsedText;
use AGR\Managers\PlayerCards;
use AGR\Core\Engine;

class D26_CarpentersYard extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D26_CarpentersYard';
    $this->name = clienttranslate("Carpenter's Yard");
    $this->deck = 'D';
    $this->number = 26;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'You can build the __Joinery__ and __Well__ major improvement even when taking a __Minor Improvement__ action, or you can build both with a single __Major Improvement__ action.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 1,
      REED => 1,
    ];
    $this->isCorbariusOrDulcinaria = true;
    $this->usedText = UsedText::get('IMPROVEMENTS_BUILT');
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Improvement', 'player', true) && $event['trueAction'] && ($event['actionType'] == 'MajorOrMinor'||$event['actionType'] == 'Major');
  }

  public function onPlayerImmediatelyAfterImprovement($player, $event)
  {
    if (!in_array($event['cardId'], ['Major_Well', 'Major_Joinery'])) {
      return;
    }

    $other = $event['cardId'] == 'Major_Well' ? 'Major_Joinery' : 'Major_Well';
    return [
      'countAsUse' => true,
      'action' => SPECIAL_EFFECT,
      'optional' => true,
      'args' => [
        'cardId' => $this->id,
        'method' => 'buildOther',
        'args' => [$other]
      ]
    ];
  }

  public function getBuildOtherDescription($cardId)
  {
    return [
      'log' => clienttranslate('Build ${card}'),
      'args' => [
        'card' => PlayerCards::get($cardId)->getName(),
      ],
    ];    
  }

  public function buildOther($cardId)
  {
    $flow = [
      'action' => IMPROVEMENT,
      'args' => [
        'types' => [MAJOR],
        'allowedPurchases' => [$cardId],
      ],
    ];
    Engine::insertAsChild($flow);
  }
}
