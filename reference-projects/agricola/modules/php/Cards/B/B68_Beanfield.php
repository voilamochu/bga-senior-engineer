<?php
namespace AGR\Cards\B;

class B68_Beanfield extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B68_Beanfield';
    $this->name = clienttranslate('Beanfield');
    $this->deck = 'B';
    $this->number = 68;
    $this->category = CROP_PROVIDER;
    $this->desc = [clienttranslate('This card is a field that can only grow vegetables.')];
    $this->vp = 1;
    $this->cost = [
      FOOD => 1,
    ];
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];
    $this->holder = true;
    $this->field = true;
  }

  public function getFieldDetails()
  {
    return [
      'constraints' => VEGETABLE,
    ];
  }
}
