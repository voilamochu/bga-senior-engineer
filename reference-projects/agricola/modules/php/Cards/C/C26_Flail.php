<?php
namespace AGR\Cards\C;

class C26_Flail extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C26_Flail';
    $this->name = clienttranslate('Flail');
    $this->deck = 'C';
    $this->number = 26;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 2 <FOOD>. Each time you use the __Farmland__ or __Cultivation__ action space, you can also take a __Bake Bread__ action.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
  }

  public function onBuy($player)
  {
    return $this->gainNode([FOOD => 2]);
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'Farmland') || $this->isActionCardEvent($event, 'Cultivation');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    return [
      'countAsUse' => true,
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        [
          'action' => EXCHANGE,
          'optional' => true,
          'pId' => $player->getId(),
          'args' => [
            'trigger' => BREAD,
          ],
        ],
      ],
    ];
    }
}
