<?php
namespace AGR\Cards\C;

class C4_WritingBoards extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C4_WritingBoards';
    $this->name = clienttranslate('Writing Boards');
    $this->deck = 'C';
    $this->number = 4;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [clienttranslate('You immediately get 1 <WOOD> for each occupation you have in front of you.')];
    $this->passing = true;
    $this->cost = [
      FOOD => 1,
    ];
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function onBuy($player)
  {
    $occs = $player->countOccupations();

    if ($occs > 0) {
      return $this->gainNode([WOOD => $occs]);
    }
  }
}
