<?php
namespace AGR\Cards\A;
use AGR\Managers\ActionCards;
use AGR\Managers\Meeples;
use AGR\Managers\Farmers;
use AGR\Helpers\Utils;

class A26_SleepingCorner extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A26_SleepingCorner';
    $this->name = clienttranslate('Sleeping Corner');
    $this->deck = 'A';
    $this->number = 26;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'You can use any __Wish for Children__ action space even if it is occupied by one other player\'s person.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 1,
    ];
    $this->prerequisite = clienttranslate('2 Grain Fields');
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $grainFields = $player->board()->getGrainFields();
    if (count($grainFields) < 2) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onPlayerComputeArgsPlaceFarmer($player)
  {
    $added = [];

    $cards = ActionCards::getVisible($player)
    ->filter(function ($space) {
      return $space->getActionCardType() == 'WishChildren';
    });

    foreach ($cards as $card) {
      if ($this->checkConditions($card->getId())) {
        $added[] = ['actionCardId' => $card->getId(), 'playerConstraint' => $player->getId()];
      }
    }

    return $added;
  }

  public function checkConditions($cId)
  {
    $card = ActionCards::get($cId);

    $farmers = Farmers::getOnCard($cId);
    $n = 0;
    foreach ($farmers as $farmer) {
      if ($farmer['state'] == CHILD) {
        continue;
      }
      $n++;

      if ($n > 1) {
        return false;
      }
    }

    return true;
  }
}
