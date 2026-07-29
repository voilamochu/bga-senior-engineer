<?php
namespace ARK\Cards\Sponsors;

class S206_MedicalBreakthrough extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S206_MedicalBreakthrough';
    $this->number = 206;
    $this->name = clienttranslate('Medical Breakthrough');
    $this->lvl = 5;
    $this->categories = [SCIENCE];
    $this->prerequisites = [
      SCIENCE => 4,
    ];
    $this->effects = [
      IMMEDIATE => [
        clienttranslate(
          'Gain 2 appeal for each time you already supported a conservation project. (You can always tell how many by the player tokens missing from the left side of your zoo map.)'
        ),
      ],
      INCOME => [clienttranslate('Gain 1 conservation point in the income phase of each break.')],
    ];
  }

  public function getImmediate()
  {
    $n = $this->getPlayer()->countSupportedProjects();
    return $n == 0 ? [] : [[APPEAL => 2 * $n]];
  }

  public function getIncome()
  {
    return [[CONSERVATION => 1]];
  }
}
