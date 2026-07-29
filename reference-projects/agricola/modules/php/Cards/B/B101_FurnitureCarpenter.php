<?php
namespace AGR\Cards\B;
use AGR\Helpers\Utils;
use AGR\Helpers\CardRulings;
use AGR\Managers\Players;

class B101_FurnitureCarpenter extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B101_FurnitureCarpenter';
    $this->name = clienttranslate('Furniture Carpenter');
    $this->deck = 'B';
    $this->number = 101;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each harvest, if any player (including you) owns the Joinery or an upgrade thereof, you can buy exactly 1 bonus <SCORE> for 2 <FOOD>.'
      ),
    ];
    $this->extraVp = true;
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
    $this->bannedWeak1or2p = true;
    $this->bannedWeak3or4p = true;

    $this->rulings = CardRulings::fromKeys([
      'MUST_USE_EXCHANGE_WINDOW',
    ]);
  }

  public function getExchanges()
  {
    $exchanges = parent::getExchanges();

    if ($this->playedJoinery()) {
      $exchanges[] = Utils::formatExchange([FOOD => [SCORE => 1], 'nb' => 2, 'max' => 1], $this->name, [HARVEST], $this->id) + ['scoreCardId' => $this->id];
    }

    return $exchanges;
  }

  public function playedJoinery()
  {
    foreach (Players::getAll() as $player2) {
      if ($player2->hasPlayedCard('Major_Joinery')) {
        return true;
      }
    }
    return false;
  }
}
