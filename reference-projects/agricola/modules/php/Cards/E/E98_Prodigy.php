<?php
namespace AGR\Cards\E;

class E98_Prodigy extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E98_Prodigy';
    $this->name = clienttranslate('Prodigy');
    $this->deck = 'E';
    $this->author = 'barbarossa89';
    $this->number = 98;
    $this->category = 'BONUS_POINTS_-_GET';
    $this->desc = [
      clienttranslate(
        'If this is your 1st occupation, you immediately get 1 <SCORE> for each improvement you have. (This will not apply to improvements played after this card.)'
      ),
    ];
    $this->players = '1+';
    $this->extraVp = true;
    $this->implemented = true;
  }
  public function onBuy($player)
  {
    if ($player->countOccupations()==1){
      return $this->gainNode([SCORE => ($player->countAllImprovements())]);
    }
  }
}
