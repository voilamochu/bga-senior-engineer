<?php
namespace AGR\Cards\C;
use AGR\Core\Globals;
use AGR\Helpers\Utils;

class C160_Outrider extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C160_Outrider';
    $this->name = clienttranslate('Outrider');
    $this->deck = 'C';
    $this->number = 160;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time before you use the action space on the most recently revealed action space card (after it has been placed on the round space), you get 1 <GRAIN>.'
      ),
    ];
    $this->players = '4+';
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event)
  {      
    $cardId = $event['actionCardId'] ?? null;
    if (!is_null($cardId)) {
      if ($cardId == Globals::getLastRevealed()) {
        $type = Utils::getActionCard($cardId)->getActionCardType();
        return $this->isActionCardEvent($event, $type);
      }
    }
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    return $this->gainNode([GRAIN => 1]);
  }

  public function onPlayerComputeArgsPlaceFarmer($player) 
  {
    return [['actionCardId' => Globals::getLastRevealed(), 'ignoreResources' => true]];
  }
}
