<?php
namespace AGR\Cards\C;

use AGR\Core\Notifications;
use AGR\Core\Globals;
use AGR\Helpers\Utils;

class C25_SteamMachine extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C25_SteamMachine';
    $this->name = clienttranslate('Steam Machine');
    $this->deck = 'C';
    $this->number = 25;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each work phase, if the last action space you use is an accumulation space, you can immediately afterward take a __Bake Bread__ action.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 2,
    ];
  }

  public function isListeningTo($event)
  {
    $player = $this->getPlayer();
    return $this->isActionEvent($event, 'PlaceFarmer', 'player', true) && !$player->hasFarmerAvailable() && Utils::getActionCard($event['actionCardId'])->hasAccumulation();
  }

  // TODO: legacy, remove
  public function onPlayerAfterCollect($player, $event)
  {
    if ($player->hasFarmerAvailable()) {
      return;
    }

    if (!Utils::getActionCard($event['actionCardId'])->hasAccumulation()) {
      return;
    }

    $node = [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        [
          'action' => EXCHANGE,
          'optional' => true,
          'pId' => $player->getId(),
          'args' => [
            'trigger' => BREAD,
          ],
        ],
      ],
    ];

    if ($player->hasAdoptiveAvailable()) {
      $node = [
        'type' => NODE_SEQ,
        'optional' => true,
        'childs' => [
          [
            'action' => EXCHANGE,
            'optional' => true,
            'pId' => $player->getId(),
            'args' => [
              'trigger' => BREAD,
            ],
          ],
          [
            'action' => SPECIAL_EFFECT,
            'args' => [
              'cardId' => $this->id,
              'method' => 'forceSkip',
              'args' => [],
            ],
          ],
        ],
      ];
    }
    return $node;
  }

  public function onPlayerImmediatelyAfterPlaceFarmer($player, $event)
  {
    if ($player->hasFarmerAvailable()) {
      return;
    }

    if (!Utils::getActionCard($event['actionCardId'])->hasAccumulation()) {
      return;
    }

    $node = [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        [
          'action' => EXCHANGE,
          'optional' => true,
          'pId' => $player->getId(),
          'args' => [
            'trigger' => BREAD,
          ],
        ],
      ],
    ];

    if ($player->hasAdoptiveAvailable()) {
      $node = [
        'type' => NODE_SEQ,
        'optional' => true,
        'childs' => [
          [
            'action' => EXCHANGE,
            'optional' => true,
            'pId' => $player->getId(),
            'args' => [
              'trigger' => BREAD,
            ],
          ],
          [
            'action' => SPECIAL_EFFECT,
            'args' => [
              'cardId' => $this->id,
              'method' => 'forceSkip',
              'args' => [],
            ],
          ],
        ],
      ];
    }
    $node['countAsUse'] = true;
    return $node;
  }

  public function getForceSkipDescription()
  {
    return clienttranslate('End of turn');
  }

  public function forceSkip()
  {
    $player = $this->getPlayer();
    $skipped = Globals::getSkippedPlayers();
    $skipped[] = $player->getId();
    Globals::setSkippedPlayers($skipped);
    Notifications::message(clienttranslate('${player_name} uses Steam Machine effect, their round is over'), [
      'player_name' => $player->getName(),
    ]);
  }
}
