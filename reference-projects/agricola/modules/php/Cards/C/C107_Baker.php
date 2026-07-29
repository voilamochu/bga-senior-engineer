<?php
namespace AGR\Cards\C;

class C107_Baker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C107_Baker';
    $this->name = clienttranslate('Baker');
    $this->deck = 'C';
    $this->number = 107;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card and at the start of each feeding phase, you can take a __Bake Bread__ action.'
      ),
    ];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'StartHarvestFeedingPhase';
  }

  public function onBuy($player)
  {
    return [
      'action' => EXCHANGE,
      'optional' => true,
      'pId' => $player->getId(),
      'args' => [
        'trigger' => BREAD,
      ],
    ];
  }

  public function onPlayerStartHarvestFeedingPhase($player)
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
