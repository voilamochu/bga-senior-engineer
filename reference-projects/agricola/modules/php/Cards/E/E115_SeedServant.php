<?php
namespace AGR\Cards\E;

class E115_SeedServant extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E115_SeedServant';
    $this->name = clienttranslate('Seed Servant');
    $this->deck = 'E';
    $this->author = 'beso';
    $this->number = 115;
    $this->category = 'CROPS_-_SOWING';
    $this->desc = [
      clienttranslate(
        'Each time after you use the __Grain Seeds__ action space, you can take a __Bake bread__ action. Each time after you use the __Vegetable Seeds__ action space, you can take a __Sow__ action.'
      ),
    ];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer') && ($event['actionCardType'] == 'GrainSeeds'||$event['actionCardType'] == 'VegetableSeeds');
  }

  public function onPlayerAfterPlaceFarmer($player, $event)
  {
    if($event['actionCardType'] == 'GrainSeeds'){
      return $this->bakeBreadNode();
    } 
    else{
      return [
        'action' => SOW,
        'optional' => true,
        'source' => $this->name,
      ];
    }
  }
}