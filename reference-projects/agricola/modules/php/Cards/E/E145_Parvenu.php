<?php
namespace AGR\Cards\E;
use AGR\Core\Globals;


class E145_Parvenu extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E145_Parvenu';
    $this->name = clienttranslate('Parvenu');
    $this->deck = 'E';
    $this->author = 'oxmond';
    $this->number = 145;
    $this->category = 'BUILDING_RESOURCES_-_REED';
    $this->desc = [
      clienttranslate(
        'If you play this card in round 7 or before, choose <CLAY> or <REED>: you immediately get a number of that building resource equal to the number you already have in your supply.'
      ),
    ];
    $this->players = '3+';
    $this->implemented = true;
  }
   public function onBuy($player)
  {
    if (Globals::getTurn() <= 7) {
        $clays=$player->countReserveResource(CLAY);
        $reeds=$player->countReserveResource(REED);
        if ($clays>0 && $reeds==0)
          return $this->gainNode([CLAY=>$clays]);
        elseif ($clays==0 && $reeds>0)
          return $this->gainNode([REED=>$reeds]);
        elseif ($clays>0 && $reeds>0)    
          return [
            'type' => NODE_XOR,
            'optional' => true,
            'childs' => [
              $this->gainNode([CLAY =>$clays]),
              $this->gainNode([REED =>$reeds]),
            ]
         ];
    }
  }
}
