<?php
namespace AGR\Cards\D;

class D6_PetrifiedWood extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D6_PetrifiedWood';
    $this->name = clienttranslate('Petrified Wood');
    $this->deck = 'D';
    $this->number = 6;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [clienttranslate('Immediately exchange up to 3 <WOOD> for 1 <STONE> each.')];
    $this->passing = true;
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];
    $this->isCorbariusOrDulcinaria = true;
  }

  public function onBuy($player)
  {
    return [
      'type' => NODE_XOR,
      'customDescription' => 'Pay <WOOD>, gain <STONE>',
      'childs' => [
        $this->payGainNode([WOOD => 1], [STONE => 1], null, false),
        $this->payGainNode([WOOD => 2], [STONE => 2], null, false),
        $this->payGainNode([WOOD => 3], [STONE => 3], null, false),
      ]
    ];
  }
}
