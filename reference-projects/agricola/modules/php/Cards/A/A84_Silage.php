<?php
namespace AGR\Cards\A;
use AGR\Managers\Meeples;
use AGR\Helpers\Utils;
use AGR\Core\Notifications;
use AGR\Core\Engine;
use AGR\Core\Globals;

class A84_Silage extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A84_Silage';
    $this->name = clienttranslate('Silage');
    $this->deck = 'A';
    $this->number = 84;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In each returning home phase after which there is no harvest, you can pay exactly 1 <GRAIN> - even from a field - to breed exactly one type of animal.'
      ),
    ];
    $this->prerequisite = clienttranslate('2 Fields');
    $this->isArtifexOrBubulcus = true;
    $this->bannedWeak1or2p = true;
    $this->bannedWeak3or4p = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if ($player->board()->countFields() < 2) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'ReturnHome' && !in_array( Globals::getTurn(), [4, 7, 9, 11, 13, 14]);
  }

  public function onPlayerReturnHome($player, $event)
  {
    $breedTypes = $player->breedTypes();
    
    if (!$breedTypes[SHEEP] && !$breedTypes[PIG] && !$breedTypes[CATTLE]) {
      return;
    }

    $childs = [];
    foreach ($breedTypes as $type => $canBreed) {
      if (!$canBreed) {
        continue;
      }

      $childs[] = [
        'action' => SPECIAL_EFFECT,
        'args' => [
          'cardId' => $this->id,
          'method' => 'breedAnimal',
          'args' => [$type],
        ],
      ];
    }

    $payGrain = [
      'type' => NODE_XOR,
      'optional' => false,
      'childs' => [
        [
          'action' => SPECIAL_EFFECT,
          'optional' => false,
          'args' => [
            'cardId' => $this->id,
            'method' => 'eatFieldGrain',
          ],
        ],
        $this->payNode([GRAIN => 1]),
      ],
    ];

    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'countAsUse' => true,
      'childs' => [
        $payGrain,
        [
          'type' => NODE_XOR,
          'childs' => $childs,
        ],
      ],
    ];
  }

  public function getBreedAnimalDescription($type)
  {
    $type = '<' . strtoupper($type) . '>';

    return [
      'log' => clienttranslate('Breed ${animal_type}'),
      'args' => [
        'animal_type' => $type,
      ],
    ];
  }

  public function breedAnimal($type)
  {
    $player = $this->getPlayer();
    if (!$player->breed($type, 'card')) {
      return;
    }

    $flow = [
      'action' => REORGANIZE,
      'pId' => $player->getId(),
      'args' => [
        'trigger' => HARVEST,
        'breedTypes' => [$type => true],
      ],
    ];
          
    Engine::insertAsChild($flow);
  }

  public function getSourceFields()
  {
    $fields = $this->getPlayer()->board()->getGrainFields();
    Utils::filter($fields, function ($field) {
      return count($field['crops']) >= 1;
    });
    return $fields;
  }

  public function getEatFieldGrainDescription()
  {
    return clienttranslate('Remove 1<GRAIN> from a field');
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
  }

  public function argsEatFieldGrain()
  {
    return [
      'cardId' => $this->id,
      'description' => clienttranslate('${actplayer} may discard 1 <GRAIN> from a field to breed (Silage)'),
      'descriptionmyturn' => clienttranslate('Select field (Silage)'),
      'sources' => $this->getSourceFields(),
    ];
  }
}