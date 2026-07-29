<?php
namespace AGR\Cards\D;

class D97_BeggingStudent extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D97_BeggingStudent';
    $this->name = clienttranslate('Begging Student');
    $this->deck = 'D';
    $this->number = 97;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you must immediately take 1 <BEGGING> marker. At the start of each harvest, you can play 1 occupation without paying an occupation cost.'
      ),
    ];
    $this->players = '1+';
    $this->bannedStrong1or2p = true;
    $this->bannedStrong3or4p = true;
  }

  public function onBuy($player)
  {
    return $this->gainNode([BEGGING => 1]);
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'StartHarvest';
  }

  public function onPlayerStartHarvest($player, $event)
  {
    return [
      'countAsUse' => true,
      'action' => OCCUPATION,
      'optional' => true,
      'args' => [
        'cost' => [],
        'source' => $this->name,
      ],
    ];
  }
}
