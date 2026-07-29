<?php
namespace AGR\Cards\D;
use AGR\Managers\ActionCards;
use AGR\Managers\Meeples;
use AGR\Helpers\Utils;
use AGR\Core\Globals;

class D50_ForeignAid extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D50_ForeignAid';
    $this->name = clienttranslate('Foreign Aid');
    $this->deck = 'D';
    $this->number = 50;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 6 <FOOD>. You may no longer use the action spaces of rounds 12 to 14.'
      ),
    ];
    $this->prerequisite = clienttranslate('Play in Round 11 or Before');
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $turn = Globals::getTurn();
    if ($turn > 11) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    return $this->gainNode([FOOD => 6]);
  }

  public function onPlayerComputeArgsPlaceFarmerRemoval($player)
  {
    return ActionCards::getVisible($player)
      ->filter(function ($card) {
        return $card->getTurn() >= 12;
      })
      ->getIds();
  }
}
