<?php
namespace AGR\Cards\E;

use AGR\Core\Globals;
use AGR\Core\Engine;
use AGR\Managers\Meeples;
use AGR\Core\Notifications;

class E22_GuestRoom extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E22_GuestRoom';
    $this->name = clienttranslate('Guest Room');
    $this->deck = 'E';
    $this->author = 'nwoll';
    $this->number = 22;
    $this->category = 'FARMYARD_-_PLACE_FOR_PERSON';
    $this->desc = [
      clienttranslate(
        'Immediately place any amount of <FOOD> from your supply on this card. Once per round, you can discard 1 <FOOD> from this card to place a person from your supply in that round.'
      ),
    ];
    $this->cost = [
      WOOD => 4,
      REED => 1,
    ];
    $this->holder = true;
    $this->bannedStrong1or2p = true;
    $this->bannedStrong3or4p = true;
  }

  public function onBuy($player)
  {
    if ($player->countReserveResource(FOOD) == 0) {
      return;
    }

    return [
      'action' => SPECIAL_EFFECT,
      'optional' => true,
      'args' => [
        'cardId' => $this->id,
        'method' => 'addFood',
        'args' => [],
      ],
    ];
  }

  public function canBeActivated()
  {
    if (
      Meeples::getResourcesOnCard($this->id, null, FOOD)->count() == 0 ||
      $this->isFlagged() ||
      !$this->getPlayer()->hasFarmerInReserve()
    ) {
      return false;
    }

    return true;
  }

  public function activateFlow()
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->flagCardNode(),
        [
          'action' => SPECIAL_EFFECT,
          'pId' => $this->pId,
          'args' => [
            'cardId' => $this->id,
            'method' => 'discardFood',
          ]
        ],
        [
          'action' => PLACE_FARMER,
          'pId' => $this->pId,
          'args' => [
            'fromSupply' => true,
            'source' => $this->id,
          ],
        ],
      ]
    ];
  }

  public function isListeningTo($event)
  {
    return ($this->isPlayerEvent($event) && $event['type'] == 'StartOfTurn');
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    return $this->unflagCardNode();
  }

  public function getDiscardFoodDescription()
  {
    return [
      'log' => clienttranslate('Discard ${resources_desc} from ${card}'),
      'args' => [
        'resources_desc' => '1 <FOOD>',
        'card' => $this->name,
      ],
    ];
  }

  public function discardFood()
  {
    $food = Meeples::getResourcesOnCard($this->id, null, FOOD)->first();
    Meeples::DB()->delete($food['id']);
    Notifications::silentKill([$food]);

    Notifications::message(
      clienttranslate('${player_name} discards ${resources_desc} from Guest Room'),
      [
        'player_name' => $this->getPlayer()->getName(),
        'resources_desc' => '1 <FOOD>',
      ]
    );
  }

  public function getAddFoodDescription()
  {
    return clienttranslate('Place food on Guest Room');
  }

  public function argsAddFood()
  {
    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} must decide how much food to pay (Guest Room)'),
      'descriptionmyturn' => clienttranslate('Choose any number of food to pay (Guest Room)'),
      'max' => $this->getPlayer()->countReserveResource(FOOD),
    ];
  }

  public function actAddFood($n)
  {
    $flow = $this->moveResourcesToSpaceNode([FOOD => $n], $this->id);
    Engine::insertAsChild($flow);
  }

  public function getStartOfRoundChoice($player)
  {
    // Use it option => add the place farmer at the end of the SEQ_NODE
    $nodeUseIt = $this->activateFlow();

    // Don't use it option => EndOfRound action
    $nodeDontUseIt = [
      'action' => SPECIAL_EFFECT,
      'pId' => $player->getId(),
      'args' => [
        'cardId' => $this->id,
        'method' => 'endOfRound',
      ],
    ];

    return [
      'type' => NODE_XOR,
      'pId' => $player->getId(),
      'childs' => [$nodeUseIt, $nodeDontUseIt],
    ];
  }

  public function getEndOfRoundDescription()
  {
    return clienttranslate('End of round');
  }

  public function endOfRound()
  {
    $player = $this->getPlayer();
    $skipped = Globals::getSkippedPlayers();
    $skipped[] = $player->getId();
    Globals::setSkippedPlayers($skipped);
    Notifications::message(
      clienttranslate('${player_name} chose not to use Guest Room effect, their round is now over'),
      [
        'player_name' => $player->getName(),
      ]
    );
  }
}
