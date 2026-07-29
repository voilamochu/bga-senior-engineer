<?php
namespace ARK\Cards\FinalScoring;

class F007_FavoriteZoo extends \ARK\Models\FinalScoring
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'F007_FavoriteZoo';
    $this->number = 7;
    $this->name = clienttranslate('Favorite Zoo');
    $this->icon = REPUTATION;
    $this->desc = clienttranslate('Gain <CONSERVATION> for **reputation** in your zoo');
    $this->scoreMap = [6 => 1, 9 => 2, 12 => 3, 15 => 4];
  }

  public function getQuantity()
  {
    return $this->getPlayer()->getReputation();
  }
}
