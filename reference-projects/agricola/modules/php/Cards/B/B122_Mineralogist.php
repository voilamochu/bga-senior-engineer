<?php
namespace AGR\Cards\B;

class B122_Mineralogist extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B122_Mineralogist';
    $this->name = clienttranslate('Mineralogist');
    $this->deck = 'B';
    $this->number = 122;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use a clay/stone accumulation space, you also get 1 of the other good, <STONE>/<CLAY>.'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isBeforeCollectEvent($event, CLAY) ||
      $this->isBeforeCollectEvent($event, STONE);
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    $resource = $this->isBeforeCollectEvent($event, CLAY) ? STONE : CLAY;

    return $this->gainNode([$resource => 1]);
  } 
}
