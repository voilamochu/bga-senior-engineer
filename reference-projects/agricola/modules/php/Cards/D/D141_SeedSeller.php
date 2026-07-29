<?php
namespace AGR\Cards\D;

class D141_SeedSeller extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D141_SeedSeller';
    $this->name = clienttranslate('Seed Seller');
    $this->deck = 'D';
    $this->number = 141;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <GRAIN>. Each time you use the __Grain Seeds__ action space, you get 1 additional <GRAIN>.'
      ),
    ];
    $this->players = '3+';
  }

  public function onBuy($player)
  {
    return $this->gainNode([GRAIN => 1]);
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'GrainSeeds');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    return $this->gainNode([GRAIN => 1]);
  }
}
