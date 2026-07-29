<?php
namespace AGR\Cards\C;

use AGR\Helpers\UsedText;
use AGR\Helpers\Utils;

class C96_Merchant extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C96_Merchant';
    $this->name = clienttranslate('Merchant');
    $this->deck = 'C';
    $this->number = 96;
    $this->isCorbariusOrDulcinaria = true;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Immediately after each time you take a __Major or Minor Improvement__ or __Minor Improvement__ action, you can pay 1 <FOOD> to take the action a second time.'
      ),
    ];
    $this->players = '1+';
    $this->usedText = UsedText::get('IMPROVEMENTS_BUILT');
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Improvement', 'player', true) && ($event['actionCardId'] ?? null) != 'C96_Merchant';
  }

  public function onPlayerImmediatelyAfterImprovement($player, $event)
  {
    if (!$event['trueAction']) {
      return;
    }

    $actionType = $event['actionType'] ?? null;

    if ($this->getExtraDatas('activated') != 1) {
      return [
        'countAsUse' => true,
        'type' => NODE_SEQ,
        'optional' => true,
        'childs' => [
          [
            'action' => PAY,
            'args' => [
              'nb' => 1,
              'costs' => Utils::formatCost([FOOD => 1]),
              'source' => $this->name,
            ],
          ],
          Utils::wrapOptional([
            'action' => IMPROVEMENT,
            'cardId' => 'C96_Merchant',
            'args' => [
              'types' => $event['types'],
              'actionType' => $actionType,
            ],
          ]),
        ],
      ];
    }
  }
}
