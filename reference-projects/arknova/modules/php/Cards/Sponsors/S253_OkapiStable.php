<?php
namespace ARK\Cards\Sponsors;

class S253_OkapiStable extends UniqueBuildingSponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S253_OkapiStable';
    $this->number = 253;
    $this->name = clienttranslate('Okapi Stable');
    $this->lvl = 6;
    $this->enclosure = 'okapi';
    $this->categories = [HERBIVORE];
    $this->effects = [
      IMMEDIATE => [clienttranslate('Place the Okapi Stable unique building on your zoo map')],
      PASSIVE => [
        clienttranslate(
          'When playing this card, place 3 player tokens from your supply on this card. For each herbivore icon you play into your zoo, you may discard 1 token from this card (return it to your supply) and play a Sponsor card for X money from your hand (X corresponds to the level of the Sponsor card). Otherwise, the usual rules for playing Sponsor cards apply. (May be used 3 times in a game.)'
        ),
      ],
    ];
  }

  public function getNTokensToAdd()
  {
    return 3;
  }

  public function getIconsReaction($icons, $isOwnZoo)
  {
    if (!$isOwnZoo || $this->getTokensOnIt()->empty()) {
      return [];
    }

    // How many icons of that type ?
    $n = $icons[HERBIVORE] ?? 0;
    return $n == 0 ? [] : [[\BUY_SPONSOR => $n]];
  }
}
