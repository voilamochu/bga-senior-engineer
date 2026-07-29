<?php
namespace ARK\Cards\Sponsors;

class S220_FederalGrants extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S220_FederalGrants';
    $this->number = 220;
    $this->name = clienttranslate('Federal Grants');
    $this->lvl = 4;
    $this->categories = [SCIENCE];
    $this->effects = [
      IMMEDIATE => [clienttranslate('Gain 3 money.')],
      INCOME => [clienttranslate('Gain 3 money.')],
      ENDGAME => [clienttranslate('Gain 1 conservation point if your zoo has 9 or more reputation.')],
    ];
  }

  public function getImmediate()
  {
    return [[MONEY => 3]];
  }

  public function getIncome()
  {
    return [[MONEY => 3]];
  }

  public function score()
  {
    $n = $this->getPlayer()->getReputation();
    $this->scoreConservation($n, [9 => 1]);
  }
}
