<?php
namespace AGR\Cards\B;

class B142_Greengrocer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B142_Greengrocer';
    $this->name = clienttranslate('Greengrocer');
    $this->deck = 'B';
    $this->number = 142;
    $this->category = CROP_PROVIDER;
    $this->desc = [clienttranslate('Each time you use the __Grain Seeds__ action space, you also get 1 <VEGETABLE>.')];
    $this->players = '3+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'GrainSeeds');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    return $this->gainNode([VEGETABLE => 1]);
  }
}
