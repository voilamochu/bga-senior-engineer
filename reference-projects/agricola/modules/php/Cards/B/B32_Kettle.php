<?php

namespace AGR\Cards\B;

use AGR\Helpers\Utils;
use AGR\Helpers\CardRulings;
use AGR\Models\MinorImprovement;

class B32_Kettle extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B32_Kettle';
    $this->name = clienttranslate('Kettle');
    $this->deck = 'B';
    $this->number = 32;
    $this->category = POINTS_PROVIDER;
    $this->desc = [clienttranslate('At any time, you can exchange 1/3/5 <GRAIN> for 3/4/5 <FOOD> and 0/1/2 bonus <SCORE>.')];
    $this->cost = [
      CLAY => '1',
    ];
    $this->extraVp = true;
    $this->prerequisite = clienttranslate('1 Grain Field');
    $this->isArtifexOrBubulcus = true;

    $this->exchanges = [
      Utils::formatExchange([GRAIN => [FOOD => 3]], $this->name),
      Utils::formatExchange([GRAIN => [FOOD => 4, SCORE => 1], 'nb' => 3], $this->name) + ['scoreCardId' => $this->id],
      Utils::formatExchange([GRAIN => [FOOD => 5, SCORE => 2], 'nb' => 5], $this->name) + ['scoreCardId' => $this->id],
    ];

    $this->rulings = CardRulings::fromKeys([
      'MUST_USE_EXCHANGE_WINDOW',
    ]);
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $grainFields = $player->board()->getGrainFields();
    if (count($grainFields) < 1) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }
}
