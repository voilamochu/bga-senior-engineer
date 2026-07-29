<?php
namespace AGR\Cards\D;

use AGR\Helpers\UsedText;
use AGR\Helpers\Utils;
use AGR\Helpers\CardRulings;

class D96_Furnisher extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D96_Furnisher';
    $this->name = clienttranslate('Furnisher');
    $this->deck = 'D';
    $this->number = 96;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 2 <WOOD>. Each time after you build at least one new room, you can build or play a number of improvements equal to the number of new rooms you built, paying up to 1 <WOOD> less for each such improvement.'
      ),
    ];
    $this->players = '1+';

    $this->rulings = array_merge(
      CardRulings::fromKeys([
      'UPDATED_DIGITAL',
      ]),
      [
      clienttranslate('The improvement does not need to cost any <WOOD>.'),
      ]
    );
    $this->usedText = UsedText::get('IMPROVEMENTS_BUILT');
  }

  public function onBuy($player)
  {
    return $this->gainNode([WOOD => 2]);
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Construct') ||
      $this->isActionEvent($event, 'Improvement');
  }

  public function onPlayerAfterConstruct($player, $event)
  {
    $flow = [
      'type' => NODE_SEQ,
      'optional' => true,
      'pId' => $this->pId,
      'childs' => [],
    ];

    foreach ($event['rooms'] as $r) {
      $flow['childs'][] = Utils::wrapOptional([
        'action' => IMPROVEMENT,
        'cardId' => 'D96_Furnisher',
        'pId' => $this->pId,
        'args' => [
          'types' => [MINOR, MAJOR],
          'trueAction' => false,
        ],
      ]);
    }

    return $flow;
  }

  public function onPlayerAfterImprovement($player, $event)
  {
    if (($event['actionCardId'] ?? null) !== 'D96_Furnisher') {
      return null;
    }
    return [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'bumpUsed',
      ],
    ];
  }

  public function bumpUsed()
  {
    $this->incStats('used');
  }

  public function isIndependentBumpUsed()
  {
    return true;
  }

  public function onPlayerComputeCardCosts($player, &$args)
  {
    if (($args['actionCardId'] ?? null) != 'D96_Furnisher') {
      return;
    }

    foreach ($args['costs']['trades'] as $trade) {
      if (isset($trade[WOOD])) {
        $trade[WOOD]--;
        if ($trade[WOOD] <= 0) {
          unset($trade[WOOD]);
        }
        $trade['sources'][] = $this->id;
        $args['costs']['trades'][] = $trade;
      }
    }
  }
}
