<?php
namespace ARK\Cards\Sponsors;
use ARK\Helpers\Utils;
use ARK\Core\Globals;
use ARK\Managers\Players;

class S274_VictoryColumn extends UniqueBuildingSponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S274_VictoryColumn';
    $this->number = 274;
    $this->name = clienttranslate('Victory Column');
    $this->lvl = 3;
    $this->enclosure = 'victory';
    $this->appeal = 1;
    $this->effects = [
      IMMEDIATE => [
        clienttranslate('Gain 1 appeal when you play this card. Place the victory column unique building on your zoo map.'),
      ],
      INCOME => [clienttranslate('In the income phase of each break, gain 1 appeal.')],
      ENDGAME => [
        clienttranslate(
          'Gain 2 appeal for each other player whose zoo has more appeal than yours (in the solo game, you cannot gain appeal this way). Gain these points after all players have taken all other appeal they gain at the end of game.'
        ),
      ],
    ];
  }

  public function getIncome()
  {
    return [[APPEAL => 1]];
  }

  public function postScore()
  {
    if (!$this->isPlayed() || Globals::isSolo()) {
      return;
    }

    $player = $this->getPlayer();
    $n = 0;
    foreach (Players::getAll() as $player2) {
      if ($player2->getAppeal() > $player->getAppeal()) {
        $n++;
      }
    }
    $player->incAppeal(2 * $n, true, $this);
  }
}
