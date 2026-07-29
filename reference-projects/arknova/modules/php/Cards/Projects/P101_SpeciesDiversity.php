<?php
namespace ARK\Cards\Projects;

class P101_SpeciesDiversity extends \ARK\Models\Projects\Project
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->type = \CARD_BASE_PROJECT;
    $this->id = 'P101_SpeciesDiversity';
    $this->number = 101;
    $this->name = clienttranslate('Species Diversity');
    $this->desc = clienttranslate('Requires different **animal category** icons in your zoo.');
    $this->icon = 'all-animals';
    $this->slots = [
      [
        'condition' => 5,
        'gain' => [CONSERVATION => 5],
      ],
      [
        'condition' => 4,
        'gain' => [CONSERVATION => 3],
      ],
      [
        'condition' => 3,
        'gain' => [CONSERVATION => 2],
      ],
    ];
  }

  public function canPlaySlot($player, $slot, $bonusIcon = 0)
  {
    $t = explode('_', $this->location);
    if ($t[0] != 'base') {
      $bonusIcon = 0;
    }
    $icons = $player->countCardIcons(true, ANIMAL_TYPES);
    $n = count($icons);
    return $n + $bonusIcon >= $slot['condition'];
  }
}
