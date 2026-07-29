<?php
namespace AGR\Cards\E;

use AGR\Helpers\UsedText;
use AGR\Helpers\CardRulings;
use AGR\Core\Globals;
use AGR\Managers\Meeples;

class E73_Scythe extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E73_Scythe';
    $this->name = clienttranslate('Scythe');
    $this->deck = 'E';
    $this->author = 'cunning';
    $this->number = 73;
    $this->category = 'CROPS_-_GRAIN_AND_VEGETABLE';
    $this->desc = [
      clienttranslate(
        'During the field phase of each harvest, you can select exactly one of your fields and harvest all the crops planted in it.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->rulings = CardRulings::fromKeys([
      'WOOD_STONE_NOT_CROPS',
    ]);
    $this->usedText = UsedText::get('FIELDS_HARVESTED');
  }

  public function isListeningTo($event)
  {
    return ($this->isPlayerEvent($event) && $event['type'] == 'StartHarvestFieldPhase' && !empty($this->getFields()) || 
      ($this->isPlayerEvent($event) && $event['type'] == 'EndHarvest'));
  }

  public function getFields()
  {
    $fields = $this->getPlayer()->board()->getPlantedFields();
    $zones = [];

    foreach ($fields as $field) {
      if (count($field['crops']) >= 2 && in_array($field['crops'][0]['type'], CROPS)) {
        $zones[] = $field;
      }
    }

    return $zones;
  }

  public function onPlayerHarvestFieldPhase($player)
  {
    return empty($this->getFields())
      ? null
      : [
        'action' => SPECIAL_EFFECT,
        'optional' => true,
        'args' => [
          'cardId' => $this->id,
          'method' => 'harvestAllCrops',
        ],
      ];
  }

  public function onPlayerStartHarvestFieldPhase($player)
  {
    return empty($this->getFields())
      ? null
      : [
        'action' => SPECIAL_EFFECT,
        'optional' => true,
        'countAsUse' => true,
        'args' => [
          'cardId' => $this->id,
          'method' => 'harvestAllCrops',
        ],
      ];
  }

  public function onPlayerEndHarvest($player)
  {
    Globals::setScytheField([]);
  }

  public function getHarvestAllCropsDescription()
  {
    return clienttranslate('Choose a field to harvest completely');
  }

  public function argsHarvestAllCrops()
  {
    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} may choose a field to harvest completely (Scythe)'),
      'descriptionmyturn' => clienttranslate('${you} may choose a field to harvest completely (Scythe)'),
      'zones' => $this->getFields(),
    ];
  }

  public function actHarvestAllCrops($field)
  {
    Globals::setScytheField($field);    
  }
}
