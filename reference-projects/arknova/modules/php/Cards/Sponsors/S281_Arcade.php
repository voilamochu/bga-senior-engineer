<?php
namespace ARK\Cards\Sponsors;
use ARK\Helpers\Utils;
use ARK\Core\Globals;
use ARK\Managers\Players;

class S281_Arcade extends UniqueBuildingSponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S281_Arcade';
    $this->number = 281;
    $this->name = clienttranslate('Arcade');
    $this->lvl = 3;
    $this->enclosure = 'arcade';
    $this->appeal = 2;
    $this->effects = [
      IMMEDIATE => [clienttranslate('Place the Arcade unique building on your zoo map.')],
      INCOME => [
        clienttranslate('Gain 1 money for every 10 appeal of your zoo. Example: If your zoo has 28 appeal, you gain 2 money.'),
      ],
      ENDGAME => [
        clienttranslate(
          'Gain 2 appeal for each other player whose zoo has less appeal than yours (up to a maximum of 4). (In the solo game, you cannot gain appeal this way). Gain these points before any player (incl. yourself) has taken all other appeal they gain at the end of game.'
        ),
      ],
    ];
  }

  public function getIncome()
  {
    $appeal = $this->getPlayer()->getAppeal();
    $money = intdiv($appeal, 10);
    return [[\MONEY => $money]];
  }

  public function preScore()
  {
    if (!$this->isPlayed() || Globals::isSolo()) {
      return;
    }

    $player = $this->getPlayer();
    $n = 0;
    foreach (Players::getAll() as $player2) {
      if ($player2->getAppeal() < $player->getAppeal()) {
        $n++;
      }
    }
    $n = min($n, 2);
    $player->incAppeal(2 * $n, true, $this);
  }
}
