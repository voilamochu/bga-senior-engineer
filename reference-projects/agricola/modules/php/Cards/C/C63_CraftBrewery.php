<?php

namespace AGR\Cards\C;

use AGR\Core\Engine;
use AGR\Core\Notifications;
use AGR\Helpers\Utils;
use AGR\Managers\Meeples;
use AGR\Models\MinorImprovement;

class C63_CraftBrewery extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C63_CraftBrewery';
    $this->name = clienttranslate('Craft Brewery');
    $this->deck = 'C';
    $this->number = 63;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In the feeding phase of each harvest, you can use this card to exchange 1 <GRAIN> from your supply plus 1 <GRAIN> from a field for 2 bonus <SCORE> and 4 <FOOD>.'
      ),
    ];
    $this->cost = [
      WOOD => '2',
      CLAY => '1',
    ];
    $this->extraVp = true;
    $this->isCorbariusOrDulcinaria = true;
    $this->bannedStrong1or2p = true;
    $this->bannedStrong3or4p = true;
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'HarvestFeedingPhase' && !empty($this->getSourceFields());
  }

  public function getSourceFields()
  {
    $fields = $this->getPlayer()->board()->getGrainFields();
    Utils::filter($fields, function ($field) {
      return count($field['crops']) >= 1;
    });
    return $fields;
  }

  public function onPlayerHarvestFeedingPhase($player, $event)
  {
    $fields = $this->getSourceFields();

    if (count($fields) == 1) {
      return [
        'type' => NODE_SEQ,
        'countAsUse' => true,
        'childs' => [
          [
            'action' => SPECIAL_EFFECT,
            'args' => [
              'cardId' => $this->id,
              'method' => 'eatSingleFieldGrain',
              'args' => [$fields[0]],
            ],
          ],
          $this->payGainNode([GRAIN => 1], [FOOD => 4, SCORE => 2], null, false),
        ],
      ];
    }

    return [
      'type' => NODE_SEQ,
      'countAsUse' => true,
      'childs' => [
        [
          'action' => SPECIAL_EFFECT,
          'optional' => false,
          'args' => [
            'cardId' => $this->id,
            'method' => 'eatFieldGrain',
          ],
        ],
        $this->payGainNode([GRAIN => 1], [FOOD => 4, SCORE => 2], null, false),
      ],
    ];
  }

  public function eatSingleFieldGrain($zone)
  {
    $this->actEatFieldGrain([$zone]);
  }

  public function getEatFieldGrainDescription()
  {
    return clienttranslate('Remove 1 <GRAIN> from a field');
  }

  public function actEatFieldGrain($zones)
  {
    if (count($zones) != 1) {
      throw new \BgaVisibleSystemException('Select only one field');
    }
    $args = $this->argsEatFieldGrain();
    $zone = $zones[0];
    if (!in_array($zone, $args['sources'])) {
      throw new \BgaVisibleSystemException('Invalid field(s)');
    }
    if($zone['fieldType'] != GRAIN) { 
      throw new \BgaVisibleSystemException('Select a grain field');
    }
    $meeples = array($zone['crops'][0]);
    Meeples::deleteResources($meeples);
    $locations = array($zone['uid']);
    Notifications::payResourcesFromFields($this->getPlayer(), $meeples, $this->name,$locations);

    if (($zone['id'] ?? null) == 'E70_CropRotationField') {
      if (Meeples::getResourcesOnCard('E70_CropRotationField', null, GRAIN)->count() == 0) {
        // XOR wrapper asks "use Crop Rotation Field?" first; once chosen, stSow auto-sows.
        // Inner leaf must NOT be optional or the engine auto-picks it and skips the ask.
        Engine::insertAsChild([
          'type' => NODE_XOR,
          'optional' => true,
          'childs' => [[
            'action' => SOW,
            'args' => [
              'max' => 1,
              'type' => VEGETABLE,
              'location' => ['E70_CropRotationField'],
              'auto' => true,
            ]
          ]],
        ]);
      }
    }
  }

  public function argsEatFieldGrain()
  {
    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} may discard 1 <GRAIN> from a field and 1 <GRAIN> from their supply to get 2 bonus <SCORE> and 4 <FOOD> (Craft Brewery)'),
      'descriptionmyturn' => clienttranslate('Select field (Craft Brewery)'),
      'sources' => $this->getSourceFields(),
    ];
  }
}