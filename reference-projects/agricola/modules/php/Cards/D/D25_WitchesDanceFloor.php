<?php
namespace AGR\Cards\D;

class D25_WitchesDanceFloor extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D25_WitchesDanceFloor';
    $this->name = ("Witches' Dance Floor");
    $this->deck = 'D';
    $this->number = 25;
    $this->isCorbariusOrDulcinaria = true;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      (
        'This card is a field that you can sow in, an occupation, and the "Fireplace" major improvement with all of its effects. You can play it only via a "Minor Improvement" action.'
      ),
    ];
    $this->prerequisite = ('see below');
    $this->implemented = false;
  }
}
