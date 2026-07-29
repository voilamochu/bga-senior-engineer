<?php
namespace AGR\Cards\D;

use AGR\Core\Notifications;
use AGR\Models\PlayerBoard;
use AGR\Helpers\Utils;
use AGR\Managers\Meeples;
use AGR\Core\Globals;

class D72_StableManure extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D72_StableManure';
    $this->name = clienttranslate('Stable Manure');
    $this->deck = 'D';
    $this->number = 72;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In the field phase of each harvest, you can harvest 1 additional good from a number of fields equal to the number of unfenced stables you have.'
      ),
    ];
    $this->prerequisite = clienttranslate('At Most 1 Occupation');
    $this->occupationPrerequisites = ['max' => 1];
    $this->isCorbariusOrDulcinaria = true;
    $this->bannedWeak3or4p = true;
  }

  public function isListeningTo($event)
  {
    return ($this->isPlayerEvent($event) && $event['type'] == 'StartHarvestFieldPhase' && !empty($this->getFieldsAndCrops()) && $this->unfencedStables() > 0) ||
      ($this->isPlayerEvent($event) && $event['type'] == 'EndHarvest');
  }

  public function getFieldsAndCrops()
  {
    $player = $this->getPlayer();
    $min = 2;
    if ($player->hasPlayedCard('E112_GrainThief')) {
      $min = 1;
    }
    $fields = $this->getPlayer()->board()->getFieldsAndCrops();
    $zones = [];
    foreach ($fields as $field) {
      $m = $field['fieldType'] == GRAIN ? $min : 2;
      if (count($field['crops']) >= $m) {
        $zones[] = $field;
      }
    }
    return $zones;
  }

  public function onPlayerStartHarvestFieldPhase($player)
  {
    return empty($this->getFieldsAndCrops())
      ? null
      : [
        'action' => SPECIAL_EFFECT,
        'optional' => true,
        'args' => [
          'cardId' => $this->id,
          'method' => 'additionalCropHarvest',
        ],
      ];
  }


  public function onPlayerEndHarvest($player)
  {
    Globals::setStableManureFields([]);
  }

  public function getAdditionalCropHarvestDescription()
  {
    return clienttranslate('Harvest additional good');
  }

  public function argsAdditionalCropHarvest()
  {
    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} may harvest additional goods (Stable Manure)'),
      'descriptionmyturn' => clienttranslate('${you} may choose fields from which to harvest 1 extra good (Stable Manure)'),
      'zones' => $this->getFieldsAndCrops(),
    ];
  }

  public function actAdditionalCropHarvest($fields)
  {
    if ($this->unfencedStables() < count($fields)) {
      throw new \BgaVisibleSystemException('Select a number of fields less than or equal to the number of unfenced stables');
    }
    Globals::setStableManureFields($fields);
  }

  function unfencedStables()
  {
    return $this->getPlayer()->board()->countUnfencedStablesForCards();
  }
}
