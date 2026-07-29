<?php
namespace AGR\Cards\C;

class C33_GreeningPlan extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C33_GreeningPlan';
    $this->name = clienttranslate('Greening Plan');
    $this->deck = 'C';
    $this->number = 33;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'During scoring, if you then have at least 2/4/5/6 unplanted fields, you get 1/2/3/5 bonus <SCORE>.'
      ),
    ];
    $this->cost = [
      FOOD => 3,
    ];
    $this->extraVp = true;
    $this->isCorbariusOrDulcinaria = true;

    $this->rulings = [
      clienttranslate('<PIG> held on unplanted field tiles from __Mud Patch__ (A011) do not affect scoring of this card.'),
      clienttranslate('Fields are checked after the final harvest is completed.'),
    ];
  }
  
  public function computeBonusScore()
  {  
    $bonus = 0;
    $player = $this->getPlayer();    
    $emptyFields = $player->board()->countEmptyLogicalFields();

    if ($emptyFields >= 2) {$bonus = 1;}
    if ($emptyFields >= 4) {$bonus = 2;}
    if ($emptyFields >= 5) {$bonus = 3;}
    if ($emptyFields >= 6) {$bonus = 5;}

    if ($bonus > 0) {
      $this->addBonusScoringEntry($bonus);
    }
  }
}
