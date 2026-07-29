<?php
namespace AGR\Cards\C;

class C78_ReedHattedToad extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C78_ReedHattedToad';
    $this->name = clienttranslate('Reed-Hatted Toad');
    $this->deck = 'C';
    $this->number = 78;
    $this->isCorbariusOrDulcinaria = true;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Add 5, 7, 9, 11, and 13 to the current round and place 1 <REED> on each corresponding round space. At the start of these rounds, you get the <REED>.'
      ),
    ];
    $this->cost = [
      FOOD => 1,
    ];
  }

  public function onBuy($player)
  {
    return $this->futureMeeplesNode([REED => 1], ['+5', '+7', '+9', '+11', '+13']);
  }
}
