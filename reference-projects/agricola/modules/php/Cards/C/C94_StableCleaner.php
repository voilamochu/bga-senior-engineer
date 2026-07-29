<?php
namespace AGR\Cards\C;
use AGR\Cards\B\B85_FarmHand;
use AGR\Helpers\Utils;

class C94_StableCleaner extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C94_StableCleaner';
    $this->name = clienttranslate('Stable Cleaner');
    $this->deck = 'C';
    $this->number = 94;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'At any time, you can take the __Build Stables__ action without placing a person. If you do, each stable costs you 1 <WOOD> and 1 <FOOD>.'
      ),
    ];
    $this->players = '1+';
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isAnytime($event) && !$this->isFlagged();
  }
  
  public function onPlayerAtAnytime($player, $event)
  {
    $stablesFlow = B85_FarmHand::wrapStablesWithFarmHandIfPlayed($player, [
      'action' => STABLES,
      'cardId' => $this->id,
      'args' => [
        'costs' => Utils::formatCost([WOOD => 1, FOOD => 1]),
      ],
    ]);

    $childs = [
      $this->flagCardNode(),
      $stablesFlow,
      $this->unflagCardNode(),
    ];

    return [
      'type' => NODE_SEQ,
      'childs' => $childs,
    ];
  }
}

