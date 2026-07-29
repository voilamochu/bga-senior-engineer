<?php
namespace AGR\Cards\E;

class E144_WaresSalesman extends \AGR\Models\Occupation {
  public function __construct($row) {
    parent::__construct($row);
    $this->id = 'E144_WaresSalesman';
    $this->name = clienttranslate('Wares Salesman');
    $this->deck = 'E';
    $this->author = 'mattthelesser';
    $this->number = 144;
    $this->category = 'BUILDING_RESOURCES_-_REED';
    $this->desc = [
      clienttranslate(
        'Each time any player (including you) plays or builds a card that lets them turn building resources into <FOOD>, you get exactly 1 corresponding building resource and 1 <REED>.'
      ),
    ];
    $this->players = '3+';
  }
  public function isListeningTo($event) {
    return $this->isActionEvent($event, 'Occupation', null) ||
      $this->isActionEvent($event, 'Improvement', null);
  }

  public function onAfterOccupation($player, $event) {
    return $this->gainBuildingResources($event);
  }

  public function onAfterImprovement($player, $event) {
    return $this->gainBuildingResources($event);
  }

  public function gainBuildingResources($event) {
    // Always resolve for the owner of Wares Salesman (reaction framework runs under current actor pId which leads to the wrong player getting things)
    $pId = $this->getPlayer()->getId();

    $cardId = $event['cardId'] ?? null;
    if ($cardId === null) {
      return;
    }

    $flow = [];
    if(in_array($cardId, ['A108_MushroomCollector', 'A138_Harpooner', 'A56_Basket', 'C153_PatternMaker', 'A34_Loppers', 'A48_ShavingHorse', 'A159_JoineroftheSea', 'B42_ForestInn', 'B53_SculptureCourse', 'B109_PaperMaker', 'C55_Studio', 'D155_Ebonist',
      'D133_BeerTentOperator', 'E54_Contraband', 'E106_EmergencySeller', 'Major_Joinery'])) {
      $flow[] = $this->gainNode([WOOD => 1, REED => 1], $pId);
    } if(in_array($cardId, ['A40_PottersYard', 'C55_Studio', 'D60_LargePottery', 'D107_Bellfounder', 'E39_Paintbrush', 'E54_Contraband', 'E106_EmergencySeller', 'Major_Pottery'])) {
      $flow[] = $this->gainNode([CLAY => 1, REED => 1], $pId);
    } if(in_array($cardId, ['C139_BasketmakersWife', 'D46_PelletPress', 'E54_Contraband', 'E106_EmergencySeller', 'E109_BraidMaker', 'Major_Basket'])) {
      $flow[] = $this->gainNode([REED => 2], $pId);
    } if(in_array($cardId, ['D108_StoneCarver', 'B53_SculptureCourse', 'C55_Studio', 'E54_Contraband', 'E106_EmergencySeller', 'E153_StoneSculptor'])) {
      $flow[] = $this->gainNode([STONE => 1, REED => 1], $pId);
    } 
    if($flow == []){
      return;
    }

    // No choice: just gain immediately (no XOR / confirmation to hijack turn flow)
    if (count($flow) == 1) {
      return $flow[0];
    }

    // Choice (e.g. Studio): owner chooses which corresponding building resource to gain
    return [
      'type' => NODE_XOR,
      'pId' => $pId,
      'childs' => $flow,
    ];
  }
}
