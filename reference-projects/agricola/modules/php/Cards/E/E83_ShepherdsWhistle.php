<?php
namespace AGR\Cards\E;

use AGR\Core\Engine;
use AGR\Helpers\Utils;

class E83_ShepherdsWhistle extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E83_ShepherdsWhistle';
    $this->name = clienttranslate("Shepherd's Whistle");
    $this->deck = 'E';
    $this->author = 'inoshishi';
    $this->number = 83;
    $this->category = 'ANIMALS_';
    $this->desc = [
      clienttranslate(
        'At the start of the breeding phase of each harvest, if you have at least 1 unfenced stable without an animal, you get 1 <SHEEP>.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->implemented = true;
  }
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'EndHarvestFeedingPhase';
  }

  public function onPlayerEndHarvestFeedingPhase($player)
  {
    if ($this->hasEmptyUnfencedStable()) {
      return $this->gainNode([SHEEP => 1]);
    }

    if ($this->hasUnfencedStableWithoutAnimalSpace()) {
      return [
        'type' => NODE_SEQ,
        'optional' => true,
        'customDescription' => clienttranslate('Reorganize animals'),
        'childs' => [
          [
            'action' => REORGANIZE,
            'args' => [
              'trigger' => ANYTIME,
            ],
          ],
          [
            'action' => SPECIAL_EFFECT,
            'args' => [
              'cardId' => $this->id,
              'method' => 'checkGainSheep',
            ],
          ],
        ],
      ];
    }
  }

  public function checkGainSheep()
  {
    if ($this->hasEmptyUnfencedStable()) {
      Engine::insertAsChild($this->gainNode([SHEEP => 1]));
    }
  }

  protected function getUnfencedStableZones()
  {
    $zones = $this->getPlayer()->board()->getAnimalsDropZonesWithAnimals();
    Utils::filter($zones, function ($zone) {
      return $zone['type'] == 'stable';
    });
    return $zones;
  }

  protected function hasEmptyUnfencedStable()
  {
    foreach ($this->getUnfencedStableZones() as $zone) {
      if ($zone['animals'] == 0) {
        return true;
      }
    }
    return false;
  }

  protected function hasUnfencedStableWithoutAnimalSpace()
  {
    $zones = $this->getUnfencedStableZones();
    return !empty($zones) && !$this->hasEmptyUnfencedStable();
  }
}
