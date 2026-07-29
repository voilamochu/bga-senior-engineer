<?php
namespace AGR\Cards\E;

class E29_Heirloom extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E29_Heirloom';
    $this->name = clienttranslate('Heirloom');
    $this->deck = 'E';
    $this->author = 'inoshishi';
    $this->number = 29;
    $this->category = 'BONUS_POINTS_-_GET';
    $this->desc = [clienttranslate('(This card has no additional effect.)')];
    $this->vp = 2;
    $this->prerequisite = clienttranslate('Your Person on Day Laborer');
    $this->implemented = true;
  }
    public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $isDaylaborerEmpty = $player
      ->getAllFarmers()
      ->filter(function ($farmer) {
        return $farmer['location'] == 'ActionDayLaborer';
      })
      ->empty();

    return !$isDaylaborerEmpty && parent::isBuyable($player, $ignoreResources, $args);
  }
}
