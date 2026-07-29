<?php

namespace ARK\Cards\Sponsors;

use ARK\Managers\ZooCards;

class S269_ConferenceOnAustralia extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'S269_ConferenceOnAustralia';
    $this->number = 269;
    $this->name = clienttranslate('Conference On Australia');
    $this->lvl = 5;
    $this->effects = [
      PASSIVE => [
        clienttranslate('For each Australia icon you play into your zoo, replace 1 standard enclosure in your zoo with an enclosure that is 1 size larger.'),
        clienttranslate('If the enclosure is occupied, gain 2 appeal.'),
        clienttranslate('The new enclosure must cover the same spaces as the old one, plus 1 additional space.'),
        clienttranslate('If the additional space covers a placement bonus, you get it.'),
        clienttranslate('You do not get a placement bonus again for covering the spaces of the old enclosure again.')
      ],
      ENDGAME => [
        clienttranslate('Gain 1 appeal for each pouched card (using other cards with the Animal ability Pouch or zoo map 7) in your zoo (up to a maximum of 5 appeal).'),
        clienttranslate('If you released an animal into the wild that had a pouched card, or sent the Expert on Australia on an expedition, their pouched cards do not count anymore.')
      ],
    ];
    $this->continents = [AUSTRALIA];

    $this->listeningIcon = AUSTRALIA;
    $this->listeningBonuses = [[INCREASE_SIZE => 1]];
  }

  public function score()
  {
    $player = $this->getPlayer();
    $pouched = 0;
    foreach ($player->getPlayedCards() as $cId => $card) {
      $pouched += $card->getExtraDatas('pouch') ?? 0;
    }
    $pouched += ZooCards::getFiltered($this->pId, 'mapPouched')->count();

    // 5 MAX
    $pouched = min($pouched, 5);

    if ($pouched > 0) {
      $player->incAppeal($pouched, true, $this);
    }
  }
}
