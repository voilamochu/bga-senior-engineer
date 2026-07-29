<?php
namespace AGR\Cards\E;
use AGR\Core\Globals;
use AGR\Helpers\Utils;

class E105_Pioneer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E105_Pioneer';
    $this->name = clienttranslate('Pioneer');
    $this->deck = 'E';
    $this->author = 'luki';
    $this->number = 105;
    $this->category = 'GOODS_-_GET';
    $this->desc = [
      clienttranslate(
        'When you play this card and each time before you use the most recent action space card, you get 1 building resource of your choice and 1 <FOOD>.'
      ),
    ];
    $this->players = '1+';
    $this->bannedStrong1or2p = true;
  }

  public function onBuy($player)
  {
    return [
      'type' => NODE_XOR,
      'childs' => [$this->gainNode([WOOD => 1,FOOD =>1]), $this->gainNode([CLAY => 1,FOOD =>1]),$this->gainNode([REED => 1,FOOD =>1]), $this->gainNode([STONE => 1,FOOD =>1])],
    ];
  }

  public function isListeningTo($event)
  {      
    $cardId = $event['actionCardId'] ?? null;
    if (!is_null($cardId)) {
      if ($cardId == Globals::getLastRevealed()) {
        $type = Utils::getActionCard($cardId)->getActionCardType();
        return $this->isActionCardEvent($event, $type);
      }
    }
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    return [
      'type' => NODE_XOR,
      'childs' => [$this->gainNode([WOOD => 1,FOOD =>1]), $this->gainNode([CLAY => 1,FOOD =>1]),$this->gainNode([REED => 1,FOOD =>1]), $this->gainNode([STONE => 1,FOOD =>1])],
    ];
  }

  public function onPlayerComputeArgsPlaceFarmer($player) 
  {
    return [['actionCardId' => Globals::getLastRevealed(), 'ignoreResources' => true]];
  }

}
