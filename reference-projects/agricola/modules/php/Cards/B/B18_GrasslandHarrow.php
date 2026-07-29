<?php
namespace AGR\Cards\B;

class B18_GrasslandHarrow extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B18_GrasslandHarrow';
    $this->name = clienttranslate('Grassland Harrow');
    $this->deck = 'B';
    $this->number = 18;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Add 1 to the current round for each building resource in your supply and place 1 field on the corresponding round space. At the start of the round, you can plow the field.'
      ),
    ];
    $this->cost = [
      WOOD => '2',
    ];
    $this->prerequisite = clienttranslate('2 Occ., 1 Resource After Payment');
    $this->occupationPrerequisites = ['min' => 2];
    $this->isArtifexOrBubulcus = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Pay');
  }

  public function onPlayerAfterPay($player, $event) {
    if (($event['source'] ?? null) == $this->name) {
      $res_num = $player->countReserveResource(WOOD)+$player->countReserveResource(STONE)+$player->countReserveResource(CLAY)+$player->countReserveResource(REED);
      if($res_num == 0){
        throw new \BgaVisibleSystemException('You need to have at least 1 building resource in your supply after paying for this card');
      }
      return $this->futureMeeplesNode([FIELD => 1], ['+'.$res_num]);
    }
  }
}