<?php
namespace ARK\Cards\Projects;

class P102_HabitatDiversity extends \ARK\Models\Projects\Project
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->type = \CARD_BASE_PROJECT;
    $this->id = 'P102_HabitatDiversity';
    $this->number = 102;
    $this->name = clienttranslate('Habitat Diversity');
    $this->desc = clienttranslate('Requires different **continent** icons in your zoo.');
    $this->icon = 'all-continents';
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
    $icons = $player->countCardIcons(true, CONTINENTS);
    $n = count($icons);
    return $n + $bonusIcon >= $slot['condition'];
  }
}
