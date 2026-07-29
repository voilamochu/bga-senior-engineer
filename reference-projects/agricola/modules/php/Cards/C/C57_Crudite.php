<?php

namespace AGR\Cards\C;

use AGR\Core\Engine;
use AGR\Core\Notifications;
use AGR\Helpers\Utils;
use AGR\Managers\Meeples;
use AGR\Models\MinorImprovement;

class C57_Crudite extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C57_Crudite';
    $this->name = clienttranslate('Crudité');
    $this->deck = 'C';
    $this->number = 57;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you can immediately buy exactly 1 <VEGETABLE> for 3 <FOOD>. At any time, you can discard 1 <VEGETABLE> on top of another <VEGETABLE> in a field to get 4 <FOOD>.'
      ),
    ];
    $this->isCorbariusOrDulcinaria = true;
  }

  public function onBuy($player)
  {
    return $this->payGainNode([FOOD => 3], [VEGETABLE => 1]);
  }

  public function isListeningTo($event)
  {
    return !empty($this->getSourceFields()) &&
      (
        $this->isAnytime($event) ||
        ($this->isPlayerEvent($event) && $event['type'] == 'StartHarvestFieldPhase')
      );
  }

  public function getEatFieldVegMethod()
  {
    return count($this->getSourceFields()) == 1 ? 'autoEatFieldVeg' : 'eatFieldVeg';
  }

  public function onPlayerStartHarvestFieldPhase($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'countAsUse' => true,
      'childs' => [
        [
          'action' => SPECIAL_EFFECT,
          'optional' => false,
          'args' => [
            'cardId' => $this->id,
            'method' => 'eatFieldVeg',
          ],
        ],
      ],
    ];
  }

  public function getSourceFields()
  {
    $fields = $this->getPlayer()->board()->getVegetableFields();
    Utils::filter($fields, function ($field) {
      return count($field['crops']) >= 2;
    });
    return $fields;
  }

  public function onPlayerAtAnytime($player, $event)
  {
    return [
      'action' => SPECIAL_EFFECT,
      'countAsUse' => true,
      'args' => [
        'cardId' => $this->id,
        'method' => $this->getEatFieldVegMethod(),
      ],
    ];
  }

  public function getAutoEatFieldVegDescription()
  {
    return clienttranslate('Discard field <VEGETABLE> for 4<FOOD>');
  }

  public function autoEatFieldVeg()
  {
    $sources = $this->getSourceFields();
    $zone = $sources[0];
    $meeples = [$zone['crops'][0]];
    $locations = [$zone['uid']];
    Meeples::deleteResources($meeples);
    Notifications::payResourcesFromFields($this->getPlayer(), $meeples, $this->name, $locations);
    Engine::insertAsChild($this->gainNode([FOOD => 4]));
  }

  public function getEatFieldVegDescription()
  {
    return clienttranslate('Discard field <VEGETABLE> for 4<FOOD>');
  }

  public function actEatFieldVeg($zones)
  {
    if (empty($zones)) {
      throw new \BgaVisibleSystemException('Select at least one field');
    }

    $args = $this->argsEatFieldVeg();
    $meeples = [];
    $locations = [];

    foreach ($zones as $zone) {
      if (!in_array($zone, $args['sources'])) {
        throw new \BgaVisibleSystemException('Invalid field(s)');
      }
      if ($zone['fieldType'] != VEGETABLE) {
        throw new \BgaVisibleSystemException('Select vegetable fields');
      }

      $meeples[] = $zone['crops'][0];
      $locations[] = $zone['uid'];
    }

    Meeples::deleteResources($meeples);
    Notifications::payResourcesFromFields($this->getPlayer(), $meeples, $this->name, $locations);
    Engine::insertAsChild($this->gainNode([FOOD => count($zones) * 4]));
  }

  public function argsEatFieldVeg()
  {
    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} may discard 1 <VEGETABLE> from each selected field to get 4 <FOOD> per field (Crudité)'),
      'descriptionmyturn' => clienttranslate('Select field(s) (Crudité)'),
      'sources' => $this->getSourceFields(),
    ];
  }
}