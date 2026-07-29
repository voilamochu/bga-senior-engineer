<?php

namespace AGR\Cards\A;

use AGR\Core\Engine;
use AGR\Core\Globals;
use AGR\Managers\Meeples;
use AGR\Models\MinorImprovement;

class A58_AsparagusKnife extends MinorImprovement {
  public function __construct($row) {
    parent::__construct($row);
    $this->id = 'A58_AsparagusKnife';
    $this->name = clienttranslate('Asparagus Knife');
    $this->deck = 'A';
    $this->number = 58;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In the returning home phase of rounds 8, 10, and 12, you can take 1 <VEGETABLE> from exactly 1 vegetable field. You can immediately exchange it for 3 <FOOD> and 1 bonus <SCORE>.'
      ),
    ];
    $this->cost = [
      WOOD => '1',
    ];
    $this->extraVp = true;
    $this->isArtifexOrBubulcus = true;
  }

  public function isListeningTo($event) {
    return $this->isPlayerEvent($event) && $event['type'] == 'ReturnHome';
  }

  public function onPlayerReturnHome($player, $event) {
    $turn = Globals::getTurn();
    $vegFields = $this->getVegetableFields();
    if(!in_array($turn, [8, 10, 12]) || empty($vegFields)) {
      return null;
    }

    if (count($vegFields) == 1) {
      return [
        'action' => SPECIAL_EFFECT,
        'optional' => true,
        'args' => [
          'cardId' => $this->id,
          'method' => 'harvestSingleField',
          'args' => [$vegFields[0]],
        ],
      ];
    }

    return [
      'action' => SPECIAL_EFFECT,
      'optional' => true,
      'args' => [
        'cardId' => $this->id,
        'method' => 'asparagusHarvest',
      ],
    ];
  }

  public function getVegetableFields() {
    return $this->getPlayer()->board()->getVegetableFields();
  }

  public function getAsparagusHarvestDescription() {
    return clienttranslate('Choose one vegetable field');
  }

  public function argsAsparagusHarvest() {
    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} must choose vegetable field (Asparagus Knife)'),
      'descriptionmyturn' => clienttranslate('Choose vegetable field (Asparagus Knife)'),
      'zones' => $this->getVegetableFields(),
    ];
  }

  public function actAsparagusHarvest($zones) {
    $source = $zones[0];
    Engine::insertAsChild([
      'type' => NODE_SEQ,
      'childs' => [
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'moveVegetable',
            'args' => [$source],
          ],
        ],
        $this->payGainNode([VEGETABLE => 1], [FOOD => 3, SCORE => 1])
      ]
    ]);
  }

  public function moveVegetable($source) {
    $crop = $source['crops'][0];
    $player = $this->getPlayer();

    $childs = [$this->receiveNode($crop['id'], true, $player)];

    if (($source['id'] ?? null) == 'E70_CropRotationField') {
      if (Meeples::getResourcesOnCard('E70_CropRotationField', null, VEGETABLE)->count() == 1) {
        // XOR wrapper asks "use Crop Rotation Field?" first; once chosen, stSow auto-sows.
        // Inner leaf must NOT be optional or the engine auto-picks it and skips the ask.
        $childs[] = [
          'type' => NODE_XOR,
          'optional' => true,
          'childs' => [[
            'action' => SOW,
            'args' => [
              'max' => 1,
              'type' => GRAIN,
              'location' => ['E70_CropRotationField'],
              'auto' => true,
            ]
          ]],
        ];
      }
    }

    $flow = [
      'type' => NODE_SEQ,
      'childs' => $childs
    ];

    Engine::insertAsChild($flow);
  }

  public function getHarvestSingleFieldDescription($source) {
    return clienttranslate('Take <VEGETABLE> from field');
  }

  public function harvestSingleField($source) {
    $this->actAsparagusHarvest([$source]);
  }

  public function getMoveVegetableDescription($source) {
    return '';
  }
}
