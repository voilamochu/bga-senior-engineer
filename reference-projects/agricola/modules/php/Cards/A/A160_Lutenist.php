<?php
namespace AGR\Cards\A;

class A160_Lutenist extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A160_Lutenist';
    $this->name = clienttranslate('Lutenist');
    $this->deck = 'A';
    $this->number = 160;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time another player uses the __Traveling Players__ accumulation space, you get 1 <FOOD> and 1 <WOOD>. Immediately after, you can buy exactly 1 <VEGETABLE> for 2 <FOOD>.'
      ),
    ];
    $this->players = '4+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer', 'opponent') && $event['actionCardType'] == 'TravelingPlayers';
  }

  public function onOpponentAfterPlaceFarmer($player, $event)
  {
    $cardOwnerId = $this->getPlayer()->getId();
    return [
      'type' => NODE_SEQ,
      'pId' => $cardOwnerId,
      'childs' => [
        $this->gainNode([FOOD => 1, WOOD => 1]),
        $this->payGainNode([FOOD => 2], [VEGETABLE => 1], clienttranslate('Lutenist\'s effect')),
      ],
    ];
  }

  // LEGACY, remove later
  public function onOpponentImmediatelyAfterPlaceFarmer($player, $event)
  {
    $cardOwnerId = $this->getPlayer()->getId();
    return [
      'type' => NODE_SEQ,
      'pId' => $cardOwnerId,
      'childs' => [
        $this->gainNode([FOOD => 1, WOOD => 1]),
        $this->payGainNode([FOOD => 2], [VEGETABLE => 1], clienttranslate('Lutenist\'s effect')),
      ],
    ];
  }
}
