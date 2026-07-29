<?php
namespace AGR\Cards\E;
use AGR\Core\Globals;

class E155_Visionary extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E155_Visionary';
    $this->name = clienttranslate('Visionary');
    $this->deck = 'E';
    $this->author = 'chris';
    $this->number = 155;
    $this->category = 'GOODS_-_GET';
    $this->desc = [
      clienttranslate(
        'If you play this card in round 4 or before, you get 1 <STONE>, 1 <VEGETABLE>, and 2 <PIG>. You cannot grow your family until round 11, unless all other players already have.'
      ),
    ];
    $this->players = '4+';
  }

  public function onBuy($player)
  {
    if (Globals::getTurn() <= 4) {
      return $this->gainNode([STONE => 1, VEGETABLE => 1, PIG => 2]);
    }
  }

  // Rest of functionality in Actions/WishChildren.php -> isDoable()
}
