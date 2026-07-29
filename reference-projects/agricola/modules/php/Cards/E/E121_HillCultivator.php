<?php
namespace AGR\Cards\E;

class E121_HillCultivator extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E121_HillCultivator';
    $this->name = clienttranslate('Hill Cultivator');
    $this->deck = 'E';
    $this->author = 'inoshishi';
    $this->number = 121;
    $this->category = 'BUILDING_RESOURCES_-_CLAY';
    $this->desc = [
      clienttranslate(
        'Each time you use the __Grain Seeds__ or __Vegetable Seeds__ action space, you also get 2 or 3 <CLAY>, respectively.'
      ),
    ];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'GrainSeeds') ||
	  $this->isActionCardEvent($event, 'VegetableSeeds');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    $n = $this->isActionCardEvent($event, 'GrainSeeds') ? 2 : 3;
    return $this->gainNode([CLAY => $n]);
  }
}