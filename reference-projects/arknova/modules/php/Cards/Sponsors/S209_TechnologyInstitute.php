<?php
namespace ARK\Cards\Sponsors;

class S209_TechnologyInstitute extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S209_TechnologyInstitute';
    $this->number = 209;
    $this->name = clienttranslate('Technology Institute');
    $this->lvl = 5;
    $this->categories = [SCIENCE];
    $this->effects = [
      IMMEDIATE => [clienttranslate('Gain 1 X-token. (Remember, you cannot have more than 5 X-tokens at any time.)')],
      INCOME => [clienttranslate('Gain 1 X-token. (Remember, you cannot have more than 5 X-tokens at any time.)')],
      ENDGAME => [clienttranslate('Gain 1 conservation point if you have 3 universities in your zoo.')],
    ];
  }

  public function getImmediate()
  {
    return [[XTOKEN => 1]];
  }

  public function getIncome()
  {
    return [[XTOKEN => 1]];
  }

  public function score()
  {
    $n = $this->getPlayer()->countUniversities();
    $this->scoreConservation($n, [3 => 1]);
  }
}
