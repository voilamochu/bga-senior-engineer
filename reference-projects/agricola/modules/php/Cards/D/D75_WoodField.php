<?php

namespace AGR\Cards\D;

use AGR\Models\MinorImprovement;
use AGR\Helpers\CardRulings;

class D75_WoodField extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D75_WoodField';
    $this->name = clienttranslate('Wood Field');
    $this->deck = 'D';
    $this->number = 75;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'You can plant <WOOD> on this card as though it were 2 fields, but it is considered 1 field. Sow and harvest <WOOD> on this card as you would <GRAIN>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      FOOD => 1,
    ];
    $this->prerequisite = clienttranslate('1 Occupation');
    $this->occupationPrerequisites = ['min' => 1];
    $this->holder = true;
    $this->field = true;

    $this->rulings = array_merge(
      CardRulings::fromKeys([
      'CAN_SOW_MULTIPLE',
      ]),
      [
      clienttranslate('Planted <WOOD> may not be spent during scoring for the Joinery.'),
      ]
    );
  }

  public function getFieldDetails()
  {
    return [
      'constraints' => WOOD,
    ];
  }
}
