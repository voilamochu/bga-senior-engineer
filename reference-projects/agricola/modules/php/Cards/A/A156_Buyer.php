<?php
namespace AGR\Cards\A;

class A156_Buyer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A156_Buyer';
    $this->name = clienttranslate('Buyer');
    $this->deck = 'A';
    $this->number = 156;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time another player uses a reed, stone, sheep, or wild boar accumulation space, you can pay them 1 <FOOD> to get 1 good of the respective type from the general supply.'
      ),
    ];
    $this->players = '4+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    $cards = [
      'ActionReedBank',
      'ActionEasternQuarry',
      'ActionWesternQuarry',
      'ActionSheepMarket',
      'ActionPigMarket',
    ];

    return $this->isActionEvent($event, 'PlaceFarmer', 'opponent') && in_array($event['actionCardId'], $cards);
  }
  
  public function onOpponentAfterPlaceFarmer($player, $event)
  {
    $map = [
      'ActionReedBank' => REED,
      'ActionEasternQuarry' => STONE,
      'ActionWesternQuarry' => STONE,
      'ActionSheepMarket' => SHEEP,
      'ActionPigMarket' => PIG
    ];

    $resource = $map[$event['actionCardId']];

    return [
      'type' => NODE_SEQ,
      'pId' => $this->pId,
      'childs' => [
        [
          'type' => NODE_SEQ,
          'optional' => true,
          'forceConfirmation' => true,
          'pId' => $this->pId,
          'childs' => [
            $this->payNode([FOOD => 1], null, 1, $player->getId()),
            $this->gainNode([$resource => 1]),
          ],
        ],
      ],
    ];
  }
}
