<?php
namespace AGR\Cards\C;
use AGR\Managers\ActionCards;
use AGR\Managers\Farmers;
use AGR\Core\Globals;

class C129_SecondSpouse extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C129_SecondSpouse';
    $this->name = clienttranslate('Second Spouse');
    $this->deck = 'C';
    $this->number = 129;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'You can use the __Urgent Wish for Children__ action space (from round 12-13) even if it is occupied by the first person another player placed.'
      ),
    ];
    $this->players = '3+';
    $this->isCorbariusOrDulcinaria = true;
  }

  public function onPlayerComputeArgsPlaceFarmer($player)
  {
    $added = [];

    foreach (['ActionUrgentWishChildren'] as $cId) {
      if (ActionCards::get($cId)->isVisible() && $this->checkCondition($cId)) {
        $added[] = ['actionCardId' => $cId, 'playerConstraint' => $player->getId()];
      }
    }

    return $added;
  }

  public function checkCondition($cId)
  {
    $farmers = Farmers::getOnCard($cId);

    if ($farmers->count() > 2) {
      return false;
    }

    foreach ($farmers as $farmer) {
      $pId = $farmer['pId'];
      $fId = $farmer['id'];
      if (Globals::getPlacedFarmers()[$pId][0] == $fId) {
        return true;
      }
    }

    return false;
  }
}
