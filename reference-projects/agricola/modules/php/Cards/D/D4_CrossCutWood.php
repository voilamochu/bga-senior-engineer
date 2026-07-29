<?php
namespace AGR\Cards\D;

class D4_CrossCutWood extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D4_CrossCutWood';
    $this->name = clienttranslate('Cross-Cut Wood');
    $this->deck = 'D';
    $this->number = 4;
    $this->isCorbariusOrDulcinaria = true;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate('You immediately get a number of <WOOD> equal to the number of <STONE> in your supply.'),
    ];
    $this->passing = true;
    $this->cost = [
      FOOD => 1,
    ];
    $this->prerequisite = clienttranslate('3 Occupations');
    $this->occupationPrerequisites = ['min' => 3];
    $this->bannedStrong1or2p = true;
    $this->bannedStrong3or4p = true;
  }

  public function onBuy($player)
  {
    $stone = $player->countReserveResource(STONE);
    if ($stone > 0) {
      return $this->gainNode([WOOD => $stone]);
    }
  }
}
