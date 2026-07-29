<?php
namespace ARK\Cards\Sponsors;

class S215_BreedingCooperation extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S215_BreedingCooperation';
    $this->number = 215;
    $this->name = clienttranslate('Breeding Cooperation');
    $this->lvl = 4;
    $this->prerequisites = [\PARTNER_ZOO => 2];
    $this->effects = [
      PASSIVE => [
        clienttranslate(
          'When this card is played, place 2 player tokens from your supply on the two spaces at the top of the card.'
        ),
        clienttranslate(
          'If you support a base conservation project, you may discard exactly 1 player token as any icon (return it to your supply).'
        ),
        \clienttranslate('You are not allowed to use both tokens for the same conservation project.'),
        \clienttranslate('Only the cards that are below the Association board count as base conservation projects.'),
        \clienttranslate(
          'Example: You want to support the Africa conservation project and have 3 Africa icons in your zoo. If you discard 1 player token from this card, you can support the conservation project as if you had 4 Africa icons.'
        ),
      ],
      ENDGAME => [
        clienttranslate(
          'Gain 1 conservation point if you have supported conservation projects 5 times or more. (You can always tell how many by the player tokens missing from the left side of your zoo map.)'
        ),
      ],
    ];
  }

  public function getNTokensToAdd()
  {
    return 2;
  }

  public function score()
  {
    $n = $this->getPlayer()->countSupportedProjects();
    $this->scoreConservation($n, [5 => 1]);
  }
}
