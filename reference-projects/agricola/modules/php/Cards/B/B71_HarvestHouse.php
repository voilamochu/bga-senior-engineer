<?php
namespace AGR\Cards\B;
use AGR\Core\Globals;

class B71_HarvestHouse extends \AGR\Models\MinorImprovement
{
  protected $map = [
    1 => 0,
    2 => 0,
    3 => 0,
    4 => 0,
    5 => 1,
    6 => 1,
    7 => 1,
    8 => 2,
    9 => 2,
    10 => 3,
    11 => 3,
    12 => 4,
    13 => 4,
    14 => 5,
  ];

  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B71_HarvestHouse';
    $this->name = clienttranslate('Harvest House');
    $this->deck = 'B';
    $this->number = 71;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, if the number of completed harvests is equal to the number of occupations you played, you immediately get 1 <FOOD>, 1 <GRAIN>, and 1 <VEGETABLE>.'
      ),
    ];
    $this->vp = 2;
    $this->cost = [
      WOOD => 1,
      CLAY => 1,
      REED => 1,
    ];
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onBuy($player)
  {
	$occs = $this->getPlayer()->countOccupations();
	$turn = Globals::getTurn();
    $harvests = $this->map[$turn];
	
	if ($occs == $harvests) {
      return $this->gainNode([FOOD => 1, GRAIN => 1, VEGETABLE => 1]);
	}
  }  
}
