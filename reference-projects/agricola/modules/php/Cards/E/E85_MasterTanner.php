<?php
namespace AGR\Cards\E;
use AGR\Core\Globals;
use AGR\Core\Engine;
use AGR\Managers\Meeples;

class E85_MasterTanner extends \AGR\Models\Occupation {
  public function __construct($row) {
    parent::__construct($row);
    $this->id = 'E85_MasterTanner';
    $this->name = clienttranslate('Master Tanner');
    $this->deck = 'E';
    $this->author = 'maninteitz';
    $this->number = 85;
    $this->category = 'FARMYARD_-_PLACE_FOR_PERSON';
    $this->desc = [
      clienttranslate(
        'For each <PIG> or <CATTLE> you turn into <FOOD>, you can place 1 of that <FOOD> on this card. While its <FOOD> equals your number of rooms, this card provides room for 1 person.'
      ),
    ];
    $this->players = '1+';
    $this->holder = true;
    $this->implemented = true;
  }

  public function isListeningTo($event): bool {
    $player = $this->getPlayer();
    if(!$player->hasPlayedCard($this->id)) {
      return false;
    }
    if($this->isActionEvent($event, 'Exchange')) {
      $this->checkCattleOrPigCooked($event);
      return false;
    }
    $n = $this->getExtraDatas('E85Cooked') ?? 0;
    return $this->isAnytime($event) && $n > 0 && $this->getExtraDatas('turnCooked') == Globals::getTurn();
  }

  public function onPlayerAtAnytime($player, $event) {
    $n = $this->getExtraDatas('E85Cooked') ?? 0;
    if($n > 0) {
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->unflagCardNode('E85Cooked'),
          [
            'action' => SPECIAL_EFFECT,
            'args' => [
              'cardId' => $this->id,
              'method' => 'moveFoodToCard',
              'args' => [$n],
            ],
          ],
        ]
      ];
    }
  }

  public function increaseCattleOrPigCooked($count) {
    if ($count > 0) {
      $current = $this->getExtraDatas('E85Cooked') ?? 0;
      $this->setExtraDatas('E85Cooked', $current + $count);
      $this->setExtraDatas('turnCooked', Globals::getTurn());
    }
  }
  
  public function checkCattleOrPigCooked($event) {
    $trades = $event['trades'];
    $exchanges = $event['exchanges'];
    $n = 0;
    foreach($trades as $tradeIndex) {
      if(is_array($tradeIndex) && $tradeIndex['source'] == 'discard') {
        continue;
      }
      $exchange = $exchanges[$tradeIndex] ?? null;

      if($exchange) {
        $fromCattleOrPig = false;
        $toFood = false;
        foreach($exchange['from'] as $resource => $amount) {
          if($resource == CATTLE || $resource == PIG) {
            $fromCattleOrPig = true;
            break;
          }
        }
        foreach($exchange['to'] as $resource => $amount) {
          if($resource == FOOD) {
            $toFood = true;
            break;
          }
        }
        if($fromCattleOrPig && $toFood) {
          $n++;
        }
      }
    }
    if($n > 0) {
      $this->setExtraDatas('E85Cooked', $n);
      $this->setExtraDatas('turnCooked', Globals::getTurn());
    }
  }

  public function getMoveFoodToCardDescription($n) {
    return [
      'log' => clienttranslate('Choose how many <FOOD> you want to move to (Master Tanner), max ${n}'),
      'args' => [
        'n' => $n,
      ],
    ];
  }

  public function argsMoveFoodToCard($n) {
    return [
      'cardId' => $this->id,
      'max' => $n,
      'description' => clienttranslate(
        '${actplayer} must choose how many <FOOD> you want to move to (Master Tanner)'
      ),
      'descriptionmyturn' => clienttranslate(
        'Choose how many <FOOD> you want to move to (Master Tanner)'
      ),
    ];
  }

  public function actMoveFoodToCard($count) {
    $flow = $this->moveResourcesToSpaceNode([FOOD => $count], $this->id);
    Engine::insertAsChild($flow);
  }

  public function hasEnoughFood() {
    $player = $this->getPlayer();
    $foodOnCard = count(Meeples::getResourcesOnCard($this->id));
    return $foodOnCard >= $player->countRooms();
  }
}
