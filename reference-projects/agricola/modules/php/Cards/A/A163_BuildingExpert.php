<?php
namespace AGR\Cards\A;

class A163_BuildingExpert extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A163_BuildingExpert';
    $this->name = clienttranslate('Building Expert');
    $this->deck = 'A';
    $this->number = 163;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Resource Market__ action space with the 1st/2nd/3rd/4th/5th person you place, you also get 1 <WOOD>/<CLAY>/<REED>/<STONE>/<STONE>.'
      ),
    ];
    $this->players = '4+';
    $this->isArtifexOrBubulcus = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'ResourceMarket');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    $map = [null, WOOD, CLAY, REED, STONE, STONE];
    $resource = $map[$player->countPlacedFarmers()];

    if (!is_null($resource)) {
      return $this->gainNode([$resource => 1]);
    }
  }
}
