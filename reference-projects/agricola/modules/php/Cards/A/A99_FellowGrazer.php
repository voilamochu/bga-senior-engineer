<?php
namespace AGR\Cards\A;

class A99_FellowGrazer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A99_FellowGrazer';
    $this->name = clienttranslate('Fellow Grazer');
    $this->deck = 'A';
    $this->number = 99;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'During scoring, you get 2 bonus <SCORE> for each pasture you have covering at least 3 farmyard spaces.'
      ),
    ];
    $this->extraVp = true;
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function computeBonusScore()
  {
    $bonus = 0;
    $zones = $this->getPlayer()->board()->getAnimalsDropZones();
    
    foreach ($zones as $zone) {
      if ($zone['type'] == 'pasture' && count($zone['locations']) >= 3) {
       $bonus += 2;
      }
    }

    if ($bonus > 0) {
      $this->addBonusScoringEntry($bonus);
    }
  }  
}
