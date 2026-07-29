<?php
namespace AGR\Cards\E;

class E132_Shearer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E132_Shearer';
    $this->name = clienttranslate('Shearer');
    $this->deck = 'E';
    $this->author = 'maninteitz';
    $this->number = 132;
    $this->category = 'BONUS_POINTS';
    $this->desc = [
      clienttranslate(
        'In the field phase of each harvest, if you have at least 1/4/7 sheep, you get 1/2/3 food. (Keep the sheep.) During scoring, you get 1 bonus point for every 3 sheep.'
      ),
    ];
    $this->players = '3+';
    $this->implemented = false;
  }
}
