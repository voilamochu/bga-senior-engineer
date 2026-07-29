<?php
namespace ARK\Cards\Animals;

class A457_Mandrill extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A457_Mandrill';
    $this->number = 457;
    $this->name = clienttranslate('Mandrill');
    $this->latin = clienttranslate('Mandrillus sphinx -  Vulnerable');
    $this->cost = 28;
    $this->appeal = 9;
    $this->conservation = 2;
    $this->enclosureSize = 5;
    $this->categories = [PRIMATE, PRIMATE];
    $this->continents = [AFRICA];
    $this->prerequisites = [
      PRIMATE => 3,
    ];
    $this->ability = [MULTIPLIER => CARDS];
  }
}
