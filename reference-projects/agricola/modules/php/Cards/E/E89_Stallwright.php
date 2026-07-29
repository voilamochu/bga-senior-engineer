<?php
namespace AGR\Cards\E;

use AGR\Helpers\Utils;

class E89_Stallwright extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E89_Stallwright';
    $this->name = clienttranslate('Stallwright');
    $this->deck = 'E';
    $this->author = 'beso';
    $this->number = 89;
    $this->category = 'FARMYARD_-_STABLE_BUILDING';
    $this->desc = [
      clienttranslate(
        'After you play your 2nd, 3rd, 5th, and 7th occupation (including this one), you can build 1 stable at no cost.'
      ),
    ];
    $this->players = '1+';
    $this->implemented = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Occupation');
  }

  public function onPlayerAfterOccupation($player, $event)
  {
    $n = $player->countOccupations();
    if (($event['cardId'] ?? null) == 'E97_Beneficiary' && $n == 4) {
      $n = 3;
    }
    if(in_array($n,[2,3,5,7])){
      return [
        'action' => STABLES,
        'cardId' => $this->id,
        'optional' => true,
        'args' => ['costs' => Utils::formatCost(['max' => 1])],
      ];
    }
  }
}
