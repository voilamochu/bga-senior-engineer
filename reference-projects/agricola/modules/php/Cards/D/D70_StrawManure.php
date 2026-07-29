<?php
namespace AGR\Cards\D;
use AGR\Core\Notifications;
use AGR\Managers\Meeples;
use AGR\Managers\Players;
use AGR\Helpers\Utils;


class D70_StrawManure extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D70_StrawManure';
    $this->name = ('Straw Manure');
    $this->deck = 'D';
    $this->number = 70;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Before the field phase of each harvest, you can pay 1 <GRAIN> from your supply to add 1 <VEGETABLE> to each of up to 2 vegetable fields.'
      ),
    ];
    $this->prerequisite = clienttranslate('2 Fields');
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if ($player->board()->countFields() < 2) {
      return false;
    }
         return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function preFeedingGoodsWanted()
  {
    return [GRAIN];
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'StartHarvestFieldPhase';
  }

  public function onPlayerStartHarvestFieldPhase($player, $event)
  {
    return [
      'countAsUse' => true,
      'type' => NODE_SEQ,
       'optional' => true,
      'childs' => [
        $this->payNode([GRAIN => 1]),
        [
          'action' => SPECIAL_EFFECT,
          'optional' => false,
          'args' => [
            'cardId' => $this->id,
            'method' => 'addFieldVeg',
          ],
        ],
      ],
    ];
  }

  public function getSourceFields()
  {
    $fields = $this->getPlayer()->board()->getVegetableFields();
    Utils::filter($fields, function ($field) {
      return count($field['crops']) >= 1;
    });
    return $fields;
  }

  public function getAddFieldVegDescription()
  {
    return clienttranslate('Add 1 <VEGETABLE> to 1 or 2 field(s)');
  }

  public function actAddFieldVeg($zones)
  {
    $player = Players::getActive();

    if (count($zones) != 1 && count($zones) != 2) {
      throw new \BgaVisibleSystemException('Select only one or two field(s)');
    }
    $args = $this->argsAddFieldVeg();
    $zone = $zones[0];

    $sows = [];

 
    foreach($zones as $zone){
      if (!in_array($zone, $args['sources'])) {
        throw new \BgaVisibleSystemException('Invalid field(s)');
      }
      if($zone['fieldType'] != VEGETABLE) { 
        throw new \BgaVisibleSystemException('Select vegetable fields');
      }
      $field = $zone;
      $type = $field['crops'][0]['type'];
      $location = $field['type'] == 'fieldCard' ? $field['id'] : 'board';

      $ids = Meeples::createResourceInLocation(
        $type,
        $location,
        $field['pId'],
        $field['x'],
        $field['y'],
        1
      );

      $sows[] = [
        'field' => $field['id'],
        'type' => $type,
        'crops' => Meeples::getMany($ids)->toArray(),
      ];
    }
    Notifications::sow($player, $sows, true);
    Notifications::updateDropZones($player);
  }

  public function argsAddFieldVeg()
  {
    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} may pay 1 <GRAIN> from supply to add 1 <VEGETABLE> to each of up to 2 vegetable fields (Straw Manure)'),
      'descriptionmyturn' => clienttranslate('Select field(s) (Straw Manure)'),
      'sources' => $this->getSourceFields(),
    ];
  }
}
