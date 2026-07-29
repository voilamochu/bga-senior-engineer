<?php
namespace AGR\Cards\E;
use AGR\Managers\Players;
use AGR\Core\Engine;

class E167_DairyCrier extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E167_DairyCrier';
    $this->name = clienttranslate('Dairy Crier');
    $this->deck = 'E';
    $this->author = 'oxmond';
    $this->number = 167;
    $this->category = 'ANIMALS_-_ALL';
    $this->desc = [
      clienttranslate(
        'When you play this card, each player (including you) can choose to get 2 <SHEEP> or 2 <FOOD>; you also get 1 <CATTLE>.'
      ),
    ];
    $this->players = '4+';
  }

  public function onBuy($player)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => false,
      'childs' => [
        [
          'action' => SPECIAL_EFFECT,
          'auto' => true,
          'args' => [
            'cardId' => $this->id,
            'method' => 'getBonus',
          ],
        ],
        [
          'action' => GAIN,
          'pId' => $player->getId(),
          'args' => [
            CATTLE => 1,
          ],
          'source' => $this->name,
        ],
      ],
    ];
  }

  public function getGetBonusDescription()
  {
    return [
      'log' => clienttranslate('${resources_desc} for all players'),
      'args' => [
        'resources_desc' => '2<SHEEP> / 2<FOOD>',
      ],
    ];
  }

  public function getBonus()
  {
    $player = $this->getPlayer();
    $flow = [];
    foreach (Players::getAll() as $player2) {
      $flow[] = [
      'type' => NODE_SEQ,
      'pId' => $player2->getId(),
      'childs' => [
        [
          'type' => NODE_XOR,
          'optional' => true,
          'forceConfirmation' => true,
          'pId' => $player2->getId(),
          'childs' => [
            [
               'action' => GAIN,
               'args' => [
                 FOOD => 2,
                 'pId' => $player2->getId(),
               ],
               'source' => $this->name,
             ],
            [
               'action' => GAIN,
               'args' => [
                 SHEEP => 2,
                 'pId' => $player2->getId(),
               ],
               'source' => $this->name,
            ],
          ]
        ]
      ]
    ];

    }
    Engine::insertAsChild(['type' => NODE_SEQ, 'childs' => $flow]);
  }
}
