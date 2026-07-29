<?php
namespace AGR\Cards\C;

class C138_AnimalFeeder extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C138_AnimalFeeder';
    $this->name = clienttranslate('Animal Feeder');
    $this->deck = 'C';
    $this->number = 138;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'On the __Day Laborer__ action space, you also get your choice of 1 <SHEEP> or 1 <GRAIN>. Instead of that good, you can buy 1 <PIG> for 1 <FOOD> or 1 <CATTLE> for 2 <FOOD>.'
      ),
    ];
    $this->players = '3+';
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'DayLaborer');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    return [
      'type' => NODE_XOR,
      'countAsUse' => true,
      'childs' => [
        $this->gainNode([SHEEP => 1]),
        $this->gainNode([GRAIN => 1]),
        $this->payGainNode([FOOD => 1], [PIG => 1]),
        $this->payGainNode([FOOD => 2], [CATTLE => 1]),
      ],
    ];
  }
}
