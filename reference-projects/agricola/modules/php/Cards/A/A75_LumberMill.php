<?php
namespace AGR\Cards\A;
use AGR\Helpers\Utils;

class A75_LumberMill extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A75_LumberMill';
    $this->name = clienttranslate('Lumber Mill');
    $this->deck = 'A';
    $this->number = 75;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [clienttranslate('Every improvement costs you 1 <WOOD> less.')];
    $this->vp = 2;
    $this->cost = [
      STONE => 2,
    ];
    $this->prerequisite = clienttranslate('At most 3 Occupations');
    $this->occupationPrerequisites = ['max' => 3];
  }

  public function onPlayerComputeCardCosts($player, &$args)
  {
    if (!in_array($args['card']->getType(), [MAJOR, MINOR])) {
      return;
    }

    Utils::addBonus($args['costs'], [WOOD => -1], $this->id);
  }
}
