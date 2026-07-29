<?php
namespace AGR\Cards\B;
use AGR\Core\Globals;
use AGR\Core\Engine;
use AGR\Core\Notifications;
use AGR\Helpers\Utils;
use AGR\Managers\Players;
use AGR\Managers\PlayerCards;
use AGR\Models\PlayerCard;
use AGR\Models\Player;

class B3_Moonshine extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B3_Moonshine';
    $this->name = clienttranslate('Moonshine');
    $this->deck = 'B';
    $this->number = 3;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Randomly select an occupation in your hand. Either play it for an occupation cost of 2 <FOOD>, or give it to the next player.'
      ),
    ];
    $this->passing = true;
    $this->isArtifexOrBubulcus = true;

    $this->rulings = [
      clienttranslate('If you cannot pay to play an occupation and/or its additional cost, that occupation must be passed left (or removed from play in a solo game.)'),
    ];
  }

  public function onBuy($player)
  {
    $player = Players::getActive();
    $hand = $player->getHand(OCCUPATION)->toArray();

    if (count($hand) == 0) {
      return;
    }

    return [
      'type' => NODE_SEQ,
      'childs' => [
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'randomizeOcc',
            'args' => []
          ],
        ],
        [
          'type' => NODE_XOR,
          'childs' => [
            [
              'action' => SPECIAL_EFFECT,
              'args' => [
                'cardId' => $this->id,
                'method' => 'playOcc',
                'args' => []
              ],
            ],
            [
              'action' => SPECIAL_EFFECT,
              'args' => [
                'cardId' => $this->id,
                'method' => 'passOcc',
                'args' => []
              ],
            ]
          ]
        ]
      ]
    ];
  }

  public function randomizeOcc()
  {
    $player = Players::getActive();
    $hand = $player->getHand(OCCUPATION)->toArray();
    $this->setExtraDatas('occ', $hand[rand(0, count($hand)-1)]->getId());
    return true; // CHECKPOINT
  }

  public function getPlayOccDescription()
  {
    $cardId = $this->getExtraDatas('occ');

    return [
      'log' => clienttranslate('Play ${card_name} for ${resources_desc}'),
      'args' => [
        'resources_desc' => '2 <FOOD>',
        'card_name' => PlayerCards::get($cardId)->getName(),
      ],
    ];
  }

  public function playOcc()
  {
    $pId = Players::getActiveId();
    $cardId = $this->getExtraDatas('occ');

    $flow = [
      'action' => OCCUPATION,
      'pId' => $pId,
      'args' => [
        'allowedCards' => [$cardId],
        'cost' => [FOOD => 2],
      ],
    ];

    Engine::insertAsChild($flow);
  }

  public function getPassOccDescription()
  {
    $cardId = $this->getExtraDatas('occ');
    $cardName = PlayerCards::get($cardId)->getName();

    if (Globals::isSolo()) {
      return [
        'log' => clienttranslate('Discard ${card_name}'),
        'args' => [
          'card_name' => $cardName,
        ],
      ];      
    }

    $player = Players::getActive();
    $nextPlayer = Players::get(Players::getNextId($player));
    $player_name = $nextPlayer->getName();

    return [
      'log' => clienttranslate('Pass ${card_name} to ${player_name}'),
      'args' => [
        'card_name' => $cardName,
        'player_name' => $player_name,
      ],
    ];
  }

  public function passOcc()
  {
    $player = Players::getActive();
    $cardId = $this->getExtraDatas('occ');

    if (Globals::isSolo()) {
      PlayerCards::moveToBox($cardId);
    } else {
      $next = Players::get(Players::getNextId($player));
      PlayerCard::DB()->update(['player_id' => $next->getId()], $cardId);
    }

    if (Globals::isSolo()) {
      Notifications::destroyCard(PlayerCards::get($cardId), $player);
    } else {
      Notifications::passCard(PlayerCards::get($cardId), $player, $next);
    }
  }
}
