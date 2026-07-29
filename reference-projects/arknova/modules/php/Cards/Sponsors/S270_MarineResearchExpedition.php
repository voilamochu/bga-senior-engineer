<?php

namespace ARK\Cards\Sponsors;

class S270_MarineResearchExpedition extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'S270_MarineResearchExpedition';
    $this->number = 270;
    $this->name = clienttranslate('Marine Research Expedition');
    $this->lvl = 5;
    $this->effects = [
      PASSIVE => [
        clienttranslate('For each sea animal icon you play into your zoo, you may either do a) or b):'),
        clienttranslate('a) **Expedition**: discard 1 of your played person Sponsor cards (not from your hand) to gain 1 conservation point.'),
        clienttranslate('You lose all future benefits of the person Sponsor card and any icons on it, but you do not have to give back anything you gained from the person before.'),
        clienttranslate('b) **Scuba Dive 3**: reveal the 3 topmost cards of the deck. Choose 1 Sponsor card and add it to your hand. Discard the other cards.')
      ],
    ];
    $this->categories = [SCIENCE, SEA_ANIMAL];
    $this->wave = true;

    $this->listeningIcon = SEA_ANIMAL;
    $this->listeningBonuses = [
      [
        'type' => NODE_XOR,
        'childs' => [
          [
            'action' => EXPEDITION
          ],
          [
            'action' => SCUBA_DIVE,
            'args' => ['n' => 3]
          ]
        ],
        'optional' => true,
      ]
    ];
  }
}
