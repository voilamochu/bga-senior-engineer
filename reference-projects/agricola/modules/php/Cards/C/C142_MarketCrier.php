<?php
namespace AGR\Cards\C;

use AGR\Managers\Players;
use AGR\Core\Engine;

class C142_MarketCrier extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C142_MarketCrier';
    $this->name = clienttranslate('Market Crier');
    $this->deck = 'C';
    $this->number = 142;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Grain Seeds__ action space, you can get an additional 1 <GRAIN> and 1 <VEGETABLE>. If you do, each other player gets 1 <GRAIN>.'
      ),
    ];
    $this->players = '3+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'GrainSeeds');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    $flow = [];

    $cardOwner = $this->getPlayer();

    return [
      'countAsUse' => true,
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        [
          'action' => GAIN,
          'pId' => $player->getId(),
          'args' => [
            GRAIN => 1,
            VEGETABLE => 1,
          ],
          'source' => $this->name,
        ],
        [
          'action' => SPECIAL_EFFECT,
          'auto' => true,
          'args' => [
            'cardId' => $this->id,
            'method' => 'getBonus',
          ],
        ],
      ],
    ];
  }

  public function getGetBonusDescription()
  {
    return [
      'log' => clienttranslate('${resources_desc} for other players'),
      'args' => [
        'resources_desc' => '1<GRAIN>',
      ],
    ];
  }

  public function getBonus()
  {
    $player = $this->getPlayer();
    $flow = [];
    foreach (Players::getAll() as $player2) {
      if ($player->getId() == $player2->getId()) {
        continue;
      }
      $flow[] = $this->gainNode([GRAIN => 1], $player2->getId());
    }
    Engine::insertAsChild(['type' => NODE_SEQ, 'childs' => $flow]);
  }
}
