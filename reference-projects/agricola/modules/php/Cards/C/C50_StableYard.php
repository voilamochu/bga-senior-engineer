<?php
namespace AGR\Cards\C;
use AGR\Core\Globals;

class C50_StableYard extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C50_StableYard';
    $this->name = clienttranslate('Stable Yard');
    $this->deck = 'C';
    $this->number = 50;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <FOOD> for each completed round left to play. At any time, you can exchange 1 <SHEEP> plus 1 <PIG> for 1 <CATTLE>.'
      ),
    ];
    $this->vp = 1;
    $this->prerequisite = clienttranslate('3 Stables and 3 Pastures');
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $pastures = count($player->board()->getPastures(true));
    $stables = $player->board()->countStablesForCards();

    if ($pastures < 3 || $stables < 3) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    $n = 14 - Globals::getTurn();

    if ($n > 0) {
      return $this->gainNode([FOOD => $n]);
    }
  }

  public function getExchanges()
  {
    return [
      [
        'source' => $this->name,
        'cardId' => $this->id,
        'triggers' => null,
        'max' => INFTY,
        'from' => [
          SHEEP => 1,
          PIG => 1,
        ],
        'to' => [CATTLE => 1],
      ],
    ];
  }

  public function enforceReorganizeOnLastHarvest()
  {
    $sheep = $this->getPlayer()->countAnimalsOnBoard()[SHEEP];
    $pig = $this->getPlayer()->countAnimalsOnBoard()[SHEEP];
    return ($sheep > 0 && $pig > 0);
  }
}
