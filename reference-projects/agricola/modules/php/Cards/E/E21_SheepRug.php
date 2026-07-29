<?php
namespace AGR\Cards\E;
use AGR\Managers\ActionCards;
use AGR\Managers\Meeples;
use AGR\Managers\Farmers;
use AGR\Helpers\Utils;

class E21_SheepRug extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E21_SheepRug';
    $this->name = clienttranslate('Sheep Rug');
    $this->deck = 'E';
    $this->author = 'beso';
    $this->number = 21;
    $this->category = 'ACTION_-_FAMILY_GROWTH';
    $this->desc = [
      clienttranslate(
        'You can use any __Wish for Children__ action space, even if it is occupied by another player\'s person.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      SHEEP => 1,
    ];
    $this->prerequisite = clienttranslate('4 Sheep');
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    return $player->countAnimalsOnBoard()[SHEEP] >= 4 && parent::isBuyable($player, $ignoreResources, $args);
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