<?php
namespace AGR\Cards\E;
use AGR\Managers\Farmers;
use AGR\Managers\ActionCards;

class E129_Imitator extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E129_Imitator';
    $this->name = clienttranslate('Imitator');
    $this->deck = 'E';
    $this->author = 'nwoll';
    $this->number = 129;
    $this->category = 'ACTION';
    $this->desc = [
      clienttranslate(
        'If you have a person on the __Day Laborer__ action space, you can use non-accumulating round 1-9 action spaces even if they are occupied.'
      ),
    ];
    $this->players = '3+';
  }

  public function onPlayerComputeArgsPlaceFarmer($player)
  {
    // Must have a person on Day Laborer
    if (Farmers::getOnCard('ActionDayLaborer', $this->getPlayer()->getId())->empty()) {
      return;
    }

    // Non-accumulating round 1–9 action spaces
    $candidates = [
      'ActionGrainUtilization',
      'ActionFencing',
      'ActionMajorImprovement',
      'ActionWishChildren',
      'ActionHouseRedevelopment',
      'ActionVegetableSeeds',
    ];

    $added = [];
    foreach ($candidates as $id) {
      $card = ActionCards::get($id);
      // Only if the space is actually visible on the board now
      if ($card === null || (method_exists($card, 'isVisible') && !$card->isVisible())) {
        continue;
      }
      // Only if already occupied (free spaces are already selectable anyway)
      if (Farmers::getOnCard($id)->empty()) {
        continue;
      }

      // 'dummy' means no real player's farmer ever matches, so self-occupied is allowed.
      $added[] = [
        'actionCardId'     => $id,
        'playerConstraint' => 'dummy',
        'ignoreResources'  => false, // false so action must still be doable
      ];
    }

    return $added ?: null;
  }
}