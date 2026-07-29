<?php
namespace AGR\Cards\E;

use AGR\Core\Globals;
use AGR\Managers\Players;

class E125_DelayedWayfarer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E125_DelayedWayfarer';
    $this->name = clienttranslate('Delayed Wayfarer');
    $this->deck = 'E';
    $this->author = 'mattthelesser';
    $this->number = 125;
    $this->category = 'BUILDING_RESOURCES_-_ALL';
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 building resource of your choice and, once all people have been placed this round, you can place a person from your supply.'
      ),
    ];
    $this->players = '1+';
  }

  /**
   * On play:
   *  - Get exactly 1 building resource
   *  - Record the current round so we can trigger during this work phase
   */
  public function onBuy($player)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        [
          'type' => NODE_XOR,
          'pId'  => $player->getId(),
          'childs' => [
            $this->gainNode([WOOD => 1]),
            $this->gainNode([CLAY => 1]),
            $this->gainNode([REED => 1]),
            $this->gainNode([STONE => 1]),
          ],
        ],
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'markThisRound',
          ],
        ],
      ],
    ];
  }

  /**
   * Can this card offer its extra placement now?
   * Conditions:
   *  - We are in the same round where the card was played.
   *  - We are in the work phase.
   *  - Owner has a farmer in reserve.
   *  - No player has any normal farmers left to place.
   */
  public function canBeActivated()
  {
    $playedOnTurn = $this->getExtraDatas('turn');
    if ($playedOnTurn === null || $playedOnTurn != Globals::getTurn()) {
      return false;
    }

    if (!Globals::getWorkPhase()) {
      return false;
    }

    $owner = $this->getPlayer();
    if (!$owner->hasFarmerInReserve()) {
      return false;
    }

    // "Once all people have been placed this round" = no normal placements left for anyone
    foreach (Players::getAll() as $player) {
      if ($player->hasFarmerAvailable() || $player->hasAdoptiveAvailable() || $player->hasGuestRoomAvailable()) {
        return false;
      }
    }

    return true;
  }

  /**
   * Build the optional extra placement from supply.
   * Player may choose to place a farmer from supply once all people are placed.
   * Whether they use it or not, we clear the round flag afterwards.
   */
  public function getExtraPlacementNode()
  {
    $player = $this->getPlayer();
    $pId = $player->getId();

    return [
      'type' => NODE_SEQ,
      'pId' => $pId,
      'childs' => [
        // Optional extra placement from supply
        [
          'type' => NODE_SEQ,
          'pId' => $pId,
          'optional' => true,
          'childs' => [
            [
              'action' => PLACE_FARMER,
              'pId' => $pId,
              'args' => [
                'fromSupply' => true,
                'source' => $this->id,
              ],
            ],
          ],
        ],
        // Always clear the flag so the card cannot trigger again this round
        [
          'action' => SPECIAL_EFFECT,
          'pId' => $pId,
          'args' => [
            'cardId' => $this->id,
            'method' => 'clearThisRound',
          ],
        ],
      ],
    ];
  }

  /** Store the round we were played in */
  public function markThisRound()
  {
    $this->setExtraDatas('turn', Globals::getTurn());
  }

  /** Clear the stored round flag so we cannot trigger again this round */
  public function clearThisRound()
  {
    $this->setExtraDatas('turn', 0);
  }
}
