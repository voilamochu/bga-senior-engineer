<?php
namespace AGR\Cards\E;

use AGR\Core\Globals;

class E119_LandHeir extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E119_LandHeir';
    $this->name = clienttranslate('Land Heir');
    $this->deck = 'E';
    $this->author = 'oxmond';
    $this->number = 119;
    $this->category = 'BUILDING_RESOURCES_-_WOOD_(AND_CLAY)';
    $this->desc = [
      clienttranslate(
        'If you play this card in round 4 or before, place 4 <WOOD> and 4 <CLAY> on the space for round 9. At the start of this round, you get the resources.'
      ),
    ];
    $this->players = '1+';
    $this->implemented = true;
  }
   public function onBuy($player)
  {
    if (Globals::getTurn() <= 4) {
      $round=['+'.(9- Globals::getTurn())];
      return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->futureMeeplesNode([WOOD => 4], $round),
        $this->futureMeeplesNode([CLAY => 4], $round),
      ]
    ];
    }
  }
}
