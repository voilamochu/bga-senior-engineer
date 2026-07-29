<?php
namespace AGR\Cards\B;
use AGR\Core\Globals;
use AGR\Helpers\CardRulings;
use AGR\Helpers\Utils;

class B104_SheepWalker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B104_SheepWalker';
    $this->name = clienttranslate('Sheep Walker');
    $this->deck = 'B';
    $this->number = 104;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'At any time, you can exchange 1 <SHEEP> on your farmyard for either 1 <PIG>, 1 <VEGETABLE>, or 1 <STONE>.'
      ),
    ];
    $this->players = '1+';

    $this->rulings = CardRulings::fromKeys([
      'ANIMALS_MUST_BE_ACCOMMODATED',
    ]);
  }

  public function getExchanges()
  {
    // Pending sheep can't be exchanged (must be accommodated first; excess is discarded before any
    // exchange could free a slot) — except in the breed phase, where Reorganize's silent-kill has
    // already vetted any sheep that reaches the cook window
    $canExchange = $this->getPlayer()->countAnimalsInReserve()[SHEEP] == 0 || Globals::isBreedPhase();

    // ANYTIME kept alongside HARVEST so turn-14 exchanges still surface in ANYTIME lookups
    // (e.g. the cook-vs-discard check in Reorganize::actReorganize)
    $harvest = Globals::isHarvest() && Globals::getTurn() == 14 ? [HARVEST, ANYTIME] : null;

    return $canExchange
      ? [
        Utils::formatExchange([SHEEP => [PIG => 1]], $this->name, $harvest) + ['cardId' => $this->id],
        Utils::formatExchange([SHEEP => [VEGETABLE => 1]], $this->name, $harvest) + ['cardId' => $this->id],
        Utils::formatExchange([SHEEP => [STONE => 1]], $this->name, $harvest) + ['cardId' => $this->id],
      ]
      : [];
  }
  
  public function enforceReorganizeOnLastHarvest()
  {
    $sheep = $this->getPlayer()->countAnimalsOnBoard()[SHEEP];
    return $sheep > 0;
  }  
}
