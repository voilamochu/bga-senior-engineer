<?php
namespace AGR\Cards\E;
use AGR\Managers\Meeples;
use AGR\Core\Notifications;

class E27_PiggyBank extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E27_PiggyBank';
    $this->name = clienttranslate('Piggy Bank');
    $this->deck = 'E';
    $this->author = 'azwandahlan';
    $this->number = 27;
    $this->category = 'ACTION_-_IMPROVEMENT';
    $this->desc = [
      clienttranslate(
        'At the end of each work phase, you can place 1 <FOOD> on this card, irretrievably. At any time, you can discard 6 <FOOD> from this card to build a major improvement at no cost.'
      ),
    ];
    $this->holder = true;
  }

  public function isListeningTo($event)
  {
    return ($this->isPlayerEvent($event) && $event['type'] == 'EndWorkPhase') ||
      ($this->isAnytime($event) && Meeples::getResourcesOnCard($this->id, null, FOOD)->count() >= 6);
  }

  public function onPlayerEndWorkPhase($player, $event)
  {
    return $this->moveResourcesToSpaceNode([FOOD => 1], $this->id, true);
  }

  public function onPlayerAtAnytime($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->flagCardNode(),
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'discardFood',
          ]
        ],
        [
          'action' => IMPROVEMENT,
          'args' => [
            'types' => [MAJOR],
            'free' => true,
            'trueAction' => false
          ]
        ],
        $this->unflagCardNode(),
      ]
    ];
  }

  public function getDiscardFoodDescription()
  {
    return [
      'log' => clienttranslate('Discard ${resources_desc} from ${card}'),
      'args' => [
        'resources_desc' => '6 <FOOD>',
        'card' => $this->name,
      ],
    ];
  }

  public function discardFood()
  {
    $foods = Meeples::getResourcesOnCard($this->id, null, FOOD);
    $meeples = [];

    foreach ($foods as $i=>$food) {
      if ($i == 6) {
        break;
      }
      Meeples::DB()->delete($food['id']);
      $meeples[] = $food;
    }

    Notifications::silentKill($meeples);
  }

  public function onPlayerComputeCardCosts($player, &$args)
  {
    if (!$this->isFlagged()) {
      return;
    }

    if ($args['card']->hasType(MAJOR)) {
      foreach ($args['costs']['trades'] as $trade) {
        $trade['sources'][] = $this->id;
        // Keep only 'sources' and any metadata; drop all resource costs.
        foreach ($trade as $key => $value) {
          if (!in_array($key, ['sources', 'nb', 'max', 'bonusChoiceIndex', 'card'])) {
            unset($trade[$key]);
          }
        }
        $args['costs']['trades'][] = $trade;
      }
    }
  }
}
