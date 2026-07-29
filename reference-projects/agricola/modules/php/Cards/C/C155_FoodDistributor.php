<?php
namespace AGR\Cards\C;
use AGR\Managers\ActionCards;
use AGR\Managers\Farmers;
use AGR\Core\Globals;

class C155_FoodDistributor extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C155_FoodDistributor';
    $this->name = clienttranslate('Food Distributor');
    $this->deck = 'C';
    $this->number = 155;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <GRAIN> and, at the start of this returning home phase, an amount of <FOOD> equal to the number of occupied Round 1-14 action spaces.'
      ),
    ];
    $this->players = '4+';
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function onBuy($player)
  {
$turn = Globals::getTurn();
$this->setExtraDatas('turn', $turn);
    return $this->gainNode([GRAIN => 1]);
  }
  
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && 
	  $event['type'] == 'StartReturnHome' &&
      !$this->isFlagged();
  }
  


  public function onPlayerStartReturnHome($player, $event)
  {
    $cards = ActionCards::getVisible()
      ->filter(function ($card) {
        return $card->getInitialLocation() != 'board' &&
          !Farmers::getOnCard($card->getId())->empty();
    });
    $n = $cards->count();
    $turn=Globals::getTurn();
    $oldturn=$this->getExtraDatas('turn');
    if(!($turn==$oldturn)){
      return $this->flagCardNode();
    }
    if ($n == 0) {
      return $this->flagCardNode();
    }
    
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->gainNode([FOOD => $n]),
        $this->flagCardNode(),
      ],
    ];
  }
}
