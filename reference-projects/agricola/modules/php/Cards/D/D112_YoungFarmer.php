<?php
namespace AGR\Cards\D;
use AGR\Managers\ActionCards;

class D112_YoungFarmer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D112_YoungFarmer';
    $this->name = clienttranslate('Young Farmer');
    $this->deck = 'D';
    $this->number = 112;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Major Improvement__ action space, you also get 1 <GRAIN> and, afterward, you can take a __Sow__ action.'
      ),
    ];
    $this->players = '1+';
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return ($this->isActionCardEvent($event, 'MajorImprovement')) || 
      ($this->isActionEvent($event, 'PlaceFarmer') && $event['actionCardType'] == 'MajorImprovement');
  }
  
  public function onPlayerPlaceFarmer($player, $event)
  {
    return $this->gainNode([GRAIN => 1]);
  }  
  
  public function onPlayerAfterPlaceFarmer($player, $event)
  {
    return [
      'countAsUse' => true,
      'action' => SOW,
      'optional' => true,
    ];
  }

  public function onPlayerComputeArgsPlaceFarmer($player)
  {
    $added = [];

    foreach (['ActionMajorImprovement'] as $cId) {
      if (ActionCards::get($cId)->isVisible()) {
        $added[] = ['actionCardId' => $cId, 'ignoreResources' => true];
      }
    }

    return $added;
  }
}
