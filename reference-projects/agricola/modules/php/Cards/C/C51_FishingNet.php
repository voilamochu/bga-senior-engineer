<?php
namespace AGR\Cards\C;

use AGR\Helpers\Utils;
use AGR\Managers\Meeples;
use AGR\Core\Notifications;

class C51_FishingNet extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C51_FishingNet';
    $this->name = clienttranslate('Fishing Net');
    $this->deck = 'C';
    $this->number = 51;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time another player uses the __Fishing__ accumulation space, they must first pay you 1 <FOOD>. Then, in the returning home phase of that round, place 2 <FOOD> on __Fishing__.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      REED => 1,
    ];

    $this->rulings = [
      clienttranslate('Others must have 1 <FOOD> before using __Fishing__ in order to pay the __Fishing Net__ effect.'),
      clienttranslate('<FOOD> from the __Fishing__ action space may not be used to pay the card owner.'),
      clienttranslate('The 2 <FOOD> is only placed for rounds in which another player used __Fishing__.'),
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'Fishing', 'opponent') ||
      ($this->isPlayerEvent($event) && $event['type'] == 'ReturnHome');
  }

  public function onOpponentPlaceFarmer($player, $args)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        [
          'action' => PAY,
          'args' => [
            'nb' => 1,
            'costs' => Utils::formatCost([FOOD => 1]),
            'source' => $this->name,
            'to' => $this->getPlayer()->getId(),
          ],
        ],
        $this->flagCardNode(),
      ],
    ];
  }

  public function onPlayerReturnHome($player, $event)
  {
    if ($this->isFlagged()) {
      return [
        'type' => NODE_SEQ,
        'childs' => [
          [
            'action' => SPECIAL_EFFECT,
            'args' => [
              'cardId' => $this->id,
              'method' => 'placeFood',
              'args' => [],
            ],
          ],
          $this->unflagCardNode(),
        ]
      ];
    }
    
    return;
  }
  
  public function placeFood() {
      $created = Meeples::createResourceOnCard(FOOD, 'ActionFishing', 2);
      Notifications::C51_FishingNet(Meeples::getMany($created));      
  }
}
