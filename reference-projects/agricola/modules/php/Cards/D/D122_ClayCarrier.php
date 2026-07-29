<?php
namespace AGR\Cards\D;
use AGR\Helpers\Utils;

class D122_ClayCarrier extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D122_ClayCarrier';
    $this->name = clienttranslate('Clay Carrier');
    $this->deck = 'D';
    $this->number = 122;
    $this->isCorbariusOrDulcinaria = true;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 2 <CLAY>. At any time, but only once per round, you can buy 2 <CLAY> for 2 <FOOD>.'
      ),
    ];
    $this->players = '1+';
  }

  public function onBuy($player)
  {
    return $this->gainNode([CLAY => 2]);
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
        $this->payGainNode([FOOD => 2], [CLAY => 2], null, false),
      ],
    ];
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    return $this->unflagCardNode();
  }
}
