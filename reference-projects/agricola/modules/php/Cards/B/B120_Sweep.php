<?php
namespace AGR\Cards\B;

use AGR\Core\Globals;
use AGR\Models\ActionCard;
use AGR\Helpers\CardRulings;

class B120_Sweep extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B120_Sweep';
    $this->name = clienttranslate('Sweep');
    $this->deck = 'B';
    $this->number = 120;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate('Each time before you use the action space above the most recent round 1-14 action space, you get 2 <CLAY>.'),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;

    $this->rulings = CardRulings::fromKeys([
      'UPDATED_DIGITAL',
    ]);
  }

  private function getAboveActionCardId()
  {
    // "Most recent round 1–14 space" is the current turn on BGA
    $turn = Globals::getTurn();

    return ActionCard::getAboveSpaceForRound($turn);
  }

  public function isListeningTo($event)
  {
    // Match Legworker pattern. Listen to all action card placements
    // then filter inside the trigger.
    return $this->isActionCardEvent($event, null);
  }

  // Pre action reward so it can change affordability
  public function onPlayerPlaceFarmer($player, $event)
  {
    $cardId = $event['actionCardId'];   // always present

    $aboveId = $this->getAboveActionCardId();
    if ($aboveId === null || $cardId != $aboveId) {
      return null;
    }

    return $this->gainNode([CLAY => 2]);
  }

  // Make the "above" space clickable when the +2 clay makes it affordable
  public function onPlayerComputeArgsPlaceFarmer($player)
  {
    $aboveId = $this->getAboveActionCardId();
    if ($aboveId === null) {
      return [];
    }

    return [
      [
        'actionCardId' => $aboveId,
        'ignoreResources' => true,
      ],
    ];
  }
}
