<?php
namespace AGR\Cards\A;

use AGR\Core\Notifications;
use AGR\Models\PlayerBoard;
use AGR\Helpers\Utils;
use AGR\Managers\Meeples;
use AGR\Core\Globals;

class A112_ScytheWorker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A112_ScytheWorker';
    $this->name = clienttranslate('Scythe Worker');
    $this->deck = 'A';
    $this->number = 112;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <GRAIN>. In the field phase of each harvest, you can harvest 1 additional <GRAIN> from each of your grain fields.'
      ),
    ];
    $this->players = '1+';
  }

  public function onBuy($player)
  {
    return $this->gainNode([GRAIN => 1]);
  }

  public function isListeningTo($event)
  {
    return ($this->isPlayerEvent($event) && $event['type'] == 'StartHarvestFieldPhase' && !empty($this->getGrainFields())) ||
      ($this->isPlayerEvent($event) && $event['type'] == 'EndHarvest');
  }

  public function getGrainFields()
  {
    $player = $this->getPlayer();
    $min = 2;
    if ($player->hasPlayedCard('E112_GrainThief')) {
      $min = 1;
    }
    $fields = $player->board()->getGrainFields();
    $zones = [];
    foreach ($fields as $field) {
      if (count($field['crops']) >= $min) {
        $zones[] = $field;
      }
    }

    return $zones;
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

  public function onPlayerEndHarvest($player)
  {
    Globals::setScytheWorkerFields([]);
  }

  public function getAdditionalCropHarvestDescription()
  {
    return clienttranslate('Harvest additional grain');
  }

  public function argsAdditionalCropHarvest()
  {
    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} may harvest additional grain (Scythe Worker)'),
      'descriptionmyturn' => clienttranslate('${you} may choose grain fields from which to harvest 1 extra <GRAIN> (Scythe Worker)'),
      'zones' => $this->getGrainFields(),
    ];
  }

  public function actAdditionalCropHarvest($zones)
  {
    Globals::setScytheWorkerFields($zones);
  }
}