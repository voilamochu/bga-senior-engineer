<?php
namespace AGR\Cards\A;

class A159_JoineroftheSea extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A159_JoineroftheSea';
    $this->name = clienttranslate('Joiner of the Sea');
    $this->deck = 'A';
    $this->number = 159;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time another player uses the __Fishing__/__Reed Bank__ accumulation space, you can give them 1 <WOOD> to get 2 <FOOD>/3 <FOOD> from the general supply.'
      ),
    ];
    $this->players = '4+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return ($this->isActionEvent($event, 'PlaceFarmer', 'opponent') && $event['actionCardType'] == 'Fishing') ||
      ($this->isActionEvent($event, 'PlaceFarmer', 'opponent') && $event['actionCardType'] == 'ReedBank');
  }
  
  public function onOpponentAfterPlaceFarmer($player, $event)
  {
    $n = $event['actionCardType'] == 'Fishing' ? 2 : 3;    

    return [
      'type' => NODE_SEQ,
      'pId' => $this->pId,
      'childs' => [
        [
          'type' => \NODE_SEQ,
          'optional' => true,
          'forceConfirmation' => true,
          'pId' => $this->pId,
          'childs' => [
            $this->payNode([WOOD => 1], null, 1, $player->getId()),
            $this->gainNode([FOOD => $n]),
            [
              'action' => SPECIAL_EFFECT,
              'args' => [
                'cardId' => $this->id,
                'method' => 'bumpUsed',
              ],
            ],
          ],
        ],
      ],
    ];
  }

  public function bumpUsed()
  {
    $this->incStats('used');
  }

  public function isIndependentBumpUsed()
  {
    return true;
  }
}
