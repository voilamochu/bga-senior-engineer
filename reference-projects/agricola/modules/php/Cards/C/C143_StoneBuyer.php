<?php
namespace AGR\Cards\C;

class C143_StoneBuyer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C143_StoneBuyer';
    $this->name = clienttranslate('Stone Buyer');
    $this->deck = 'C';
    $this->number = 143;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you can immediately buy exactly 2 <STONE> for 1 <FOOD>. From the next round on, once per round, you can buy 1 <STONE> for 2 <FOOD>.'
      ),
    ];
    $this->players = '3+';
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function onBuy($player)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->flagCardNode(),
        $this->payGainNode([FOOD => 1], [STONE => 2]),
      ]
    ];
  }

  public function isListeningTo($event)
  {
    return ($this->isAnytime($event) && !$this->isFlagged()) || // Use the card
      ($this->isPlayerEvent($event) && $event['type'] == 'StartOfTurn'); // Unuse the card
  }

  public function onPlayerAtAnytime($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->flagCardNode(),      
        $this->payGainNode([FOOD => 2], [STONE => 1], null, false),
      ],
    ];
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    return $this->unflagCardNode();
  }
}
