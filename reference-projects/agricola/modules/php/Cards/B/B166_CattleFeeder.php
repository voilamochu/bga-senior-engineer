<?php
namespace AGR\Cards\B;

class B166_CattleFeeder extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B166_CattleFeeder';
    $this->name = clienttranslate('Cattle Feeder');
    $this->deck = 'B';
    $this->number = 166;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate('Each time you use the __Grain Seeds__ action space, you can also buy 1 <CATTLE> for 1 <FOOD>.'),
    ];
    $this->players = '4+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'GrainSeeds');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    //'actionId' => 'CattleFeeder',
    return $this->payGainNode([FOOD => 1], [CATTLE => 1]);
  }
}
