<?php

namespace ARK\Cards\Sponsors;

class S280_Reconstruction extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'S280_Reconstruction';
    $this->number = 280;
    $this->name  = clienttranslate("Reconstruction");
    $this->lvl = 3;
    $this->effects = [
      PASSIVE => [clienttranslate('FOR THE REST OF THE GAME, for EVERY placement bonus (and every H icon on zoo map 8) you cover, DO NOT GET get the bonus.')],
      IMMEDIATE => [
        clienttranslate('You may rearrange up to 3 buildings on your zoo map.'),
        clienttranslate('Take up to 3 buildings off your zoo map and then place them again for free (the usual building rules apply).'),
        clienttranslate("It's OK if this separates your buildings from each other."),
        clienttranslate('You may also place 1 pavilion and/or 1 kiosk for free (the usual building rules apply).'),
        clienttranslate('You do not get placement bonuses for placing any of these buildings, nor do you get new Sponsor cards for covering the H icons on zoo map 8.'),
        clienttranslate('If your zoo map is fully covered, you do not gain the bonus for that again by repositioning buildings.')
      ],
      ENDGAME => [clienttranslate('Gain 5 appeal if you have completely covered your zoo map (all spaces except the rock and water spaces).')],
    ];
  }

  public function getImmediate()
  {
    return [
      [RECONSTRUCTION_REMOVE => 1],
      [
        'type' => NODE_PARALLEL,
        'childs' => [
          [
            'action' => BUILD,
            'args' => ['free' => true, 'freeBuilding' => PAVILION, 'canPass' => true],
            'sourceId' => $this->id
          ],
          [
            'action' => BUILD,
            'args' => ['free' => true, 'freeBuilding' => KIOSK, 'canPass' => true],
            'sourceId' => $this->id
          ],
        ]
      ]
    ];
  }

  public function score()
  {
    $player = $this->getPlayer();
    $emptySpaces = $player->map()->countEmptySpaces();
    if ($emptySpaces == 0) {
      $player->incAppeal(5, true, $this->getName());
    }
  }
}
