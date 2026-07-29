<?php
namespace AGR\Cards\D;

class D120_ClayDeliveryman extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D120_ClayDeliveryman';
    $this->name = clienttranslate('Clay Deliveryman');
    $this->deck = 'D';
    $this->number = 120;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 1 <CLAY> on each remaining space for rounds 6 to 14. At the start of these rounds, you get the <CLAY>.'
      ),
    ];
    $this->players = '1+';
  }

  public function onBuy($player)
  {
    return $this->futureMeeplesNode([CLAY => 1], [6, 7, 8, 9, 10, 11, 12, 13, 14]);      
  }
}
