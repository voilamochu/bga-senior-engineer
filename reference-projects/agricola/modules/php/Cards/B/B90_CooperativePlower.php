<?php
namespace AGR\Cards\B;

use AGR\Managers\Farmers;

class B90_CooperativePlower extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B90_CooperativePlower';
    $this->name = clienttranslate('Cooperative Plower');
    $this->deck = 'B';
    $this->number = 90;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Farmland__ action space while the __Grain Seeds__ action space is occupied, you can plow 1 additional field.'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'Farmland');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    if (!Farmers::getOnCard('ActionGrainSeeds')->empty()) {
      return [
        'action' => PLOW,
        'cardId' => $this->id,
        'optional' => true,
      ];
    }
  }
}
