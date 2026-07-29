<?php
namespace AGR\Cards\E;
use AGR\Managers\Meeples;
use AGR\Managers\PlayerCards;
use AGR\Core\Notifications;
use AGR\Core\Stats;
use AGR\Core\Engine;

class E53_BoarSpear extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E53_BoarSpear';
    $this->name = clienttranslate('Boar Spear');
    $this->deck = 'E';
    $this->author = 'tamos';
    $this->number = 53;
    $this->category = FOOD;
    $this->desc = [
      clienttranslate(
        'Each time you get at least 1 <PIG> outside of the breeding phase of a harvest, you can immediately turn them into 4 <FOOD> each.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 1,
      STONE => 1,
    ];
  }

  public function increasePigConverted($count) {
    if($count > 0) {
      $this->setExtraDatas('E53Converted', $count);
    }
  }

  public function isListeningTo($event)
  {
    if($this->isDuringActionEvent($event, 'Gain') ||
      $this->isDuringActionEvent($event, 'Receive') ||
      $this->isDuringActionEvent($event, 'Collect')){
      return true;
    }
    $player = $this->getPlayer();
    if (!$player->hasPlayedCard($this->id)) {
      return false;
    }
    if ($this->isActionEvent($event, 'Exchange')) {
      //print_r($event);
      $this->checkPigIsConverted($event);
      return false;
    }
    $n = $this->getExtraDatas('E53Converted') ?? 0;
    return $this->isAnytime($event) && $n > 0;
  }

  public function onPlayerAtAnytime($player, $event)
  {
    $n = $this->getExtraDatas('E53Converted') ?? 0;
    if ($n > 0) {
      for ($i = 1; $i <= $n; $i++) {
         $childpay = $this->payGainNode([PIG => $i], [FOOD => $i*4], null, false);
         if($player->hasPlayedCard('E85_MasterTanner')){
           $childall = [
             'type' => NODE_SEQ,
             'optional' => false,
             'childs' => [
               $childpay,
               [
                 'action' => SPECIAL_EFFECT,
                 'args' => [
                   'cardId' => 'E85_MasterTanner',
                   'method' => 'increaseCattleOrPigCooked',
                   'args' => [$i],
                 ],
               ],
             ],
           ];
           $childs[] = $childall;
         }
         else{
          $childs[] = $childpay;
         } 
      }
      $child = [
        'type' => NODE_XOR,
        'optional' => false,
        'childs' => $childs,
      ];
      return [
        'type' => NODE_SEQ,
        'countAsUse' => true,
        'childs' => [
          $this->unflagCardNode('E53Converted'),
          $child,
        ],
      ];
    }
  }

  public function checkPigIsConverted($event)
  {
    $trades = $event['trades'];
    $exchanges = $event['exchanges'];
    $count = 0;

    foreach ($trades as $tradeIndex) {
      if (is_array($tradeIndex) && $tradeIndex['source'] == 'discard') {
        continue;
      }
      $exchange = $exchanges[$tradeIndex] ?? null;
     
      if ($exchange) {
        $isWoodExchange = false;
        foreach ($exchange['to'] as $resource => $amount) {
          if ($resource == PIG) {
            $isWoodExchange = true;
            break;
          }
        }
        if ($isWoodExchange) {
          $count++;
        }
      }
    }
    //print($count);
    if ($count >= 0) {
      $this->setExtraDatas('E53Converted', $count);
    }
  }

  public function onBuy($player)
  {
    $this->setExtraDatas('E53Converted', 0);
    return $this->setExtraDatas('ids',[]);
  }

  public function onPlayerObtain($player, $event)
  {
    //return;
    if(!array_key_exists('meeples',$event)){
      return;
    }
    $meeples = $event['meeples'];
    $n = 0;
    $newIds = [];
    $ids = $this->getExtraDatas('ids');
    if($ids==null){
      $ids = [];
    }
    foreach ($meeples as $meeple) {
      if ($meeple['type'] == PIG && !in_array($meeple['id'],$ids)) {
        $n++;
         $ids[] = $meeple['id'];
         $newIds[] = $meeple['id'];
      }
    }
    if ($n > 0) {
      for ($i = 1; $i <= $n; $i++) {
         // Convert the exact pigs we just gained, not other (fungible) pigs the player owns.
         $childpay = $this->payGainNode([PIG => $i], [FOOD => $i*4], null, false, null, array_slice($newIds, 0, $i));
         if($player->hasPlayedCard('E85_MasterTanner')){
           $childall = [
             'type' => NODE_SEQ,
             'optional' => false,
             'childs' => [
               $childpay,
               [
                 'action' => SPECIAL_EFFECT,
                 'args' => [
                   'cardId' => 'E85_MasterTanner',
                   'method' => 'increaseCattleOrPigCooked',
                   'args' => [$i],
                 ],
               ],
             ],
           ];
           $childs[] = $childall;
         }
         else{
          $childs[] = $childpay;
         } 
      }
      $child = [
        'type' => NODE_XOR,
        'optional' => false,
        'childs' => $childs,
      ];
      $setIds = [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'setIds',
            'args' => [$ids],
          ]
        ];
       return [
        'type' => NODE_SEQ,
        'optional' => true,
        'countAsUse' => true,
        'childs' => [$child,
         $setIds,],
      ];
    }
  }

  public function setIds($ids)
  {
    $this->setExtraDatas('ids',$ids);
  }

  public function onPlayerGain($player, $event)
  {
    return $this->onPlayerObtain($player, $event);
  }

  public function onPlayerReceive($player, $event)
  {
    return $this->onPlayerObtain($player, $event);
  }

  public function onPlayerCollect($player, $event)
  {
    return $this->onPlayerObtain($player, $event);
  }
}