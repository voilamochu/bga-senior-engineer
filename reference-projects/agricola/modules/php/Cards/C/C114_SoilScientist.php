<?php
namespace AGR\Cards\C;

class C114_SoilScientist extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C114_SoilScientist';
    $this->name = clienttranslate('Soil Scientist');
    $this->deck = 'C';
    $this->number = 114;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time after you use a clay/stone accumulation space, you can place 1 <STONE>/2 <CLAY> from your supply on the space to get 2 <GRAIN>/1 <VEGETABLE>, respectively.'
      ),
    ];
    $this->players = '1+';
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isCollectEvent($event, CLAY) ||
      $this->isCollectEvent($event, STONE);
  }

  public function onPlayerAfterCollect($player, $event)
  {      
    if ($this->isCollectEvent($event, CLAY)) {
      $type = STONE;
      $n = 1;
      $gain = [GRAIN => 2];
    } else {
      $type = CLAY;
      $n = 2;
      $gain = [VEGETABLE => 1];
    }
    
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'countAsUse' => true,
      'childs' => [
        $this->moveResourcesToSpaceNode([$type => $n], $event['actionCardId']),
        $this->gainNode($gain),
      ]
    ];
  }
}
