<?php
namespace AGR\Cards\B;

use AGR\Helpers\Utils;

class B128_Plumber extends \AGR\Models\Occupation {
  public function __construct($row) {
    parent::__construct($row);
    $this->id = 'B128_Plumber';
    $this->name = clienttranslate('Plumber');
    $this->deck = 'B';
    $this->number = 128;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Each time after you use the __Major Improvement__ action space, you can take a __Renovation__ action, paying 2 <CLAY> or 2 <STONE> less for the renovation.'
      ),
    ];
    $this->players = '3+';
    $this->isArtifexOrBubulcus = true;
  }

  public function isListeningTo($event) {
    return $this->isActionEvent($event, 'PlaceFarmer') &&
      $event['actionCardType'] == 'MajorImprovement';
  }

  public function onPlayerAfterPlaceFarmer($player, $event) {
    return [
      'countAsUse' => true,
      'action' => RENOVATION,
      'pId' => $this->pId,
      'optional' => true,
      'args' => ['actionCardId' => 'B128_Plumber'],
    ];
  }

  public function orderComputeCostsRenovation() {
    return [['>', 'C13_WoodSlideHammer'], ['>', 'A123_FrameBuilder']];
  }

  public function onPlayerComputeCostsRenovation($player, &$args) {
    if($args['actionCardId'] != 'B128_Plumber') {
      return;
    }

    $resource = $args['newRoomType'] == 'roomClay' ? CLAY : STONE;
    Utils::addBonusChoices($args['costs'], [
      [$resource => -1],
      [$resource => -2],
    ], $this->id);
  }
}
