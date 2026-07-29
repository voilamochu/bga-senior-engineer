<?php

namespace ARK\Cards\Animals;

class A464_BrownSpiderMonkey extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A464_BrownSpiderMonkey';
    $this->number = 464;
    $this->name = clienttranslate('Brown Spider Monkey');
    $this->latin = clienttranslate('Ateles hybridus - Critically Endangered');
    $this->cost = 17;
    $this->appeal = 6;
    $this->conservation = 1;
    $this->enclosureSize = 4;
    $this->categories = [PRIMATE];
    $this->continents = [AMERICAS];
    $this->prerequisites = [
      PRIMATE => 1,
    ];
    $this->ability = [INVENTIVE => 0];
  }

  public function getInventiveTokens()
  {
    $map = [0 => 1, 1 => 1, 2 => 1, 3 => 2, 4 => 2];
    $icons = $this->getPlayer()->countCardIcon(PRIMATE);

    return $map[$icons] ?? 3;
  }
}
