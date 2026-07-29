<?php

namespace ARK\Cards\Sponsors;

class S252_SpottedHyenaCompound extends UniqueBuildingSponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S252_SpottedHyenaCompound';
    $this->number = 252;
    $this->name = clienttranslate('Spotted Hyena Compound');
    $this->lvl = 5;
    $this->enclosureRequirements = [
      ROCK => 1,
    ];
    $this->enclosure = 'hyena';
    $this->categories = [PREDATOR];
    $this->effects = [
      IMMEDIATE => [
        clienttranslate('Place the Spotted Hyena Compound unique building on your zoo map (adjacent to at least 1 rock space).'),
      ],
      PASSIVE => [
        '',
        clienttranslate(
          'For each predator icon you play into your zoo, reveal the top X cards of the deck (X equals the number of predator icons in your zoo). Add 1 revealed Animal card to your hand. Discard the other cards (Hunter X).'
        ),
        clienttranslate(
          'If there are no Animal cards among the drawn cards, you must discard all cards. If you play 2 predator icons into your zoo at the same time (e. g. the cheetah, Animal card 401), you may use the Hunter ability twice in a row. Both icons count both times.'
        ),
      ],
    ];
  }

  public function getIconsReaction($icons, $isOwnZoo)
  {
    if (!$isOwnZoo) {
      return [];
    }

    $reaction = [];
    $predators = $this->countIcon(PREDATOR);
    for ($i = 0; $i < ($icons[PREDATOR] ?? 0); $i++) {
      $reaction[] = [HUNTER => PREDATOR];
    }

    return $reaction;
  }
}
