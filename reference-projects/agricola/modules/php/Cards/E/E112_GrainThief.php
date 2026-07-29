<?php
namespace AGR\Cards\E;

use AGR\Core\Globals;

class E112_GrainThief extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E112_GrainThief';
    $this->name = clienttranslate('Grain Thief');
    $this->deck = 'E';
    $this->author = 'letsdance';
    $this->number = 112;
    $this->category = 'CROPS_-_GRAIN';
    $this->desc = [
      clienttranslate(
        'Each time you would harvest a grain field, you can leave the grain on the field and take 1 <GRAIN> from the general supply instead.'
      ),
    ];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    return ($this->isPlayerEvent($event) && $event['type'] == 'StartHarvestFieldPhase' && !empty($this->getGrainFields())) ||
      ($this->isPlayerEvent($event) && $event['type'] == 'EndHarvestFieldPhase');
  }

  public function getGrainFields()
  {
    return $this->getPlayer()->board()->getGrainFields();
  }

  public function onPlayerStartHarvestFieldPhase($player)
  {
    return empty($this->getGrainFields())
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

  public function onPlayerEndHarvestFieldPhase($player)
  {
    $zones = Globals::getGrainThiefFields();
    if (count($zones)) {
      return [
        'type' => NODE_SEQ,
        'pId' => $this->pId,
        'childs' => [
          $this->gainNode([GRAIN => count($zones)]),
          [
            'action' => SPECIAL_EFFECT,
            'args' => [
              'cardId' => $this->id,
              'method' => 'unsetGrainThiefFields',
            ],
          ],
        ],
      ];
    }
  }

  public function getAdditionalCropHarvestDescription()
  {
    return clienttranslate('Gain grain from supply instead of harvesting');
  }

  public function argsAdditionalCropHarvest()
  {
    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} may choose grain fields not to harvest (Grain Thief)'),
      'descriptionmyturn' => clienttranslate('${you} may choose grain fields not to harvest (Grain Thief)'),
      'zones' => $this->getGrainFields(),
    ];
  }

  public function actAdditionalCropHarvest($zones)
  {
    Globals::setGrainThiefFields($zones);
  }

  public function unsetGrainThiefFields()
  {
    Globals::setGrainThiefFields([]);
  }
}