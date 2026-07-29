<?php
namespace ARK\Cards\Animals;

class A403_Leopard extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A403_Leopard';
    $this->number = 403;
    $this->name = clienttranslate('Leopard');
    $this->latin = clienttranslate('Panthera pardus - Vunerable');
    $this->cost = 20;
    $this->appeal = 7;
    $this->conservation = 1;
    $this->enclosureRequirements = [ROCK => 1];
    $this->enclosureSize = 3;
    $this->categories = [PREDATOR];
    $this->continents = [AFRICA];
    $this->prerequisites = [PARTNER_ZOO => 1];
    $this->ability = [HUNTER => 4];
  }
}
