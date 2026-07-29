<?php

namespace AGR\Cards\A;

use AGR\Core\Engine;
use AGR\Core\Globals;
use AGR\Models\MinorImprovement;
use AGR\Managers\Meeples;

class A70_LiftingMachine extends MinorImprovement {
  public function __construct($row) {
    parent::__construct($row);
    $this->id = 'A70_LiftingMachine';
    $this->name = clienttranslate('Lifting Machine');
    $this->deck = 'A';
    $this->number = 70;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'At the end of each round that does not end with a harvest, you can move 1 <VEGETABLE> from one of your fields to your supply. (This is not considered a field phase.)'
      ),
    ];
    $this->cost = [
      WOOD => '1',
    ];
    $this->prerequisite = clienttranslate('3 Fields');
    $this->isArtifexOrBubulcus = true;
    $this->bannedWeak1or2p = true;
    $this->bannedWeak3or4p = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = []) {
    if($player->board()->countFields() < 3) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function isListeningTo($event) {
    return $this->isPlayerEvent($event) &&
      $event['type'] == 'EndOfRound';
  }

  public function onPlayerEndOfRound($player) {
    $turn = Globals::getTurn();
    $harvestRounds = [4, 7, 9, 11, 13, 14];
    $vegFields = $this->getVegetableFields();
    if(in_array($turn, $harvestRounds) || empty($vegFields)) {
      return null;
    }

    if (count($vegFields) == 1) {
      return [
        'countAsUse' => true,
        'action' => SPECIAL_EFFECT,
        'optional' => true,
        'args' => [
          'cardId' => $this->id,
          'method' => 'moveVegetable',
          'args' => [$vegFields[0]],
        ],
      ];
    }

    return [
      'countAsUse' => true,
      'action' => SPECIAL_EFFECT,
      'optional' => true,
      'args' => [
        'cardId' => $this->id,
        'method' => 'liftingMachine',
      ],
    ];
  }

  public function getVegetableFields() {
    return $this->getPlayer()->board()->getVegetableFields();
  }

  public function getLiftingMachineDescription() {
    return clienttranslate('Take 1<VEGETABLE> from one of your fields');
  }

  public function argsLiftingMachine() {
    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} may take 1<VEGETABLE> from one of their fields (Lifting Machine)'),
      'descriptionmyturn' => clienttranslate('${you} may take 1<VEGETABLE> from one of your fields (Lifting Machine)'),
      'zones' => $this->getVegetableFields(),
    ];
  }

  public function actLiftingMachine($zones) {
    $source = $zones[0];
    Engine::insertAsChild(
      [
        'action' => SPECIAL_EFFECT,
        'args' => [
          'cardId' => $this->id,
          'method' => 'moveVegetable',
          'args' => [$source],
        ],
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

  public function getMoveVegetableDescription($source) {
    return clienttranslate('Take <VEGETABLE> from field');
  }
}
